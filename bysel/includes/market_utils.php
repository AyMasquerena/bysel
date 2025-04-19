<?php
/*--------------------------------------------------------------
 | includes/market_utils.php
 | Helper functions for market hours, holidays & queued orders
 *--------------------------------------------------------------*/

require_once __DIR__ . '/db_connect.php';    // ensures $conn + session
require_once __DIR__ . '/price_engine.php';  // ensures updateLivePrices()

/**
 * Check if the market is currently open.
 *
 * Rules:
 *   • Closed on Saturday (6) and Sunday (7)
 *   • Closed on any date listed in market_holidays
 *   • Otherwise open only between open_time / close_time in market_hours
 *
 * @param PDO $conn
 * @return bool
 */
function isMarketOpen(PDO $conn): bool
{
    $now      = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
    $todayStr = $now->format('Y-m-d');

    /* --- weekend check ------------------------------------------------ */
    $dayOfWeek = (int)$now->format('N');   // 1 = Mon … 7 = Sun
    if ($dayOfWeek >= 6) {                 // Sat / Sun
        return false;
    }

    /* --- holiday check ------------------------------------------------ */
    $p = $conn->prepare("SELECT 1 FROM market_holidays WHERE holiday_date = ? LIMIT 1");
    $p->execute([$todayStr]);
    if ($p->fetchColumn()) {
        return false;
    }

    /* --- open/close time check --------------------------------------- */
    $row = $conn
        ->query("SELECT open_time, close_time FROM market_hours WHERE id = 1")
        ->fetch(PDO::FETCH_ASSOC);

    if (!$row) {  // failsafe: treat as open if config missing
        return true;
    }

    $open  = DateTime::createFromFormat('H:i:s', $row['open_time']);
    $close = DateTime::createFromFormat('H:i:s', $row['close_time']);

    $open->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
    $close->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));

    return ($now >= $open && $now <= $close);
}

/**
 * Execute all still‑pending orders if the market is open.
 * Cancels orders that become impossible (no funds / no shares).
 *
 * @param PDO $conn
 */
function processPendingOrders(PDO $conn): void
{
    // 1) Drift prices first
    updateLivePrices($conn);

    // 2) Bail if market is closed
    if (!isMarketOpen($conn)) {
        return;
    }

    /* Fetch all pending orders */
    $stmt = $conn->prepare("
        SELECT po.*
        FROM pending_orders po
        WHERE po.status = 'pending'
        ORDER BY po.created_at ASC
        FOR UPDATE
    ");
    $conn->beginTransaction();
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $order) {
        $userId = (int)$order['user_id'];
        $ticker = $order['ticker'];
        $shares = (int)$order['shares'];
        $type   = $order['type'];

        /* Latest price */
        $p = $conn->prepare("SELECT price FROM stocks WHERE ticker = ?");
        $p->execute([$ticker]);
        $price = (float)$p->fetchColumn();
        if (!$price) {
            // Cancel unknown ticker
            $c = $conn->prepare("
                UPDATE pending_orders
                SET status = 'cancelled', executed_at = NOW()
                WHERE order_id = ?
            ");
            $c->execute([$order['order_id']]);
            continue;
        }

        $total = $shares * $price;

        /* Cash balance (lock row) */
        $p = $conn->prepare("SELECT cash_balance FROM users WHERE id = ? FOR UPDATE");
        $p->execute([$userId]);
        $cash = (float)$p->fetchColumn();

        if ($type === 'buy') {
            if ($cash < $total) {
                // Cancel insufficient funds
                $c = $conn->prepare("
                    UPDATE pending_orders
                    SET status = 'cancelled', executed_at = NOW()
                    WHERE order_id = ?
                ");
                $c->execute([$order['order_id']]);
                continue;
            }
            $newCash = $cash - $total;
            $p = $conn->prepare("UPDATE users SET cash_balance = ? WHERE id = ?");
            $p->execute([$newCash, $userId]);
        } else { // sell
            /* Shares owned */
            $p = $conn->prepare("
                SELECT COALESCE(SUM(
                    CASE WHEN type='buy' THEN shares ELSE -shares END
                ), 0)
                FROM transactions
                WHERE user_id = ? AND ticker = ?
            ");
            $p->execute([$userId, $ticker]);
            $owned = (int)$p->fetchColumn();

            if ($owned < $shares) {
                // Cancel insufficient shares
                $c = $conn->prepare("
                    UPDATE pending_orders
                    SET status = 'cancelled', executed_at = NOW()
                    WHERE order_id = ?
                ");
                $c->execute([$order['order_id']]);
                continue;
            }
            $newCash = $cash + $total;
            $p = $conn->prepare("UPDATE users SET cash_balance = ? WHERE id = ?");
            $p->execute([$newCash, $userId]);
        }

        /* Record transaction */
        $p = $conn->prepare("
            INSERT INTO transactions
                (user_id, type, ticker, shares, amount)
            VALUES (?, ?, ?, ?, ?)
        ");
        $p->execute([$userId, $type, $ticker, $shares, $total]);

        /* Mark order executed */
        $p = $conn->prepare("
            UPDATE pending_orders
            SET status = 'executed', executed_at = NOW()
            WHERE order_id = ?
        ");
        $p->execute([$order['order_id']]);
    }

    $conn->commit();
}

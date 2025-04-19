<?php
/*--------------------------------------------------------------
 | includes/price_engine.php
 | Random‑walk intraday price generator with high/low tracking
 *--------------------------------------------------------------*/

require_once __DIR__ . '/db_connect.php';    // starts session & $conn
require_once __DIR__ . '/market_utils.php';  // for isMarketOpen()

/**
 * Drift each stock price once per minute while the market is open.
 * • ΔP = P * random(‑0.2 %, +0.2 %)
 * • Tracks day_open_price, day_high_price, day_low_price.
 *
 * @param PDO $conn
 */
function updateLivePrices(PDO $conn): void
{
    // Only move prices during market hours
    if (!isMarketOpen($conn)) {
        return;
    }

    // Select stocks not updated in the last 60 seconds (or never updated)
    $stmt = $conn->prepare("
        SELECT stock_id, price, day_open_price, last_update
        FROM stocks
        WHERE last_update IS NULL
           OR TIMESTAMPDIFF(SECOND, last_update, NOW()) >= 60
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $id    = (int)$row['stock_id'];
        $price = (float)$row['price'];

        // First update after market open: reset open, high, and low
        if ($row['last_update'] === null) {
            $conn->prepare("
                UPDATE stocks
                SET day_open_price = :open,
                    day_high_price = :open,
                    day_low_price  = :open,
                    last_update    = NOW()
                WHERE stock_id = :id
            ")->execute([
                'open' => $price,
                'id'   => $id
            ]);
            continue;
        }

        // Compute a random ±0.2% drift
        $percent  = mt_rand(-20, 20) / 10000;       // -0.002 … +0.002
        $newPrice = max(0.01, $price * (1 + $percent));
        $newPrice = round($newPrice, 2);

        // Update price, timestamp, and adjust high/low
        $conn->prepare("
            UPDATE stocks
            SET price          = :p,
                last_update    = NOW(),
                day_high_price = GREATEST(day_high_price, :p),
                day_low_price  = LEAST(day_low_price,  :p)
            WHERE stock_id = :id
        ")->execute([
            'p'  => $newPrice,
            'id' => $id
        ]);
    }
}

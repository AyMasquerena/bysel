<?php
// portfolio.php  –  Display user’s portfolio and cash balance
//--------------------------------------------------------------
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/market_utils.php';
require_once 'includes/header.php';
require_once 'includes/footer.php';

// Execute any pending orders if the market is now open
processPendingOrders($conn);

// Fetch cash balance
$stmt = $conn->prepare("SELECT cash_balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cash_balance = (float)$stmt->fetchColumn();

// Fetch stock holdings (only tickers with >0 shares)
$stmt = $conn->prepare("
    SELECT 
        t.ticker,
        SUM(CASE WHEN t.type = 'buy' THEN t.shares ELSE -t.shares END) AS total_shares
    FROM transactions t
    WHERE t.user_id = ? AND t.ticker IS NOT NULL
    GROUP BY t.ticker
    HAVING total_shares > 0
");
$stmt->execute([$_SESSION['user_id']]);
$holdings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate current prices, individual values, and total portfolio value
$portfolio_value = $cash_balance;
foreach ($holdings as &$stock) {
    $p = $conn->prepare("SELECT price FROM stocks WHERE ticker = ?");
    $p->execute([$stock['ticker']]);
    $price = (float)$p->fetchColumn();

    $stock['current_price'] = $price;
    $stock['value']         = $stock['total_shares'] * $price;
    $portfolio_value       += $stock['value'];
}

displayHeader("Portfolio");
?>

<div class="form-container">
    <h2>Total Portfolio Value: $<?= number_format($portfolio_value, 2) ?></h2>

    <div class="balance-box">
        <h3>Cash Balance: $<?= number_format($cash_balance, 2) ?></h3>
    </div>

    <table>
        <thead>
            <tr>
                <th>Ticker</th>
                <th>Shares</th>
                <th>Current Price</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($holdings as $stock): ?>
            <tr>
                <td><?= htmlspecialchars($stock['ticker']) ?></td>
                <td><?= $stock['total_shares'] ?></td>
                <td>$<?= number_format($stock['current_price'], 2) ?></td>
                <td>$<?= number_format($stock['value'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php displayFooter(); ?>
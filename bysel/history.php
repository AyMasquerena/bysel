<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/footer.php';

// Get transaction history
$stmt = $conn->prepare("
    SELECT transaction_date, type, ticker, shares, amount 
    FROM transactions 
    WHERE user_id = ?
    ORDER BY transaction_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

displayHeader("Transaction History");
?>

<div class="form-container">
    <h2>Transaction History</h2>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Ticker</th>
                <th>Shares</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td><?= date('m/d/Y H:i', strtotime($transaction['transaction_date'])) ?></td>
                <td><?= ucfirst($transaction['type']) ?></td>
                <td><?= $transaction['ticker'] ?></td>
                <td><?= $transaction['shares'] ?? '-' ?></td>
                <td>$<?= number_format($transaction['amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php displayFooter(); ?>
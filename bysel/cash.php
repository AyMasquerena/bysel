<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/footer.php';

// Get current cash balance
$stmt = $conn->prepare("SELECT cash_balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$cash_balance = $user['cash_balance'];

displayHeader("Cash Management");
?>

<div class="form-container">
    <h2>Cash Balance: $<?= number_format($cash_balance, 2) ?></h2>
    
    <form action="handlers/cash_handler.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group">
            <label>Transaction Type:</label>
            <select name="transaction_type" required>
                <option value="deposit">Deposit</option>
                <option value="withdraw">Withdraw</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Amount:</label>
            <input type="number" name="amount" step="0.01" min="0.01" required>
        </div>
        
        <button type="submit" class="btn-primary">Process Transaction</button>
    </form>
</div>

<?php displayFooter(); ?>
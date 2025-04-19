<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/footer.php';

displayHeader("Transact");
?>

<div class="form-container">
    <h2>Stock Transaction</h2>
    
    <form action="handlers/transact_handler.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group">
            <label>Transaction Type:</label>
            <select name="transaction_type" required>
                <option value="buy">Buy</option>
                <option value="sell">Sell</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Ticker Symbol:</label>
            <input type="text" name="ticker" pattern="[A-Z]{1,5}" required>
        </div>
        
        <div class="form-group">
            <label>Shares:</label>
            <input type="number" name="shares" min="1" required>
        </div>
        
        <button type="submit" class="btn-primary">Execute Trade</button>
    </form>
</div>

<?php displayFooter(); ?>
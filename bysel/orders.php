<?php
// orders.php  —  View & manage your queued trades
//--------------------------------------------------
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/market_utils.php';   // flushes when market opens
require_once 'includes/header.php';
require_once 'includes/footer.php';

/* Execute any due orders first */
processPendingOrders($conn);

/* Fetch still‑pending orders for this user */
$stmt = $conn->prepare("
    SELECT order_id, ticker, shares, type, price_at_order, created_at
    FROM pending_orders
    WHERE user_id = ? AND status = 'pending'
    ORDER BY created_at ASC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

displayHeader("Queued Orders");
?>

<div class="form-container">
    <h2>Pending Orders</h2>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'cancelled'): ?>
        <div class="success-message">Order cancelled.</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <p>You have no pending orders at the moment.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date&nbsp;Queued</th>
                    <th>Type</th>
                    <th>Ticker</th>
                    <th>Shares</th>
                    <th>Price&nbsp;at&nbsp;Order</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?= date('m/d/Y&nbsp;H:i', strtotime($o['created_at'])) ?></td>
                    <td><?= ucfirst($o['type']) ?></td>
                    <td><?= htmlspecialchars($o['ticker']) ?></td>
                    <td><?= $o['shares'] ?></td>
                    <td>$<?= number_format($o['price_at_order'], 2) ?></td>
                    <td>
                        <form action="handlers/order_cancel_handler.php" method="post" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="order_id"   value="<?= $o['order_id'] ?>">
                            <button type="submit" class="btn-secondary" onclick="return confirm('Cancel this order?');">
                                Cancel
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php displayFooter(); ?>

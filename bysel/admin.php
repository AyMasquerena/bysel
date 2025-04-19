<?php
// admin.php  –  Admin Dashboard
//--------------------------------------------------------------
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/footer.php';

// Verify admin privileges
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Capture messages
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

displayHeader("Admin Dashboard");
?>

<div class="admin-container">
    <!-- Stock Management -->
    <h1>Stock Management</h1>

    <?php if ($success): ?>
        <div class="success-message"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="stock-form" action="handlers/stock_handler.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-group">
            <label for="action">Action Type:</label>
            <select id="action" name="action" required>
                <option value="create">Create Stock</option>
                <option value="update">Update Stock</option>
                <option value="delete">Delete Stock</option>
            </select>
        </div>

        <div class="form-group">
            <label for="company">Company Name:</label>
            <input type="text" id="company" name="company" required>
        </div>

        <div class="form-group">
            <label for="ticker">Ticker Symbol:</label>
            <input type="text" id="ticker" name="ticker"
                   pattern="[A-Z]{1,5}"
                   title="1-5 uppercase letters"
                   required>
        </div>

        <div class="form-group">
            <label for="price">Price per Share ($):</label>
            <input type="number" id="price" name="price"
                   step="0.01" min="0.01" required>
        </div>

        <div class="form-group">
            <label for="volume">Available Volume:</label>
            <input type="number" id="volume" name="volume"
                   min="1" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Execute Action</button>
            <button type="reset"  class="btn-secondary">Reset Form</button>
        </div>
    </form>

    <!-- Market Hours -->
    <h1 style="margin-top:3rem;">Market Hours</h1>

    <?php
        $stmt = $conn->query("SELECT open_time, close_time FROM market_hours WHERE id = 1");
        $hours = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    <form class="stock-form" action="handlers/market_hours_handler.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-group">
            <label for="open_time">Open Time (HH:MM):</label>
            <input type="time" id="open_time" name="open_time"
                   value="<?= htmlspecialchars(substr($hours['open_time'],0,5)) ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="close_time">Close Time (HH:MM):</label>
            <input type="time" id="close_time" name="close_time"
                   value="<?= htmlspecialchars(substr($hours['close_time'],0,5)) ?>"
                   required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Update Hours</button>
        </div>
    </form>

    <!-- Market Holidays -->
    <h1 style="margin-top:3rem;">Market Holidays</h1>

    <?php
        $holidays = $conn
            ->query("SELECT holiday_date, description FROM market_holidays ORDER BY holiday_date")
            ->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th style="width:120px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($holidays)): ?>
                <tr><td colspan="3">No holidays configured.</td></tr>
            <?php else: ?>
                <?php foreach ($holidays as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['holiday_date']) ?></td>
                        <td><?= htmlspecialchars($h['description']) ?></td>
                        <td>
                            <form action="handlers/holiday_handler.php" method="post" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="holiday_date" value="<?= $h['holiday_date'] ?>">
                                <button type="submit" class="btn-secondary"
                                        onclick="return confirm('Remove this holiday?');">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <form class="stock-form" action="handlers/holiday_handler.php" method="post" style="margin-top:2rem;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="add">

        <div class="form-group">
            <label for="holiday_date">Holiday Date:</label>
            <input type="date" id="holiday_date" name="holiday_date" required>
        </div>

        <div class="form-group">
            <label for="description">Description:</label>
            <input type="text" id="description" name="description"
                   maxlength="100" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Add Holiday</button>
        </div>
    </form>

    <!-- Manual Price Adjust -->
    <h1 style="margin-top:3rem;">Adjust Stock Price</h1>

    <form class="stock-form" action="handlers/price_adjust_handler.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-group">
            <label for="ticker_adj">Ticker Symbol:</label>
            <input type="text" id="ticker_adj" name="ticker"
                   pattern="[A-Z]{1,5}" required>
        </div>

        <div class="form-group">
            <label for="percent">Max % Change (e.g. 2 = ±2%)</label>
            <input type="number" id="percent" name="percent"
                   step="0.01" min="0" required>
        </div>

        <div class="form-group" style="grid-column:1 / -1;">
            <label>
                <input type="checkbox" name="negative_only" value="1">
                Limit to neutral/negative move (0% to –%max)
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Apply Adjustment</button>
        </div>
    </form>

    <!-- Manual Price Drift Trigger -->
    <h1 style="margin-top:3rem;">Manual Price Drift</h1>

    <form class="stock-form" action="handlers/run_drift_handler.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-actions">
            <button type="submit" class="btn-secondary">
                Run Price Drift Now
            </button>
        </div>
    </form>
</div>

<?php
displayFooter();
?>

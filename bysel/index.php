<?php
// index.php  –  Home / landing page
//--------------------------------------------------------------
require_once 'includes/db_connect.php';
require_once 'includes/auth_check.php';
require_once 'includes/market_utils.php';   // for market‑status banner
updateLivePrices($conn);

require_once 'includes/header.php';
require_once 'includes/footer.php';

displayHeader("Home");

/* -------- market status banner -------- */
$isOpen = isMarketOpen($conn);
?>
<div style="padding:0.8rem; text-align:center;
            background-color:<?= $isOpen ? '#d4edda' : '#f8d7da' ?>;
            color:<?= $isOpen ? '#155724' : '#721c24' ?>;">
    <strong>Market Status:</strong>
    <?= $isOpen ? 'OPEN' : 'CLOSED' ?>
</div>
<?php
/* -------------------------------------- */
?>

<div class="content-container">

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="form-container home-box">
            <h2>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
            <a href="portfolio.php" class="btn btn-primary">View Portfolio</a>
            <a href="transact.php"  class="btn btn-primary">Make a Trade</a>
        </div>
    <?php else: ?>
        <div class="form-container home-box">
            <p>Our mission is to provide a realistic stock‑trading simulator for learning and practice.</p>
            <a href="login.php"    class="btn btn-primary">Login</a>
            <a href="register.php" class="btn btn-primary">Create Account</a>
        </div>
    <?php endif; ?>
</div>

<?php
// Fetch all available stocks
$stmt = $conn->query("
    SELECT ticker,
           price,
           volume,
           day_open_price,
           day_high_price,
           day_low_price
    FROM stocks
    ORDER BY ticker
");
$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="form-container">
    <h2>Available Stocks</h2>
    <table>
        <thead>
            <tr>
                <th>Ticker</th>
                <th>Price</th>
                <th>Volume</th>
                <th>Market Cap</th>
                <th>Open Price</th>
                <th>Day High</th>
                <th>Day Low</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($stocks as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['ticker']) ?></td>
                <td>$<?= number_format($s['price'], 2) ?></td>
                <td><?= number_format($s['volume']) ?></td>
                <td>$<?= number_format($s['price'] * $s['volume'], 2) ?></td>
                <td>$<?= number_format($s['day_open_price'], 2) ?></td>
                <td>$<?= number_format($s['day_high_price'], 2) ?></td>
                <td>$<?= number_format($s['day_low_price'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>


<?php displayFooter(); ?>

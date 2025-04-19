<?php
function displayHeader($pageTitle) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BySel - <?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon"
      href="assets/favicon.ico?v=<?= filemtime('assets/favicon.ico') ?>"
      type="image/x-icon">
	<link rel="stylesheet"
      href="assets/styles.css?v=<?= filemtime('assets/styles.css') ?>">
</head>
<body>
    <header>
        <img src="assets/newbanner.jpg" class="header-banner" alt="Bear and Bull">
        
        <nav class="horizontalNavigation">
            <ul>
                <li><a href="index.php">Home</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="portfolio.php">Portfolio</a></li>
					<li><a href="orders.php">Orders</a></li>
                    <li><a href="transact.php">Transact</a></li>
                    <li><a href="history.php">History</a></li>
                    <li><a href="cash.php">Cash</a></li>
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Create Account</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
<?php
}
?>
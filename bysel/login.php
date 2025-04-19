<?php
// login.php
require_once 'includes/db_connect.php';   // starts session & DB
require_once 'includes/header.php';
require_once 'includes/footer.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

displayHeader("Login");
?>

<div class="form-container">
    <h2>Login</h2>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
        <div class="success-message">Account created successfully! You can log in now.</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form action="handlers/auth_process.php" method="post">
        <!-- CSRF protection -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn-primary">Login</button>
    </form>
</div>

<?php displayFooter(); ?>
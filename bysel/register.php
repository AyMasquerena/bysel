<?php
// register.php
require_once 'includes/db_connect.php';   // starts session & DB
require_once 'includes/header.php';
require_once 'includes/footer.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

displayHeader("Register");
?>

<div class="form-container">
    <h2>Create Account</h2>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'username_taken'): ?>
        <div class="error-message">That username is already taken. Please choose another.</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form action="handlers/auth_process.php" method="post">
        <!-- Tell the handler this is a registration request -->
        <input type="hidden" name="action" value="register">
        <!-- CSRF protection -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn-primary">Register</button>
    </form>
</div>

<?php displayFooter(); ?>


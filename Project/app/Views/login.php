<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - Crowd Pulse</title>
    <link rel="stylesheet" href="/css/layouts/layout.css">
</head>

<body>
    <div class="common-container login">
        <h2>Login</h2>
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="/login">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($old['username'] ?? '') ?>" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button class="btn btn-primary" type="submit">Login</button>
        </form>
        <p>Don't have an account? <a class="link" href="/register">Register here</a>.</p>
    </div>
</body>

</html>
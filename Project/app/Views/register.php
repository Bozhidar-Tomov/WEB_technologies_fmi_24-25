<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <?php if (isset($viewStyle)): ?>
        <link rel="stylesheet" href="<?= $viewStyle ?>">
    <?php endif; ?>
</head>

<body>
    <div class="register-container">
        <h1>Register for CrowdPulse</h1>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="/register" method="post">
            <label>
                Username:
                <input type="text" name="username" value="<?= $old['username'] ?? '' ?>" required>
            </label>

            <label>
                Password:
                <input type="password" name="password" required>
            </label>

            <label>
                Gender:
                <select name="gender" required>
                    <option value="">Select</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </label>

            <label>
                Role:
                <select name="role" required>
                    <option value="participant">Active Participant</option>
                    <option value="viewer">Passive Viewer</option>
                    <option value="leader">Group Leader</option>
                </select>
            </label>

            <label>
                Tags (comma-separated, e.g. VIP,Guest,Fan):
                <input type="text" name="tags">
            </label>

            <button type="submit">Register</button>
        </form>
    </div>
</body>

</html>
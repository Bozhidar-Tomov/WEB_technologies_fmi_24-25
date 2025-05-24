<div class="common-container register">
    <h1>Register</h1>

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
        <label for="username">
            Username:
            <input type="text" name="username" id="username" autocomplete="username" value="<?= $old['username'] ?? '' ?>" required>
        </label>

        <label for="password">
            Password:
            <input type="password" name="password" id="password" autocomplete="new-password" required>
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

        <label for="tags">
            Tags (comma-separated, e.g. VIP,Guest,Fan):
            <input type="text" name="tags" id="tags" autocomplete="off">
        </label>

        <button class=" btn btn-primary" type="submit">Register</button>
    </form>
    <p>Already have an account? <a class="link" href="/login">Login here.</a></p>
</div>
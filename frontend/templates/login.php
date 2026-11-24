<h1>Please Log In</h1>
<?php if ($error !== null): ?>
<p class="error" role="alert"><?= $e($error) ?></p>
<?php endif; ?>
<form method="post" action="/login">
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <label for="who">User name</label>
    <input id="who" name="who" maxlength="64" autocomplete="username">
    <label for="pass">Password</label>
    <input id="pass" name="pass" type="password" autocomplete="current-password">
    <button type="submit">Log In</button>
    <button type="submit" name="cancel" value="1">Cancel</button>
</form>

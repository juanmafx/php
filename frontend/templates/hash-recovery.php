<h1>MD5 PIN Recovery</h1>
<p>Searches all 10,000 four-digit PINs for a matching MD5 digest. It demonstrates why fast, unsalted hashes are unsuitable for credentials.</p>
<?php if ($error !== null): ?>
<p class="error" role="alert"><?= $e($error) ?></p>
<?php endif; ?>
<?php if ($result !== null): ?>
<p class="<?= $result->pin !== null ? 'success' : 'error' ?>" role="status">
    Original PIN: <strong><?= $e($result->pin ?? 'Not found') ?></strong>
    (<?= $e($result->attempts) ?> attempts, <?= $e($elapsedMs) ?> ms)
</p>
<?php endif; ?>
<form method="get" action="/hash-recovery">
    <label for="md5">MD5 digest</label>
    <input id="md5" name="md5" size="40" maxlength="32" autocomplete="off" value="<?= $e($digest) ?>">
    <button type="submit">Recover PIN</button>
</form>

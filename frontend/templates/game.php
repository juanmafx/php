<h1>Rock Paper Scissors</h1>
<p>Welcome: <strong><?= $e($player) ?></strong></p>
<form method="post" action="/game">
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <label for="human">Your move</label>
    <select id="human" name="human">
        <option value="0">Rock</option>
        <option value="1">Paper</option>
        <option value="2">Scissors</option>
        <option value="matrix">Show all outcomes</option>
    </select>
    <button type="submit">Play</button>
</form>
<form method="post" action="/logout" class="inline">
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <button type="submit">Log Out</button>
</form>
<?php if (isset($round['error'])): ?>
<p class="error" role="alert"><?= $e($round['error']) ?></p>
<?php elseif (isset($round['matrix'])): ?>
<pre><?php foreach ($round['matrix'] as $row): ?>Human=<?= $e($row['human']->name) ?> Computer=<?= $e($row['computer']->name) ?> Result=<?= $e($row['outcome']->value) ?>

<?php endforeach; ?></pre>
<?php elseif (isset($round['outcome'])): ?>
<pre>Your play=<?= $e($round['human']->name) ?> Computer play=<?= $e($round['computer']->name) ?> Result=<?= $e($round['outcome']->value) ?></pre>
<?php endif; ?>

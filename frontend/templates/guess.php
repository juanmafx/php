<h1>Guessing Game</h1>
<?php if ($guess !== null): ?>
<p>Your guess was: <strong><?= $e($guess) ?></strong></p>
<?php endif; ?>
<p class="<?= $isCorrect ? 'success' : 'error' ?>" role="status"><?= $e($message) ?></p>
<form method="get" action="/guess">
    <label for="guess">Your guess</label>
    <input id="guess" name="guess" inputmode="decimal" maxlength="32" value="<?= $e($guess ?? '') ?>">
    <button type="submit">Check guess</button>
</form>

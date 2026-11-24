<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($title) ?></title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header>
    <nav aria-label="Main">
        <a href="/">Home</a>
        <a href="/guess">Guessing Game</a>
        <a href="/hash-recovery">MD5 PIN Recovery</a>
        <a href="/game">Rock Paper Scissors</a>
    </nav>
</header>
<main>
<?= $content ?>
</main>
<footer>
    Derived from exercises in
    <a href="https://www.wa4e.com/" rel="noopener noreferrer">Web Applications for Everybody</a>
    by Charles Severance, used under the BSD-2-Clause license.
</footer>
</body>
</html>

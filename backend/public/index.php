<?php

declare(strict_types=1);

use PhpLabs\Domain\RockPaperScissors\RandomMoveSource;
use PhpLabs\Http\Application;
use PhpLabs\Http\Request;
use PhpLabs\Security\CredentialVerifier;
use PhpLabs\Security\LoginThrottle;
use PhpLabs\Security\NativeSessionStore;
use PhpLabs\View\TemplateRenderer;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

$request = Request::fromGlobals();
$passwordHash = getenv('PHP_LABS_PASSWORD_HASH');
$throttleDirectory = getenv('PHP_LABS_THROTTLE_DIR');

$application = new Application(
    new NativeSessionStore($request->https),
    new TemplateRenderer($root . '/frontend/templates'),
    new CredentialVerifier(is_string($passwordHash) ? $passwordHash : null),
    new LoginThrottle(is_string($throttleDirectory) && $throttleDirectory !== '' ? $throttleDirectory : sys_get_temp_dir() . '/php-labs-throttle'),
    new RandomMoveSource(),
    $root . '/frontend/assets/app.css',
);

$application->handle($request)->send();

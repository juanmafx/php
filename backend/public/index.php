<?php

declare(strict_types=1);

use PhpLabs\Domain\RockPaperScissors\RandomMoveSource;
use PhpLabs\Http\Application;
use PhpLabs\Http\Request;
use PhpLabs\Http\Response;
use PhpLabs\Security\CredentialVerifier;
use PhpLabs\Security\LoginThrottle;
use PhpLabs\Security\NativeSessionStore;
use PhpLabs\Security\SecurityHeaders;
use PhpLabs\View\TemplateRenderer;

ini_set('display_errors', '0');
ini_set('log_errors', '1');
header_remove('X-Powered-By');

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

$request = Request::fromGlobals();
$passwordHash = getenv('PHP_LABS_PASSWORD_HASH');
$throttleDirectory = getenv('PHP_LABS_THROTTLE_DIR');

try {
    $application = new Application(
        new NativeSessionStore($request->https),
        new TemplateRenderer($root . '/frontend/templates'),
        new CredentialVerifier(is_string($passwordHash) ? $passwordHash : null),
        new LoginThrottle(is_string($throttleDirectory) && $throttleDirectory !== '' ? $throttleDirectory : sys_get_temp_dir() . '/php-labs-throttle'),
        new RandomMoveSource(),
        $root . '/frontend/assets/app.css',
    );
    $response = $application->handle($request);
} catch (Throwable $exception) {
    error_log(sprintf('%s at %s:%d', $exception::class, $exception->getFile(), $exception->getLine()));
    $response = Response::text('Internal Server Error', 500)->withHeaders(SecurityHeaders::for($request->https));
}

$response->send();

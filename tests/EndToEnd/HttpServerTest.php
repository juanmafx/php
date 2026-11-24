<?php

declare(strict_types=1);

namespace PhpLabs\Tests\EndToEnd;

use PHPUnit\Framework\TestCase;
use PhpLabs\Tests\Support\HttpResult;
use RuntimeException;

final class HttpServerTest extends TestCase
{
    private const string PASSWORD = 'end-to-end-test-passphrase';

    private static mixed $server = null;
    private static int $port = 0;
    private static string $throttleDirectory = '';
    private array $cookies = [];

    public static function setUpBeforeClass(): void
    {
        $probe = stream_socket_server('tcp://127.0.0.1:0');
        if ($probe === false) {
            throw new RuntimeException('Unable to allocate a port.');
        }
        self::$port = (int) substr(strrchr((string) stream_socket_get_name($probe, false), ':') ?: ':0', 1);
        fclose($probe);

        $root = dirname(__DIR__, 2);
        self::$throttleDirectory = sys_get_temp_dir() . '/php-labs-e2e-' . bin2hex(random_bytes(6));
        $environment = getenv() + [];
        $environment['PHP_LABS_PASSWORD_HASH'] = password_hash(self::PASSWORD, PASSWORD_BCRYPT);
        $environment['PHP_LABS_THROTTLE_DIR'] = self::$throttleDirectory;

        self::$server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . self::$port, '-t', $root . '/backend/public', $root . '/backend/public/index.php'],
            [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
            $root,
            $environment,
        );

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $connection = @fsockopen('127.0.0.1', self::$port, $code, $message, 0.1);
            if ($connection !== false) {
                fclose($connection);

                return;
            }
            usleep(50_000);
        }

        throw new RuntimeException('The test server did not start.');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        array_map(unlink(...), glob(self::$throttleDirectory . '/*') ?: []);
        if (is_dir(self::$throttleDirectory)) {
            rmdir(self::$throttleDirectory);
        }
    }

    private function request(string $method, string $path, array $form = []): HttpResult
    {
        $headers = ['Content-Type: application/x-www-form-urlencoded'];
        if ($this->cookies !== []) {
            $pairs = [];
            foreach ($this->cookies as $name => $value) {
                if (is_string($value)) {
                    $pairs[] = $name . '=' . $value;
                }
            }
            $headers[] = 'Cookie: ' . implode('; ', $pairs);
        }

        $context = stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => http_build_query($form),
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => 10,
        ]]);
        $body = (string) file_get_contents('http://127.0.0.1:' . self::$port . $path, false, $context);
        $responseHeaders = http_get_last_response_headers() ?? [];
        http_clear_last_response_headers();

        foreach ($responseHeaders as $header) {
            if (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $header, $match) === 1) {
                if ($match[2] === '' || $match[2] === 'deleted') {
                    unset($this->cookies[$match[1]]);
                } else {
                    $this->cookies[$match[1]] = $match[2];
                }
            }
        }

        return new HttpResult((int) substr($responseHeaders[0] ?? '', 9, 3), $responseHeaders, $body);
    }

    private function csrfToken(string $body): string
    {
        self::assertSame(1, preg_match('/name="csrf_token" value="([a-f0-9]{64})"/', $body, $match));

        return $match[1] ?? '';
    }

    public function testPublicPagesDoNotStartASession(): void
    {
        $response = $this->request('GET', '/guess?guess=42');

        self::assertSame(200, $response->status);
        self::assertStringContainsString('you are right', $response->body);
        self::assertSame([], $this->cookies);
    }

    public function testSessionCookieIsHardened(): void
    {
        $response = $this->request('GET', '/login');
        $cookie = $response->setCookieHeader('php_labs_session');

        self::assertStringContainsString('HttpOnly', $cookie);
        self::assertStringContainsString('SameSite=Strict', $cookie);
    }

    public function testForgedSessionIdIsNotAdopted(): void
    {
        $forged = str_repeat('a', 32);
        $this->cookies = ['php_labs_session' => $forged];
        $this->request('GET', '/login');

        self::assertNotSame($forged, $this->cookies['php_labs_session']);
    }

    public function testFullLoginPlayLogoutFlow(): void
    {
        $login = $this->request('GET', '/login');
        $anonymousCookie = $this->cookies['php_labs_session'];
        $redirect = $this->request('POST', '/login', ['who' => 'Ada', 'pass' => self::PASSWORD, 'csrf_token' => $this->csrfToken($login->body)]);

        self::assertSame(303, $redirect->status);
        self::assertContains('Location: /game', $redirect->headers);
        self::assertNotSame($anonymousCookie, $this->cookies['php_labs_session']);

        $game = $this->request('GET', '/game');
        self::assertSame(200, $game->status);
        self::assertStringContainsString('Ada', $game->body);

        $played = $this->request('POST', '/game', ['human' => '0', 'csrf_token' => $this->csrfToken($game->body)]);
        self::assertMatchesRegularExpression('/Your play=Rock Computer play=(Rock|Paper|Scissors) Result=(Tie|You Win|You Lose)/', $played->body);

        $loggedOut = $this->request('POST', '/logout', ['csrf_token' => $this->csrfToken($played->body)]);
        self::assertSame(303, $loggedOut->status);
        self::assertContains('Location: /login', $this->request('GET', '/game')->headers);
    }

    public function testStylesheetSatisfiesContentSecurityPolicy(): void
    {
        $response = $this->request('GET', '/assets/app.css');

        self::assertSame(200, $response->status);
        self::assertContains('Content-Type: text/css; charset=utf-8', $response->headers);
    }

    public function testSourceFilesAreNotServed(): void
    {
        foreach (['/composer.json', '/../../composer.json', '/backend/src/Http/Application.php'] as $path) {
            self::assertSame(404, $this->request('GET', $path)->status);
        }
    }
}

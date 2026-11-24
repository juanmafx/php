<?php

declare(strict_types=1);

namespace PhpLabs\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PhpLabs\Domain\RockPaperScissors\Move;
use PhpLabs\Http\Application;
use PhpLabs\Http\Request;
use PhpLabs\Http\Response;
use PhpLabs\Security\CredentialVerifier;
use PhpLabs\Security\CsrfGuard;
use PhpLabs\Security\LoginThrottle;
use PhpLabs\Tests\Support\FixedMoveSource;
use PhpLabs\Tests\Support\InMemorySessionStore;
use PhpLabs\View\TemplateRenderer;

final class ApplicationTest extends TestCase
{
    private const string PASSWORD = 'integration-test-passphrase';

    private InMemorySessionStore $session;
    private Application $application;
    private string $throttleDirectory;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 2);
        $this->session = new InMemorySessionStore();
        $this->throttleDirectory = sys_get_temp_dir() . '/php-labs-app-' . bin2hex(random_bytes(6));
        $this->application = new Application(
            $this->session,
            new TemplateRenderer($root . '/frontend/templates'),
            new CredentialVerifier(password_hash(self::PASSWORD, PASSWORD_BCRYPT)),
            new LoginThrottle($this->throttleDirectory),
            new FixedMoveSource(Move::Rock),
            $root . '/frontend/assets/app.css',
        );
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->throttleDirectory . '/*') ?: []);
        rmdir($this->throttleDirectory);
    }

    private function get(string $path, array $query = []): Response
    {
        return $this->application->handle(new Request('GET', $path, $query));
    }

    private function post(string $path, array $form): Response
    {
        $form['csrf_token'] = (new CsrfGuard($this->session))->token();

        return $this->application->handle(new Request('POST', $path, [], $form));
    }

    private function logIn(): Response
    {
        return $this->post('/login', ['who' => 'Ada', 'pass' => self::PASSWORD]);
    }

    private function headerValue(array $headers, string $name): string
    {
        $value = $headers[$name] ?? '';

        return is_string($value) ? $value : '';
    }

    public function testEveryResponseCarriesSecurityHeaders(): void
    {
        foreach (['/', '/guess', '/hash-recovery', '/login', '/missing'] as $path) {
            $headers = $this->get($path)->headers;
            self::assertStringContainsString("default-src 'none'", $this->headerValue($headers, 'Content-Security-Policy'));
            self::assertSame('nosniff', $headers['X-Content-Type-Options']);
            self::assertArrayNotHasKey('Strict-Transport-Security', $headers);
        }
    }

    public function testHstsIsSentOnlyOverHttps(): void
    {
        $response = $this->application->handle(new Request('GET', '/', https: true));

        self::assertArrayHasKey('Strict-Transport-Security', $response->headers);
    }

    public function testGuessEscapesReflectedInput(): void
    {
        $body = $this->get('/guess', ['guess' => '"><script>alert(1)</script>'])->body;

        self::assertStringNotContainsString('<script>', $body);
        self::assertStringContainsString('&lt;script&gt;', $body);
        self::assertStringContainsString('not a number', $body);
    }

    public function testGuessIgnoresArrayInput(): void
    {
        $response = $this->get('/guess', ['guess' => ['42']]);

        self::assertSame(200, $response->status);
        self::assertStringContainsString('Missing guess parameter', $response->body);
    }

    public function testHashRecoveryFindsPin(): void
    {
        $response = $this->get('/hash-recovery', ['md5' => md5('4321')]);

        self::assertSame(200, $response->status);
        self::assertStringContainsString('<strong>4321</strong>', $response->body);
    }

    public function testHashRecoveryRejectsInvalidDigestAndEscapesIt(): void
    {
        $response = $this->get('/hash-recovery', ['md5' => '<img src=x>']);

        self::assertSame(422, $response->status);
        self::assertStringNotContainsString('<img', $response->body);
    }

    public function testUnknownRouteAndWrongMethod(): void
    {
        self::assertSame(404, $this->get('/missing')->status);

        $response = $this->application->handle(new Request('DELETE', '/login'));
        self::assertSame(405, $response->status);
        self::assertSame('GET, POST', $response->headers['Allow']);
    }

    public function testPostWithoutCsrfTokenIsRejected(): void
    {
        foreach (['/login', '/game', '/logout'] as $path) {
            $response = $this->application->handle(new Request('POST', $path, [], ['who' => 'Ada', 'pass' => self::PASSWORD]));
            self::assertSame(403, $response->status);
        }
        self::assertNull($this->session->get('player_name'));
    }

    public function testGameRequiresLogin(): void
    {
        self::assertSame('/login', $this->get('/game')->headers['Location']);
        self::assertSame('/login', $this->post('/game', ['human' => '1'])->headers['Location']);
    }

    public function testSuccessfulLoginRegeneratesSessionAndRotatesCsrf(): void
    {
        $guard = new CsrfGuard($this->session);
        $tokenBefore = $guard->token();
        $response = $this->logIn();

        self::assertSame(303, $response->status);
        self::assertSame('/game', $response->headers['Location']);
        self::assertSame(1, $this->session->regenerations);
        self::assertSame('Ada', $this->session->get('player_name'));
        self::assertFalse($guard->isValid($tokenBefore));
    }

    public function testWrongPasswordIsGenericAndThrottled(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $response = $this->post('/login', ['who' => 'Ada', 'pass' => 'wrong']);
            self::assertSame(401, $response->status);
            self::assertStringContainsString('Incorrect credentials.', $response->body);
        }

        self::assertSame(429, $this->logIn()->status);
    }

    public function testLoginRequiresBothFields(): void
    {
        self::assertSame(422, $this->post('/login', ['who' => '', 'pass' => ''])->status);
    }

    public function testLoginRejectsOversizedName(): void
    {
        self::assertSame(422, $this->post('/login', ['who' => str_repeat('a', 65), 'pass' => self::PASSWORD])->status);
    }

    public function testLoginDisabledWithoutConfiguredHash(): void
    {
        $root = dirname(__DIR__, 2);
        $session = new InMemorySessionStore();
        $application = new Application(
            $session,
            new TemplateRenderer($root . '/frontend/templates'),
            new CredentialVerifier(null),
            new LoginThrottle($this->throttleDirectory),
            new FixedMoveSource(Move::Rock),
            $root . '/frontend/assets/app.css',
        );
        $form = ['who' => 'Ada', 'pass' => 'anything', 'csrf_token' => (new CsrfGuard($session))->token()];

        self::assertSame(503, $application->handle(new Request('POST', '/login', [], $form))->status);
    }

    public function testPlayEscapesPlayerNameAndReportsResult(): void
    {
        $this->post('/login', ['who' => '<b>Ada</b>', 'pass' => self::PASSWORD]);
        $body = $this->post('/game', ['human' => '1'])->body;

        self::assertStringNotContainsString('<b>Ada</b>', $body);
        self::assertStringContainsString('Your play=Paper Computer play=Rock Result=You Win', $body);
    }

    public function testMatrixAndInvalidMove(): void
    {
        $this->logIn();

        self::assertSame(9, substr_count($this->post('/game', ['human' => 'matrix'])->body, 'Human='));
        self::assertSame(422, $this->post('/game', ['human' => '9'])->status);
    }

    public function testLogoutClearsPlayer(): void
    {
        $this->logIn();
        $response = $this->post('/logout', []);

        self::assertSame('/', $response->headers['Location']);
        self::assertNull($this->session->get('player_name'));
    }

    public function testStylesheetIsServedWithCssType(): void
    {
        $response = $this->get('/assets/app.css');

        self::assertSame('text/css; charset=utf-8', $response->headers['Content-Type']);
        self::assertStringContainsString('color-scheme', $response->body);
    }
}

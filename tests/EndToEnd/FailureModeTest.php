<?php

declare(strict_types=1);

namespace PhpLabs\Tests\EndToEnd;

use PHPUnit\Framework\TestCase;

final class FailureModeTest extends TestCase
{
    public function testInternalErrorsDoNotDiscloseDetails(): void
    {
        $probe = stream_socket_server('tcp://127.0.0.1:0');
        self::assertIsResource($probe);
        $port = (int) substr(strrchr((string) stream_socket_get_name($probe, false), ':') ?: ':0', 1);
        fclose($probe);

        $root = dirname(__DIR__, 2);
        $environment = getenv() + [];
        $environment['PHP_LABS_THROTTLE_DIR'] = '/dev/null/unusable';
        $server = proc_open(
            [PHP_BINARY, '-d', 'display_errors=1', '-S', '127.0.0.1:' . $port, '-t', $root . '/backend/public', $root . '/backend/public/index.php'],
            [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
            $root,
            $environment,
        );
        self::assertIsResource($server);

        try {
            for ($attempt = 0; $attempt < 100; $attempt++) {
                $connection = @fsockopen('127.0.0.1', $port, $code, $message, 0.1);
                if ($connection !== false) {
                    fclose($connection);
                    break;
                }
                usleep(50_000);
            }
            $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 10]]);
            $body = (string) file_get_contents('http://127.0.0.1:' . $port . '/', false, $context);
            $headers = http_get_last_response_headers() ?? [];
            http_clear_last_response_headers();
        } finally {
            proc_terminate($server);
            proc_close($server);
        }

        self::assertStringContainsString('500', $headers[0] ?? '');
        self::assertSame('Internal Server Error', $body);
        self::assertNotEmpty(preg_grep('/^Content-Security-Policy:/i', $headers));
        self::assertEmpty(preg_grep('/^X-Powered-By:/i', $headers));
    }
}

<?php

declare(strict_types=1);

namespace PhpLabs\Http\Controller;

use PhpLabs\Domain\HashRecovery\Md5PinRecovery;
use PhpLabs\Http\Request;
use PhpLabs\Http\Response;
use PhpLabs\View\TemplateRenderer;

final readonly class HashRecoveryController
{
    public function __construct(private TemplateRenderer $view, private Md5PinRecovery $recovery)
    {
    }

    public function show(Request $request): Response
    {
        $digest = $request->query('md5');
        $data = ['digest' => $digest ?? '', 'error' => null, 'result' => null, 'elapsedMs' => null];
        $status = 200;

        if ($digest !== null && !Md5PinRecovery::isValidDigest($digest)) {
            $data['error'] = 'Enter a 32-character hexadecimal MD5 digest.';
            $status = 422;
        } elseif ($digest !== null) {
            $startedAt = hrtime(true);
            $data['result'] = $this->recovery->recover($digest);
            $data['elapsedMs'] = round((hrtime(true) - $startedAt) / 1_000_000, 2);
        }

        return Response::html($this->view->page('hash-recovery', 'MD5 PIN Recovery', $data), $status);
    }
}

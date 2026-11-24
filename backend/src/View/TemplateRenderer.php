<?php

declare(strict_types=1);

namespace PhpLabs\View;

use InvalidArgumentException;

final readonly class TemplateRenderer
{
    public function __construct(private string $templateDirectory)
    {
    }

    public function page(string $template, string $title, array $data = []): string
    {
        $content = $this->render($template, $data);

        return $this->render('layout', ['title' => $title, 'content' => $content]);
    }

    private function render(string $template, array $data): string
    {
        if (preg_match('/\A[a-z-]+\z/', $template) !== 1) {
            throw new InvalidArgumentException('Invalid template name.');
        }

        $path = $this->templateDirectory . '/' . $template . '.php';
        $escape = static fn (string|int|float $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        ob_start();
        try {
            (static function (string $templatePath, array $templateData, callable $e): void {
                extract($templateData, EXTR_SKIP);
                require $templatePath;
            })($path, $data, $escape);

            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}

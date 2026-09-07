<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Http\Response;
use PHPUnit\Framework\Assert;
use System\Models\File;

/**
 * Records what a project renders without touching templates, Twig or dompdf.
 */
class PDFFake extends PDFWrapper
{
    /** @var array<int, array{code: string, kind: string, data: array<string, mixed>, layout: string|null, locale: string|null}> */
    protected array $renders = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function loadTemplate(string $code, array $data = [], ?string $encoding = null, ?string $layout = null, ?string $locale = null): self
    {
        $this->renders[] = ['code' => $code, 'kind' => 'template', 'data' => $data, 'layout' => $layout, 'locale' => $locale];

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function loadLayout(string $code, array $data = [], ?string $encoding = null, ?string $locale = null): self
    {
        $this->renders[] = ['code' => $code, 'kind' => 'layout', 'data' => $data, 'layout' => null, 'locale' => $locale];

        return $this;
    }

    public function loadHTML(string $string, ?string $encoding = null): self
    {
        return $this;
    }

    public function render(): void
    {
    }

    /**
     * @param  array<string, int>  $options
     */
    public function output(array $options = []): string
    {
        return '';
    }

    public function save(string $filename, ?string $disk = null): self
    {
        return $this;
    }

    public function download(string $filename = 'document.pdf'): Response
    {
        return $this->emptyResponse($filename, 'attachment');
    }

    public function stream(string $filename = 'document.pdf'): Response
    {
        return $this->emptyResponse($filename, 'inline');
    }

    public function toFile(string $filename = 'document.pdf', bool $public = true): File
    {
        $file = new File;
        $file->setAttribute('file_name', $filename);
        $file->setAttribute('content_type', 'application/pdf');
        $file->setAttribute('is_public', $public);

        return $file;
    }

    public function encrypt(string $password, string $ownerPassword = '', array $permissions = []): self
    {
        return $this;
    }

    public function assertRendered(string $code, ?callable $callback = null): void
    {
        $matches = array_filter($this->renders, fn (array $render): bool => $render['code'] === $code && ($callback === null || $callback($render['data'], $render)));

        Assert::assertNotEmpty($matches, "The PDF [{$code}] was not rendered" . ($callback ? ' with the expected data.' : '.'));
    }

    public function assertNotRendered(string $code): void
    {
        Assert::assertEmpty(
            array_filter($this->renders, fn (array $render): bool => $render['code'] === $code),
            "The PDF [{$code}] was rendered.",
        );
    }

    public function assertNothingRendered(): void
    {
        Assert::assertEmpty($this->renders, 'PDFs were rendered: ' . implode(', ', array_column($this->renders, 'code')));
    }

    public function assertRenderedCount(int $count): void
    {
        Assert::assertCount($count, $this->renders);
    }

    /**
     * @return array<int, array{code: string, kind: string, data: array<string, mixed>, layout: string|null, locale: string|null}>
     */
    public function rendered(): array
    {
        return $this->renders;
    }

    protected function emptyResponse(string $filename, string $disposition): Response
    {
        return new Response('', 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }
}

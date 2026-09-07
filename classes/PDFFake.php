<?php

namespace Renatio\DynamicPDF\Classes;

use Dompdf\Dompdf;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Response;
use Illuminate\Support\Testing\Fakes\Fake;
use PHPUnit\Framework\Assert;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Symfony\Component\HttpFoundation\HeaderUtils;
use System\Models\File;

/**
 * Records what a project renders without touching templates, Twig, dompdf, the database
 * or the filesystem.
 */
class PDFFake extends PDFWrapper implements Fake
{
    /** @var array<int, array{code: string, kind: string, data: array<string, mixed>, layout: string|null, locale: string|null}> */
    protected array $renders = [];

    public function __construct(Dompdf $dompdf, ConfigRepository $config, Filesystem $files, ViewFactory $view)
    {
        $this->dompdf = $dompdf;
        $this->config = $config;
        $this->files = $files;
        $this->view = $view;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function loadTemplate(string $code, array $data = [], ?string $encoding = null, ?string $layout = null, ?string $locale = null): self
    {
        return $this->record('template', $code, $data, $layout, $locale);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function loadLayout(string $code, array $data = [], ?string $encoding = null, ?string $locale = null): self
    {
        return $this->record('layout', $code, $data, null, $locale);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $mergeData
     */
    public function loadView(string $view, array $data = [], array $mergeData = [], ?string $encoding = null): self
    {
        return $this->record('view', $view, array_merge($mergeData, $data), null, null);
    }

    public function loadFile(string $file): self
    {
        return $this->record('file', $file, [], null, null);
    }

    public function loadHTML(string $string, ?string $encoding = null): self
    {
        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parseTemplate(Template $template, array $data = []): string
    {
        $this->record('template', (string) $template->code, $data, null, null);

        return '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parseLayout(Layout $layout, array $data = []): string
    {
        $this->record('layout', (string) $layout->code, $data, null, null);

        return '';
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

    /**
     * Complete enough to be attached and saved without a byte written anywhere.
     */
    public function toFile(string $filename = 'document.pdf', bool $public = true): File
    {
        $file = new File;
        $file->setAttribute('file_name', $filename);
        $file->setAttribute('disk_name', uniqid() . '.pdf');
        $file->setAttribute('content_type', 'application/pdf');
        $file->setAttribute('file_size', 0);
        $file->setAttribute('is_public', $public);

        return $file;
    }

    public function encrypt(string $password, string $ownerPassword = '', array $permissions = []): self
    {
        return $this;
    }

    /**
     * @param  array<string>  $pc
     */
    public function setEncryption(string $password, string $ownerpassword = '', array $pc = []): void
    {
    }

    /**
     * @param  array<string, string>  $info
     */
    public function addInfo(array $info): self
    {
        return $this;
    }

    public function allowRemoteApplicationAssets(): self
    {
        return $this;
    }

    public function allowSelfSignedCertificates(): self
    {
        return $this;
    }

    public function __call($method, $parameters)
    {
        return $this;
    }

    public function assertRendered(string $code, ?callable $callback = null): void
    {
        $matches = array_filter($this->renders, fn (array $render): bool => $render['code'] === $code && ($callback === null || $callback($render['data'], $render)));

        Assert::assertNotEmpty($matches, "The PDF [{$code}] was not rendered" . ($callback ? ' with the expected data.' : '.'));
    }

    public function assertRenderedTimes(string $code, int $times): void
    {
        Assert::assertCount($times, array_filter($this->renders, fn (array $render): bool => $render['code'] === $code), "The PDF [{$code}] was not rendered {$times} time(s).");
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

    /**
     * @return array<int, array{code: string, kind: string, data: array<string, mixed>, layout: string|null, locale: string|null}>
     */
    public function rendered(): array
    {
        return $this->renders;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function record(string $kind, string $code, array $data, ?string $layout, ?string $locale): self
    {
        $this->renders[] = ['code' => $code, 'kind' => $kind, 'data' => $data, 'layout' => $layout, 'locale' => $locale];

        return $this;
    }

    protected function emptyResponse(string $filename, string $disposition): Response
    {
        return new Response('', 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $filename, $this->fallbackName($filename)),
            'Content-Length' => 0,
        ]);
    }
}

<?php

namespace Renatio\DynamicPDF\Classes;

use Closure;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

class TemplateRenderer
{
    public const RESERVED_VARIABLES = ['content_html', 'css', 'background_img', 'locale'];

    public function __construct(protected TwigRenderer $twig)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function template(PDFWrapper $pdf, Template $template, array $data): string
    {
        return $this->renderWithEvents($pdf, $template, $data, function (array $data) use ($pdf, $template): string {
            $html = $this->twig->render($template->content_html, $data, (string) $template->code);

            if (! $template->layout) {
                return $html;
            }

            return $this->renderLayout(
                $pdf,
                $template->layout,
                array_merge(['content_html' => $html], $data),
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function layout(PDFWrapper $pdf, Layout $layout, array $data): string
    {
        return $this->renderWithEvents($pdf, $layout, $data, fn (array $data): string => $this->renderLayout($pdf, $layout, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  callable(array<string, mixed>): string  $render
     */
    protected function renderWithEvents(PDFWrapper $pdf, Template|Layout $model, array $data, callable $render): string
    {
        $data = array_merge($this->withoutReserved($this->registeredVariables()), $data);

        foreach (Event::fire(Events::BEFORE_RENDER, [$pdf, $model, $data]) ?? [] as $extra) {
            if (is_array($extra)) {
                $data = array_merge($data, $this->withoutReserved($extra));
            }
        }

        $html = PDFAsset::whileRendering($pdf, fn (): string => $render($data));

        return $this->afterRender([$pdf, $model, $html]);
    }

    /**
     * Calls the listeners one by one so each receives the HTML returned by the previous one. Event::fake() only
     * records the event.
     *
     * @param  array{PDFWrapper, Template|Layout, string}  $payload
     */
    protected function afterRender(array $payload): string
    {
        if (! Event::getFacadeRoot() instanceof Dispatcher) {
            Event::dispatch(Events::AFTER_RENDER, $payload);

            return $payload[2];
        }

        foreach (Event::getListeners(Events::AFTER_RENDER) as $listener) {
            $replacement = $listener(Events::AFTER_RENDER, $payload);

            if ($replacement === false) {
                break;
            }

            if (is_string($replacement)) {
                $payload[2] = $replacement;
            }
        }

        return $payload[2];
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    protected function withoutReserved(array $variables): array
    {
        return array_diff_key($variables, array_flip(self::RESERVED_VARIABLES));
    }

    /**
     * @return array<string, mixed>
     */
    protected function registeredVariables(): array
    {
        return array_map(
            fn (mixed $value): mixed => $value instanceof Closure ? $value() : $value,
            PDFManager::instance()->listRegisteredVariables(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function renderLayout(PDFWrapper $pdf, Layout $layout, array $data): string
    {
        return $this->twig->render($layout->content_html, array_merge([
            'background_img' => $this->backgroundImage($pdf, $layout),
            'css' => $layout->getCSS(),
        ], $data), (string) $layout->code);
    }

    protected function backgroundImage(PDFWrapper $pdf, Layout $layout): ?string
    {
        $image = $layout->background_img;

        if ($image === null) {
            return null;
        }

        if ($pdf->isForBrowser()) {
            return $image->getPath();
        }

        $path = $pdf->localPath($image);

        return $path !== '' && $pdf->insideChroot($path) ? $path : $image->getPath();
    }
}

<?php

namespace Renatio\DynamicPDF\Classes;

use Closure;
use Illuminate\Support\Facades\Event;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

class TemplateRenderer
{
    public function __construct(protected PDFWrapper $pdf, protected TwigRenderer $twig)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function template(Template $template, array $data): string
    {
        return $this->renderWithEvents($template, $data, function (array $data) use ($template): string {
            $html = $this->twig->render($template->content_html, $data, (string) $template->code);

            if (! $template->layout) {
                return $html;
            }

            return $this->renderLayout(
                $template->layout,
                array_merge(['content_html' => $html], $data),
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function layout(Layout $layout, array $data): string
    {
        return $this->renderWithEvents($layout, $data, fn (array $data): string => $this->renderLayout($layout, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  callable(array<string, mixed>): string  $render
     */
    protected function renderWithEvents(Template|Layout $model, array $data, callable $render): string
    {
        $data = array_merge($this->withoutReserved($this->registeredVariables()), $data);

        foreach (Event::fire(Events::BEFORE_RENDER, [$this->pdf, $model, $data]) ?? [] as $extra) {
            if (is_array($extra)) {
                $data = array_merge($data, $this->withoutReserved($extra));
            }
        }

        $html = $render($data);

        foreach (Event::fire(Events::AFTER_RENDER, [$this->pdf, $model, $html]) ?? [] as $replacement) {
            if (is_string($replacement)) {
                $html = $replacement;
            }
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    protected function withoutReserved(array $variables): array
    {
        return array_diff_key($variables, array_flip(PDFWrapper::RESERVED_VARIABLES));
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
    protected function renderLayout(Layout $layout, array $data): string
    {
        return $this->twig->render($layout->content_html, array_merge([
            'background_img' => $layout->background_img?->getPath(),
            'css' => $layout->getCSS(),
        ], $data), (string) $layout->code);
    }
}

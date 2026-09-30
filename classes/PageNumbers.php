<?php

namespace Renatio\DynamicPDF\Classes;

use Dompdf\Dompdf;
use InvalidArgumentException;

class PageNumbers
{
    public const POSITIONS = ['top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right'];

    /**
     * @param  array<int, float>  $color
     */
    public function __construct(
        protected string $text,
        protected string $position,
        protected float $size,
        protected ?string $font,
        protected float $margin,
        protected array $color,
    ) {
        if (! in_array($position, self::POSITIONS, true)) {
            throw new InvalidArgumentException("Unknown page numbers position [{$position}].");
        }

        if (count($color) !== 3 || array_filter($color, fn (float $c): bool => $c < 0 || $c > 1) !== []) {
            throw new InvalidArgumentException('Page numbers color must be three RGB components between 0 and 1.');
        }
    }

    public function stamp(Dompdf $dompdf): void
    {
        $canvas = $dompdf->getCanvas();
        $metrics = $dompdf->getFontMetrics();
        $font = $metrics->getFont($this->font);

        if ($font === null) {
            throw new InvalidArgumentException("Font [{$this->font}] is not available in the rendered document.");
        }

        $pages = (string) $canvas->get_page_count();
        // One page_text() call serves every page, so the widest text (the last page) sets the position.
        $sample = str_replace(['{PAGE_NUM}', '{PAGE_COUNT}'], [$pages, $pages], $this->text);
        $width = $metrics->getTextWidth($sample, $font, $this->size);
        $height = $metrics->getFontHeight($font, $this->size);
        [$vertical, $horizontal] = explode('-', $this->position);

        $x = match ($horizontal) {
            'left' => $this->margin,
            'center' => ($canvas->get_width() - $width) / 2,
            default => $canvas->get_width() - $this->margin - $width,
        };
        $y = $vertical === 'top' ? $this->margin : $canvas->get_height() - $this->margin - $height;

        $canvas->page_text($x, $y, $this->text, $font, $this->size, $this->color);
    }
}

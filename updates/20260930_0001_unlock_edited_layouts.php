<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use October\Rain\Database\Updates\Migration;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\PDFParser;
use Renatio\DynamicPDF\Plugin;
use System\Models\Parameter;

/**
 * Unlocks layouts edited before 8.1.0, which never cleared is_locked, before locked layouts start following their view.
 */
return new class extends Migration
{
    protected const LAYOUTS = 'renatio_dynamicpdf_pdf_layouts';

    protected const OWN_VIEWS = 'renatio.dynamicpdf::';

    protected const PRE_8_1_MISSING_NAME = '???';

    /**
     * sha256 of every renatio.dynamicpdf::pdf.layouts.* view in the Git history, see fingerprint().
     */
    protected const SHIPPED_FINGERPRINTS = [
        '445a3ce85022da614ea096324e6998dc4ab93224ddcb626b9779ce409060ff8b',
        '38e4d9c66aec8dde734eaa6e7194d528bbc477579a77bd4a7e24b8eafbdb0d63',
        '5a705d3c0329b518d71cbcf862db47e0bbb1ff5abe5ec999e38c77c2ff946a70',
        'f3b958f51b23de22b3cb7c0483706f52687d2a3bffdd7d4543c0069592f690c0',
        'e0e33a1a36a7545c17fa326fe8a539365d445fe45704c662910c459f12211b43',
        'f2a0f9b98ad76d68ad420bdd236c2b0b1d6251bc687091d0e703ef9f818d433a',
        'c5440c416b6c45173501ab1b5a33c514efe6d2ce0c81b53cd4bc450ff9a5fa6e',
    ];

    public function up()
    {
        try {
            $views = PDFManager::instance()->listRegisteredLayouts();
        } catch (Throwable $e) {
            Log::warning("Renatio.DynamicPDF could not list the registered layouts, only layouts matching a shipped view stay locked: {$e->getMessage()}");
            $views = [];
        }

        $rows = DB::table(self::LAYOUTS)
            ->where('is_locked', true)
            ->get(['id', 'code', 'name', 'content_html', 'content_css']);

        $edited = $rows->reject(fn (object $row): bool => $this->isUnedited($row, $views[$row->code] ?? null))->pluck('id');

        if ($edited->isNotEmpty()) {
            DB::table(self::LAYOUTS)->whereIn('id', $edited->all())->update(['is_locked' => false]);
        }

        Parameter::set(Plugin::LAYOUTS_FOLLOW_VIEWS_PARAMETER, 1);
    }

    public function down()
    {
        Parameter::set(Plugin::LAYOUTS_FOLLOW_VIEWS_PARAMETER, 0);
    }

    protected function isUnedited(object $row, ?string $view): bool
    {
        $fingerprint = $this->fingerprint($row->name, $row->content_html, $row->content_css);

        if (str_starts_with((string) $row->code, self::OWN_VIEWS) && in_array($fingerprint, self::SHIPPED_FINGERPRINTS, true)) {
            return true;
        }

        if ($view === null) {
            return false;
        }

        try {
            $sections = (new PDFParser)->parseView($view);
        } catch (Throwable) {
            return false;
        }

        $name = Arr::get($sections, 'settings.name');
        $storedNames = $name ? [$name] : [$row->code, self::PRE_8_1_MISSING_NAME];

        foreach ($storedNames as $storedName) {
            if ($fingerprint === $this->fingerprint($storedName, $sections['html'], $sections['css'])) {
                return true;
            }
        }

        return false;
    }

    protected function fingerprint(mixed $name, mixed $html, mixed $css): string
    {
        $normalize = fn (mixed $value): string => str_replace("\r\n", "\n", trim((string) $value));

        return hash('sha256', $normalize($name) . "\0" . $normalize($html) . "\0" . $normalize($css));
    }
};

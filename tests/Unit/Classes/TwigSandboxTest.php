<?php

use Carbon\CarbonImmutable;
use Cms\Classes\Theme;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use October\Rain\Database\Model;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\TwigRenderer;
use Renatio\DynamicPDF\Models\Template;
use System\Models\File;
use Twig\Environment;
use Twig\Error\Error as TwigError;
use Twig\Extension\SandboxExtension;
use Twig\Sandbox\SecurityError;
use Twig\TwigFilter;
use Twig\TwigFunction;

function extendSandboxedTwig(Closure $extend): void
{
    Event::listen('system.extendTwig', $extend);
    Event::listen('cms.extendTwig', $extend);

    $instances = (fn (): array => $this->instances)->call(app());

    foreach (array_keys($instances) as $key) {
        if (str_starts_with($key, 'renatio.dynamicpdf.twig.')) {
            app()->forgetInstance($key);
        }
    }
}

dataset('environments', [
    'CMS environment' => ['demo'],
    'system environment' => [null],
]);

describe('Twig sandbox', function () {
    afterEach(function () {
        Event::forget('cms.theme.getActiveTheme');
        Event::forget('cms.extendTwig');
        Event::forget('system.extendTwig');
        Theme::resetCache();
        app()->forgetInstance('twig.environment');
        DB::disableQueryLog();
    });

    it('refuses markup that reads secrets or calls PHP functions', function (?string $theme, string $markup, string $error) {
        $this->activateTheme($theme);

        expect(fn () => (new TwigRenderer)->render($markup, ['victim' => $this->createTemplate()], 'sandbox'))->toThrow(TwigError::class, $error);
    })->with('environments')->with([
        'config()' => ['{{ config("app.key") }}', 'is not allowed in PDF templates'],
        'env()' => ['{{ env("APP_KEY") }}', 'is not allowed in PDF templates'],
        'a PHP function name in a filter' => ['{{ {3: "ab"}|map("str_repeat")|join }}', 'must be a Closure in sandbox mode'],
        'a PHP function name in a collection' => ['{{ collect({3: "ab"}).map("str_repeat")|join }}', 'must be of type callable'],
        'a Str macro' => ['{{ str_macro("pdfSandboxProbe", "strrev") }}{{ str_pdfSandboxProbe("ab") }}', 'is not allowed in PDF templates'],
        'a Str macro through a filter' => ['{{ "pdfSandboxFilterProbe"|str_macro("strrev") }}', 'is not allowed in PDF templates'],
        'a Str macro with a mangled name' => ['{{ str_m_a_c_r_o("pdfSandboxMangledProbe", "strrev") }}', 'is not allowed in PDF templates'],
        'an Html macro' => ['{{ html_macro("pdfSandboxProbe", "strrev") }}', 'is not allowed in PDF templates'],
        'a Str factory callback' => ['{{ str_create_random_strings_using("strrev") }}{{ str_random(16) }}', 'is not allowed in PDF templates'],
        'a form with the CSRF token' => ['{{ form_open() }}', 'is not allowed in PDF templates'],
        'a static method through a date' => ['{{ victim.created_at.setTestNow("2001-01-01") }}', 'is not allowed in PDF templates'],
        'a PHP function name passed to a date' => ['{{ victim.created_at.roundUnit("day", 1, "php_uname") }}', 'is not allowed in PDF templates'],
        'named arguments' => ['{{ attribute(victim.created_at, "format", {format: "Y"}) }}', 'is not allowed in PDF templates'],
    ]);

    it('refuses CMS functions and tags with side effects', function (string $markup) {
        $this->activateTheme('demo');

        expect(fn () => (new TwigRenderer)->render($markup, [], 'sandbox'))->toThrow(TwigError::class, 'is not allowed in PDF templates');
    })->with([
        'response()' => ['{{ response("x") }}'],
        'redirect()' => ['{{ redirect("/") }}'],
        'abort()' => ['{{ abort(500) }}'],
        'ajaxHandler()' => ['{{ ajaxHandler("onSave") }}'],
        'flash()' => ['{{ flash()|length }}'],
        'dump()' => ['{{ dump() }}'],
        'd()' => ['{{ d(1) }}'],
        'dd()' => ['{{ dd(1) }}'],
        'the flash tag' => ['{% flash %}{{ message }}{% endflash %}'],
        'the cache tag' => ['{% cache "pdf" %}x{% endcache %}'],
    ]);

    it('allows no method calls or lazy loads on live models and queries from functions', function (?string $theme, string $markup) {
        $this->activateTheme($theme);
        $victim = $this->createTemplate(['title' => 'Original', 'layout_id' => $this->createLayout()->id])->fresh();
        $rows = Template::whereKey($victim->id)->get();
        extendSandboxedTwig(function (Environment $twig) use ($victim, $rows) {
            $twig->addFunction(new TwigFunction('live_model', fn () => $victim));
            $twig->addFunction(new TwigFunction('live_query', fn () => Template::query()));
            $twig->addFunction(new TwigFunction('live_base_query', fn () => Template::query()->toBase()));
            $twig->addFunction(new TwigFunction('live_rows', fn () => $rows));
        });
        DB::enableQueryLog();

        expect(fn () => (new TwigRenderer)->render($markup, [], 'sandbox'))->toThrow(SecurityError::class)
            ->and(DB::getQueryLog())->toBe([]);
    })->with('environments')->with([
        'a model method' => ['{{ live_model().getKey() }}'],
        'a model method without parentheses' => ['{{ live_model().forceSave }}'],
        'an Eloquent query' => ['{{ live_query().get()|length }}'],
        'a base query' => ['{{ live_base_query().count() }}'],
        'an eager load on a collection' => ['{{ live_rows().load("layout")|length }}'],
        'a model from a collection' => ['{{ live_rows().first().refresh() }}'],
    ]);

    it('reads attributes of live models without lazy loading relations', function (?string $theme) {
        $this->activateTheme($theme);
        $victim = $this->createTemplate(['title' => 'Live', 'layout_id' => $this->createLayout()->id])->fresh();
        extendSandboxedTwig(fn (Environment $twig) => $twig->addFunction(new TwigFunction('live_model', fn () => $victim)));
        DB::enableQueryLog();

        $output = (new TwigRenderer)->render('{{ live_model().title }}|{{ live_model()["title"] }}|{{ live_model().layout.name ?? "none" }}|{{ live_model()["layout"] ?? "none" }}', [], 'sandbox');

        expect($output)->toBe('Live|Live|none|none')
            ->and(DB::getQueryLog())->toBe([]);
    })->with('environments');

    it('hides hidden attributes of live models and keeps null columns defined', function (?string $theme) {
        $this->activateTheme($theme);
        $victim = $this->createTemplate(['title' => 'Secret'])->fresh()->setHidden(['title']);
        extendSandboxedTwig(fn (Environment $twig) => $twig->addFunction(new TwigFunction('live_model', fn () => $victim)));

        $output = (new TwigRenderer)->render('{{ live_model().title ?? "hidden" }}|{{ live_model()["title"] ?? "hidden" }}|{{ live_model().title is defined ? "y" : "n" }}|{{ live_model().layout_id is defined ? "y" : "n" }}', [], 'sandbox');

        expect($output)->toBe('hidden|hidden|n|y');
    })->with('environments');

    it('reads collections by key', function (?string $theme) {
        $this->activateTheme($theme);

        expect((new TwigRenderer)->render('{{ collect([7, 8])[0] }}|{% set c = collect({a: 5}) %}{{ c["a"] }}', [], 'sandbox'))->toBe('7|5');
    })->with('environments');

    it('formats immutable and native dates', function (?string $theme) {
        $this->activateTheme($theme);

        $output = (new TwigRenderer)->render('{{ immutable.format("Y") }}|{{ native.format("Y") }}', [
            'immutable' => CarbonImmutable::create(2001),
            'native' => new DateTimeImmutable('2002-01-01'),
        ], 'sandbox');

        expect($output)->toBe('2001|2002');
    })->with('environments');

    it('passes models as arrays that cannot query, change or run anything', function (?string $theme, string $markup) {
        $this->activateTheme($theme);
        $victim = $this->createTemplate(['title' => 'Original']);
        $changed = Template::find($victim->id);
        $changed->title = 'Changed';
        $data = ['victim' => $victim, 'changed' => $changed, 'rows' => Template::all()];
        $count = Template::count();
        DB::enableQueryLog();

        $output = rescue(fn () => (new TwigRenderer)->render($markup, $data, 'sandbox'), '', false);

        expect(DB::getQueryLog())->toBe([])
            ->and($output)->not->toContain(php_uname())
            ->and(Template::count())->toBe($count)
            ->and(Template::where('id', $victim->id)->value('title'))->toBe('Original');
    })->with('environments')->with([
        'an eager load of a magic method' => ['{{ rows.toQuery().with("truncate").get()|length }}'],
        'the base query' => ['{{ victim.getQuery().get()|length }}{{ victim.toBase().get()|length }}'],
        'a lazy load' => ['{{ victim.load("layout").layout.name }}'],
        'setAttribute and forceSave' => ['{{ victim.setAttribute("title", "Changed").forceSave() }}'],
        'forceSave without parentheses' => ['{{ changed.forceSave }}'],
        'a method named at runtime' => ['{{ attribute(changed, "force" ~ "Save") }}'],
        'alwaysPush()' => ['{{ changed.alwaysPush() }}'],
        'a PHP function name passed to a model' => ['{{ victim.unguarded("php_uname") }}{{ victim.newQuery().when(true, "php_uname") }}'],
    ]);

    it('reads models, relations, collections and dates as plain data', function (?string $theme) {
        $this->activateTheme($theme);
        $layout = $this->createLayout(['name' => 'Framed']);
        $loaded = $this->createTemplate(['title' => 'Loaded', 'layout_id' => $layout->id])->fresh();
        $lazy = $this->createTemplate(['layout_id' => $layout->id])->fresh();
        $loaded->setRelation('layout', $layout);
        $logo = new File(['disk_name' => 'abcdef0123456789.png', 'file_name' => 'logo.png']);
        $loaded->setRelation('logo', $logo);
        $secret = new class extends Model
        {
            protected $hidden = ['secret'];
        };
        $secret->setRawAttributes(['name' => 'Visible', 'secret' => 'Hidden value']);
        $markup = '{{ items[0] }}|{{ rows[0].title }}|{{ row["date"] }}|{{ loaded.layout.name }}|{{ lazy.layout.name ?? "none" }}|'
            . '{{ secret.name }}{{ secret.secret }}|{{ loaded.created_at|date("Y") }}|{{ loaded.created_at.format("Y") }}|'
            . '{{ loaded.logo.path }}|{{ [3, 1, 2]|sort|map(n => n * 2)|join(",") }}|{{ str_upper("ok") }}|'
            . '{{ pdf_qr("x")|slice(0, 10) }}|{{ "assets/logo.png"|pdfasset|length > 0 ? "asset" }}';
        $rows = Template::whereKey($loaded->id)->get();
        DB::enableQueryLog();

        $output = (new TwigRenderer)->render($markup, [
            'items' => collect(['first', 'second']),
            'rows' => $rows,
            'row' => ['date' => '2026-01-01'],
            'loaded' => $loaded,
            'lazy' => $lazy,
            'secret' => $secret,
        ], 'sandbox');

        expect(DB::getQueryLog())->toBe([])
            ->and(explode('|', $output))->toBe([
                'first', 'Loaded', '2026-01-01', 'Framed', 'none', 'Visible', now()->format('Y'), now()->format('Y'),
                $logo->getPath(), '2,4,6', 'OK', 'data:image', 'asset',
            ]);
    })->with('environments');

    it('passes a model returned by a registered variable as an array', function () {
        $template = $this->createTemplate(['title' => 'Registered']);
        PDFManager::instance()->registerVariables(['latest' => fn (): ?Template => Template::find($template->id)]);

        expect(app('dynamicpdf')->parseTemplate($this->createTemplate(['content_html' => '{{ latest.title }}|{{ latest.getKey() ?? "array" }}'])))
            ->toBe('Registered|array');
    });

    it('allows filters whose name merely contains a denied word', function () {
        $filter = fn (Environment $twig) => $twig->addFilter(new TwigFilter('pdf_macronutrients', fn (string $value): string => "{$value} kcal"));
        Event::listen('system.extendTwig', $filter);
        Event::listen('cms.extendTwig', $filter);

        expect((new TwigRenderer)->render('{{ "100"|pdf_macronutrients }}', [], 'sandbox'))->toBe('100 kcal');
    });

    it("enforces October's SecurityPolicy when safe mode uses the legacy policy", function () {
        config(['cms.safe_mode' => true, 'cms.security_policy_v1' => true]);
        $this->activateTheme('demo');

        expect(fn () => (new TwigRenderer)->render('{{ collect({3: "ab"}).map("str_repeat")|join }}', [], 'sandbox'))
            ->toThrow(TwigError::class, 'must be of type callable');
    });

    it('renders live objects without the sandbox when it is disabled', function (?string $theme) {
        config(['renatio.dynamicpdf.sandbox' => false]);
        $this->activateTheme($theme);
        $victim = $this->createTemplate();

        expect((new TwigRenderer)->render('{{ collect({3: "ab"}).map("str_repeat")|join }}|{{ victim.getKey() }}', ['victim' => $victim], 'sandbox'))
            ->toBe("ababab|{$victim->id}");
    })->with('environments');

    it('leaves the shared system environment untouched', function () {
        $this->activateTheme(null);

        app('dynamicpdf')->parseTemplate($this->createTemplate());

        expect(app('twig.environment')->hasExtension(SandboxExtension::class))->toBeFalse();
    });
});

<?php

use Backend\Facades\BackendAuth;
use October\Rain\Exception\ForbiddenException;
use October\Rain\Support\Facades\Yaml;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Template;
use Renatio\DynamicPDF\Plugin;

const PERMISSION_SOURCES = ['Plugin.php', 'classes', 'console', 'controllers', 'listeners', 'models', 'traits', 'views'];

/**
 * @param  array<int, string>  $paths  relative to the plugin directory
 * @return array<int, string>
 */
function pluginSourceMatches(string $pattern, array $paths = PERMISSION_SOURCES): array
{
    static $matches = [];

    return $matches[$pattern . "\0" . implode(',', $paths)] ??= scanPluginSources($pattern, $paths);
}

/**
 * @param  array<int, string>  $paths
 * @return array<int, string>
 */
function scanPluginSources(string $pattern, array $paths): array
{
    $root = plugins_path('renatio/dynamicpdf');
    $matches = [];

    foreach ($paths as $path) {
        $files = is_dir("{$root}/{$path}")
            ? array_map(fn (SplFileInfo $file): string => $file->getPathname(), iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$path}", FilesystemIterator::SKIP_DOTS)), false))
            : ["{$root}/{$path}"];

        foreach ($files as $file) {
            if (preg_match('/\.(php|yaml|htm)$/', $file)) {
                preg_match_all($pattern, (string) file_get_contents($file), $found);
                array_push($matches, ...$found[1]);
            }
        }
    }

    return array_values(array_unique($matches));
}

describe('Permission codes', function () {
    beforeEach(function () {
        $this->registered = array_keys((new Plugin(app()))->registerPermissions());
        $this->definitions = array_values(array_filter(array_keys((new ReflectionClass(Templates::class))->getConstant('LIST_MODELS')), is_string(...)));
    });

    it('registers every permission code the plugin checks', function () {
        $settings = array_merge(...array_column((new Plugin(app()))->registerSettings(), 'permissions'));
        $required = array_merge(...array_map(
            fn (string $controller): array => (new ReflectionClass($controller))->getDefaultProperties()['requiredPermissions'],
            [Templates::class, Layouts::class],
        ));

        expect(array_diff(pluginSourceMatches('/(renatio\.dynamicpdf\.manage_[a-z_]+(?:\.[a-z]+)?)/'), $this->registered))->toBe([])
            ->and(array_diff([...$settings, ...$required], $this->registered))->toBe([]);
    });

    it('registers the codes the list builds for every definition', function () {
        $checked = [];
        $auth = BackendAuth::getFacadeRoot();
        BackendAuth::shouldReceive('userHasAccess')->andReturnUsing(function (string $code) use (&$checked): bool {
            $checked[] = $code;

            return false;
        });

        try {
            $controller = (new ReflectionClass(Templates::class))->newInstanceWithoutConstructor();
            $actions = pluginSourceMatches('/checkListPermission\(\$definition, [\'"](\w+)[\'"]\)/', ['controllers/Templates.php']);

            foreach ($this->definitions as $definition) {
                $controller->listOverrideRecordUrl(new Template, $definition);

                foreach ($actions as $action) {
                    expect(fn () => (new ReflectionMethod($controller, 'checkListPermission'))->invoke($controller, $definition, $action))->toThrow(ForbiddenException::class);
                }
            }
        } finally {
            BackendAuth::swap($auth);
        }

        $suffixes = pluginSourceMatches('/\$permission \. [\'"]\.(\w+)[\'"]/', ['controllers/templates/_column_actions.php']);

        foreach ($this->definitions as $definition) {
            foreach ($suffixes as $suffix) {
                $checked[] = "renatio.dynamicpdf.manage_{$definition}.{$suffix}";
            }
        }

        expect($actions)->not->toBeEmpty()
            ->and($suffixes)->not->toBeEmpty()
            ->and(array_values(array_diff($checked, $this->registered)))->toBe([]);
    });

    it('maps every form permission a controller checks to a registered code', function (string $definition, array $paths) {
        $permissions = Yaml::parseFile(plugins_path("renatio/dynamicpdf/controllers/{$definition}/config_form.yaml"))['permissions'];

        expect(array_keys($permissions))->toEqualCanonicalizing(pluginSourceMatches('/[\'"](model[A-Z][A-Za-z]+)[\'"]/', $paths))
            ->and(array_diff($permissions, $this->registered))->toBe([])
            ->and(array_values(array_unique($permissions)))->toHaveSameSize($permissions)
            ->each->toStartWith("renatio.dynamicpdf.manage_{$definition}.");
    })->with([
        'templates' => ['templates', ['controllers/Templates.php', 'controllers/templates', 'traits']],
        'layouts, which falls back to the templates views' => ['layouts', ['controllers/Layouts.php', 'controllers/layouts', 'controllers/templates', 'traits']],
    ]);

    it('grants exactly the registered codes of every list definition on upgrade', function () {
        $migration = new ReflectionClass(require plugins_path('renatio/dynamicpdf/updates/20260930_0002_grant_granular_permissions.php'));
        $parents = $migration->getConstant('PARENTS');
        $children = $migration->getConstant('CHILDREN');
        $granted = [...$parents, ...array_merge(...array_map(
            fn (string $parent): array => array_map(fn (string $child): string => "{$parent}.{$child}", $children),
            $parents,
        ))];

        expect($granted)->toEqualCanonicalizing($this->registered)
            ->and(str_replace('renatio.dynamicpdf.manage_', '', $parents))->toEqualCanonicalizing($this->definitions);
    });
});

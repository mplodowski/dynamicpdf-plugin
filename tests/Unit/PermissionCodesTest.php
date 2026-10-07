<?php

use October\Rain\Support\Facades\Yaml;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Plugin;

/**
 * @return array<int, string>
 */
function pluginSourceMatches(string $pattern): array
{
    $root = plugins_path('renatio/dynamicpdf');
    $matches = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        $relative = substr($file->getPathname(), strlen($root) + 1);

        if (preg_match('#^(tests|vendor|lang|node_modules)/#', $relative) || ! in_array($file->getExtension(), ['php', 'yaml', 'htm'], true)) {
            continue;
        }

        preg_match_all($pattern, (string) file_get_contents($file->getPathname()), $found);
        array_push($matches, ...$found[1]);
    }

    return array_values(array_unique($matches));
}

describe('Permission codes', function () {
    beforeEach(function () {
        $this->registered = array_keys((new Plugin(app()))->registerPermissions());
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

    it('maps every form permission the controllers check to a registered code', function (string $definition) {
        $permissions = Yaml::parseFile(plugins_path("renatio/dynamicpdf/controllers/{$definition}/config_form.yaml"))['permissions'];

        expect(array_keys($permissions))->toEqualCanonicalizing(pluginSourceMatches("/'(model[A-Z][A-Za-z]+)'/"))
            ->and(array_diff($permissions, $this->registered))->toBe([])
            ->and(array_values(array_unique($permissions)))->toHaveCount(count($permissions))
            ->each->toStartWith("renatio.dynamicpdf.manage_{$definition}.");
    })->with(['templates', 'layouts']);

    it('builds the codes of every list definition and grants exactly the registered ones on upgrade', function () {
        $migration = new ReflectionClass(require plugins_path('renatio/dynamicpdf/updates/20260930_0002_grant_granular_permissions.php'));
        $parents = $migration->getConstant('PARENTS');
        $children = $migration->getConstant('CHILDREN');
        $granted = [...$parents, ...array_merge(...array_map(
            fn (string $parent): array => array_map(fn (string $child): string => "{$parent}.{$child}", $children),
            $parents,
        ))];

        expect($granted)->toEqualCanonicalizing($this->registered)
            ->and(str_replace('renatio.dynamicpdf.manage_', '', $parents))
            ->toEqualCanonicalizing(array_keys((new ReflectionClass(Templates::class))->getConstant('LIST_MODELS')));
    });
});

<?php

use Renatio\DynamicPDF\Plugin;
use System\Classes\PluginBase;

describe('Plugin Class', function () {
    describe('Class Structure', function () {
        it('extends PluginBase', function () {
            $reflection = new ReflectionClass(Plugin::class);

            expect($reflection->getParentClass()?->getName())->toBe(PluginBase::class);
        });

        it('has required methods', function (string $method) {
            expect(method_exists(Plugin::class, $method))->toBeTrue();
        })->with([
            'pluginDetails',
            'boot',
            'register',
            'registerPermissions',
            'registerMarkupTags',
            'registerSettings',
            'registerPDFTemplates',
            'registerPDFLayouts',
        ]);
    });

    describe('Plugin Details', function () {
        it('returns an array', function () {
            $plugin = new Plugin(app());

            expect($plugin->pluginDetails())->toBeArray();
        });

        it('has required keys', function (string $key) {
            $plugin = new Plugin(app());
            $details = $plugin->pluginDetails();

            expect($details)->toHaveKey($key);
        })->with(['name', 'description', 'author', 'icon', 'homepage']);

        it('has Renatio as author', function () {
            $plugin = new Plugin(app());
            $details = $plugin->pluginDetails();

            expect($details['author'])->toBe('Renatio');
        });

        it('uses PDF icon', function () {
            $plugin = new Plugin(app());
            $details = $plugin->pluginDetails();

            expect($details['icon'])->toBe('octo-icon-file-pdf-o');
        });
    });

    describe('Permissions', function () {
        it('returns an array of permissions', function () {
            $plugin = new Plugin(app());

            expect($plugin->registerPermissions())->toBeArray();
        });

        it('registers manage_templates permission', function () {
            $plugin = new Plugin(app());
            $permissions = $plugin->registerPermissions();

            expect($permissions)->toHaveKey('renatio.dynamicpdf.manage_templates');
        });

        it('registers manage_layouts permission', function () {
            $plugin = new Plugin(app());
            $permissions = $plugin->registerPermissions();

            expect($permissions)->toHaveKey('renatio.dynamicpdf.manage_layouts');
        });
    });

    describe('Settings', function () {
        it('returns an array of settings', function () {
            $plugin = new Plugin(app());

            expect($plugin->registerSettings())->toBeArray();
        });

        it('registers templates setting', function () {
            $plugin = new Plugin(app());
            $settings = $plugin->registerSettings();

            expect($settings)->toHaveKey('templates');
        });

        it('templates setting has required keys', function (string $key) {
            $plugin = new Plugin(app());
            $settings = $plugin->registerSettings();

            expect($settings['templates'])->toHaveKey($key);
        })->with(['label', 'category', 'icon', 'url', 'description', 'permissions']);
    });

    describe('Markup Tags', function () {
        it('returns an array', function () {
            $plugin = new Plugin(app());

            expect($plugin->registerMarkupTags())->toBeArray();
        });
    });

    describe('PDF Templates and Layouts', function () {
        it('registerPDFTemplates returns an array', function () {
            $plugin = new Plugin(app());

            expect($plugin->registerPDFTemplates())->toBeArray();
        });

        it('registerPDFLayouts returns an array', function () {
            $plugin = new Plugin(app());

            expect($plugin->registerPDFLayouts())->toBeArray();
        });
    });
});

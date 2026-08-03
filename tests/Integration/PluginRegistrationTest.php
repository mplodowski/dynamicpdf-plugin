<?php

use System\Classes\PluginManager;

describe('Plugin Registration', function () {
    describe('October CMS Integration', function () {
        it('registers the plugin with October CMS', function () {
            $pluginManager = PluginManager::instance();

            expect($pluginManager->hasPlugin('Renatio.DynamicPDF'))->toBeTrue();
        });

        it('can be found by identifier', function () {
            $pluginManager = PluginManager::instance();
            $plugin = $pluginManager->findByIdentifier('Renatio.DynamicPDF');

            expect($plugin)->not->toBeNull();
        });

        it('boots without errors', function () {
            $pluginManager = PluginManager::instance();
            $plugin = $pluginManager->findByIdentifier('Renatio.DynamicPDF');

            expect(function () use ($plugin) {
                $plugin->boot();
            })->not->toThrow(Exception::class);
        });
    });

    describe('Service Registration', function () {
        it('binds dynamicpdf to container', function () {
            expect(app()->bound('dynamicpdf'))->toBeTrue();
        });

        it('registers DomPDF service provider', function () {
            $providers = app()->getLoadedProviders();

            expect($providers)->toHaveKey('Barryvdh\DomPDF\ServiceProvider');
        });
    });
});

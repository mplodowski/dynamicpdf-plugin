<?php

/*
 * Bootstrap file for running plugin tests with Pest.
 *
 * This file loads the October CMS test bootstrap and ensures
 * the Pest configuration is loaded for Pest-style tests.
 */

require __DIR__ . '/../../../../modules/system/tests/bootstrap.php';

/*
 * Load Pest configuration if running under Pest.
 * This check ensures the Pest.php file is loaded once.
 */
if (class_exists('Pest\TestSuite', false)) {
    $pestConfigFile = __DIR__ . '/Pest.php';
    if (file_exists($pestConfigFile)) {
        require_once $pestConfigFile;
    }
}

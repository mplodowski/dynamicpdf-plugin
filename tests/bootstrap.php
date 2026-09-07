<?php

require __DIR__ . '/../../../../modules/system/tests/bootstrap.php';

if (class_exists('Pest\TestSuite', false)) {
    $pestConfigFile = __DIR__ . '/Pest.php';
    if (file_exists($pestConfigFile)) {
        require_once $pestConfigFile;
    }
}

<?php

/** October defines \TestCase and \PluginTestCase without a namespace and loads them via require, so no autoloader can reach them. */
require_once __DIR__ . '/../../../modules/system/tests/TestCase.php';
require_once __DIR__ . '/../../../modules/system/tests/PluginTestCase.php';

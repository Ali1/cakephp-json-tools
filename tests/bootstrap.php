<?php

declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Core\Configure;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

define('ROOT', dirname(__DIR__));
define('APP_DIR', 'src');
define('TESTS', ROOT . DS . 'tests' . DS);
define('TEST_APP', TESTS . 'TestApp' . DS);
define('APP', TEST_APP . APP_DIR . DS);
define('TMP', ROOT . DS . 'tmp' . DS);
define('CONFIG', TESTS . 'config' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . APP_DIR . DS);

foreach ([TMP, LOGS, CACHE] as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
    }
}

require ROOT . DS . 'vendor' . DS . 'autoload.php';
require CORE_PATH . 'config' . DS . 'bootstrap.php';

Configure::write('App', [
    'namespace' => 'TestApp',
    'encoding' => 'UTF-8',
    'fullBaseUrl' => 'http://localhost',
    'paths' => [
        'templates' => [TEST_APP . 'templates' . DS],
    ],
]);
Configure::write('debug', true);

Cache::setConfig([
    'default' => [
        'className' => 'File',
        'path' => CACHE,
    ],
    '_cake_core_' => [
        'className' => 'File',
        'prefix' => 'json_tools_cake_core_',
        'path' => CACHE . 'persistent' . DS,
        'serialize' => true,
        'duration' => '+10 seconds',
    ],
    '_cake_model_' => [
        'className' => 'File',
        'prefix' => 'json_tools_cake_model_',
        'path' => CACHE . 'models' . DS,
        'serialize' => true,
        'duration' => '+10 seconds',
    ],
]);

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

// Workaround https://bugs.php.net/64566
if (ini_get('auto_prepend_file') && !in_array(realpath(ini_get('auto_prepend_file')), get_included_files(), true)) {
    require ini_get('auto_prepend_file');
}

if (is_file($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.$_SERVER['SCRIPT_NAME'])) {
    return false;
}

$script = isset($_ENV['APP_FRONT_CONTROLLER']) ? $_ENV['APP_FRONT_CONTROLLER'] : 'index.php';

$_SERVER = array_merge($_SERVER, $_ENV);
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.$script;

// Since we are rewriting to app_dev.php, adjust SCRIPT_NAME and PHP_SELF accordingly
$_SERVER['SCRIPT_NAME'] = DIRECTORY_SEPARATOR.$script;
$_SERVER['PHP_SELF'] = DIRECTORY_SEPARATOR.$script;

require $_SERVER['SCRIPT_FILENAME'];

error_log(sprintf('%s:%d [%d]: %s', $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'], http_response_code(), $_SERVER['REQUEST_URI']), 4);

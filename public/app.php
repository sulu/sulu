<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

if (preg_match('/^\/admin(\/|$)/', $_SERVER['REQUEST_URI'])) {
    require_once __DIR__ . '/admin.php';
} else {
    require_once __DIR__ . '/website.php';
}

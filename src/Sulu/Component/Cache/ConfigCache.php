<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache;

use Symfony\Component\Config\ConfigCache as BaseConfigCache;

class ConfigCache extends BaseConfigCache
{
    /**
     * @var string
     */
    protected $file;

    public function __construct($file, $debug)
    {
        parent::__construct($file, $debug);

        $this->file = $file;
    }

    /**
     * Returns file content.
     *
     * @return string
     */
    public function read()
    {
        return file_get_contents($this->file);
    }
}

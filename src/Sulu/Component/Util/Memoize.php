<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

use Doctrine\Common\Cache\Cache;

/**
 * Utility-Class which implements Memoize Pattern
 */
class Memoize
{
    /**
     * @var Cache
     */
    protected $cache;

    function __construct($cache)
    {
        $this->cache = $cache;
    }

    public function get($value)
    {
        $callers = debug_backtrace();

        $id = sprintf('%s(%s)', $callers[1]['function'], json_encode($callers[1]['args']));
        if ($this->cache->contains($id)) {
            return $this->cache->fetch($id);
        } else {
            $value = $value();
            $this->cache->save($id, $value);

            return $value;
        }
    }
} 

<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR;

/**
 * cleans path strings.
 */
class PathCleanup implements PathCleanupInterface
{
    /**
     * replacers for cleanup.
     *
     * @var array
     */
    protected $replacers = [];

    /**
     * valid pattern for path
     * example: /products/machines
     *  + test whole input case insensitive
     *  + trailing slash
     *  + one or more sign (a-z, 0-9, -, _)
     *  + repeat.
     *
     * @var string
     */
    private $pattern = '/^(\/[a-z0-9][a-z0-9-_]*)+$/';

    /**
     * PathCleanup constructor.
     *
     * @param array $replacers
     */
    public function __construct(array $replacers)
    {
        $this->replacers = $replacers;
    }

    /**
     * returns a clean string.
     *
     * @param string $dirty dirty string to cleanup
     * @param string $languageCode
     *
     * @return string clean string
     */
    public function cleanup($dirty, $languageCode = null)
    {
        $replacers = $this->replacers['default'];

        if ($languageCode !== null) {
            $replacers = array_merge(
                $replacers,
                (isset($this->replacers[$languageCode]) ? $this->replacers[$languageCode] : [])
            );
        }

        if (count($replacers) > 0) {
            foreach ($replacers as $key => $value) {
                $dirty = str_replace($key, $value, $dirty);
            }
        }

        $clean = strtolower($dirty);

        // Inspired by ZOOLU
        // delete problematic characters
        $clean = str_replace('%2F', '/', urlencode(preg_replace('/([^A-za-z0-9\s-_\/])/', '', $clean)));

        // replace multiple dash with one
        $clean = preg_replace('/([-]+)/', '-', $clean);

        // remove dash after slash
        $clean = preg_replace('/\/[-]+/', '/', $clean);

        // delete dash at the beginning or end
        $clean = preg_replace('/^([-])/', '', $clean);
        $clean = preg_replace('/([-])$/', '', $clean);

        // remove double slashes
        $clean = str_replace('//', '/', $clean);

        return $clean;
    }

    /**
     * returns TRUE if path is valid.
     *
     * @param string $path
     *
     * @return bool
     */
    public function validate($path)
    {
        return $path === '/' || preg_match($this->pattern, $path) === 1;
    }
}

<?php
/*
 * This file is part of the Sulu CMS.
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
    protected $replacers = array(
        'default' => array(
            ' ' => '-',
            '+' => '-',
            '.' => '-',
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            // because strtolower ignores Ä,Ö,Ü
            'Ä' => 'ae',
            'Ö' => 'oe',
            'Ü' => 'ue',
            'ß' => 'ss',
            // TODO should be filled
        ),
        'de' => array(
            '&' => 'und',
        ),
        'en' => array(
            '&' => 'and',
        ),
    );

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
    private $pattern = '/^(\/[a-z0-9-_]+)+$/i';

    /**
     * returns a clean string.
     *
     * @param string $dirty dirty string to cleanup
     * @param  string $languageCode
     *
     * @return string clean string
     */
    public function cleanup($dirty, $languageCode)
    {
        $clean = strtolower($dirty);

        $replacers = array_merge(
            $this->replacers['default'],
            (isset($this->replacers[$languageCode]) ? $this->replacers[$languageCode] : array())
        );

        if (count($replacers) > 0) {
            foreach ($replacers as $key => $value) {
                $clean = str_replace($key, $value, $clean);
            }
        }

        // Inspired by ZOOLU
        // delete problematic characters
        $clean = str_replace('%2F', '/', urlencode(preg_replace('/([^A-za-z0-9\s-_\/])/', '', $clean)));

        // replace multiple minus with one
        $clean = preg_replace('/([-]+)/', '-', $clean);

        // delete minus at the beginning or end
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

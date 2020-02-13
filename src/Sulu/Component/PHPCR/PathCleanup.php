<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * cleans path strings.
 *
 * @internal this class is for internal use
 */
class PathCleanup implements PathCleanupInterface
{
    /**
     * replacers for cleanup.
     *
     * @var array
     */
    protected $replacers = [];

    /** @var SluggerInterface */
    private $slugger;

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

    public function __construct(array $replacers, SluggerInterface $slugger = null)
    {
        if (null === $slugger) {
            @trigger_error(
                'Initializing the PathCleanup without a slugger is deprecated since Sulu 2.1.',
                E_USER_DEPRECATED
            );
            $slugger = new AsciiSlugger();
        }

        $this->replacers = $replacers;
        $this->slugger = $slugger;
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

        if (null !== $languageCode) {
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

        $parts = explode('/', $dirty);
        $newParts = [];
        foreach ($parts as $part) {
            $part = str_replace('&', '-and-', $part);
            $slug = $this->slugger->slug($part, '-', $languageCode);
            $slug = $slug->lower();
            $newParts[] = $slug->toString();
        }

        return implode('/', $newParts);
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
        return '/' === $path || 1 === preg_match($this->pattern, $path);
    }
}

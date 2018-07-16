<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

/**
 * Provides a centralized repository of path components.
 *
 * Note that this is not used by the document manager itself, but
 * is a useful utility for implementors.
 *
 * TODO: Move this class to somewhere more appropriate
 */
class PathSegmentRegistry
{
    /**
     * @var array
     */
    private $pathSegments;

    /**
     * @param array Array of roles to pathSegments
     */
    public function __construct(array $pathSegments = [])
    {
        $this->pathSegments = $pathSegments;
    }

    /**
     * Return the configured named path segment.
     *
     * @param string $name Name of path segment
     *
     * @throws \InvalidArgumentException
     *
     * @return string The path segment
     */
    public function getPathSegment($name)
    {
        if (!isset($this->pathSegments[$name])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unknown path segment "%s". Known path segments: "%s"',
                    $name,
                    implode('", "', array_keys($this->pathSegments))
                )
            );
        }

        return $this->pathSegments[$name];
    }
}

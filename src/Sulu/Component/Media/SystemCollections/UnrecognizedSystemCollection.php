<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\SystemCollections;

/**
 * Indicates not existing system collection.
 */
class UnrecognizedSystemCollection extends \Exception
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string []
     */
    private $recognizedSystemCollections;

    public function __construct($key, array $recognizedSystemCollections)
    {
        parent::__construct(
            sprintf(
                'Unrecognized system collection "%s" available collections: [%s]',
                $key,
                implode(', ', $recognizedSystemCollections)
            )
        );

        $this->key = $key;
        $this->recognizedSystemCollections = $recognizedSystemCollections;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string[]
     */
    public function getRecognizedSystemCollections()
    {
        return $this->recognizedSystemCollections;
    }
}

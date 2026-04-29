<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @param string $key
     * @param string[] $recognizedSystemCollections
     */
    public function __construct(
        private $key,
        private array $recognizedSystemCollections
    ) {
        parent::__construct(
            \sprintf(
                'Unrecognized system collection "%s" available collections: [%s]',
                $this->key,
                \implode(', ', $this->recognizedSystemCollections)
            )
        );
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

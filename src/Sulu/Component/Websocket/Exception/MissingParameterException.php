<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\Exception;

/**
 * Represents a missing Parameter.
 */
class MissingParameterException extends \Exception
{
    /**
     * @var string
     */
    private $name;

    public function __construct($name)
    {
        parent::__construct(sprintf('Parameter "%s" missing for preview', $name));

        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getName();
    }
}

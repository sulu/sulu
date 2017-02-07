<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Events;

use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is thrown right before a preview will be rendered.
 */
class PreRenderEvent extends Event
{
    /**
     * @var RequestAttributes
     */
    private $requestAttributes;

    /**
     * @param RequestAttributes $requestAttributes
     */
    public function __construct(RequestAttributes $requestAttributes)
    {
        $this->requestAttributes = $requestAttributes;
    }

    /**
     * Returns requestAttributes.
     *
     * @return RequestAttributes
     */
    public function getRequestAttributes()
    {
        return $this->requestAttributes;
    }

    /**
     * Returns request attribute with given name.
     *
     * @param string $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return $this->requestAttributes->getAttribute($name, $default);
    }
}

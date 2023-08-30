<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Exception;

/**
 * Base exception to catch all rendering errors.
 */
abstract class PreviewRendererException extends PreviewException
{
    public const BASE_CODE = 9900;

    /**
     * @var mixed
     */
    private $object;

    /**
     * @var int|string
     */
    private $id;

    /**
     * @var string
     */
    private $webspaceKey;

    /**
     * @var string
     */
    private $locale;

    /**
     * @param string $message
     * @param int $code
     * @param int|string $id
     * @param string $webspaceKey
     * @param string $locale
     */
    public function __construct($message, $code, $object, $id, $webspaceKey, $locale, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->object = $object;
        $this->id = $id;
        $this->webspaceKey = $webspaceKey;
        $this->locale = $locale;
    }

    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getWebspaceKey()
    {
        return $this->webspaceKey;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }
}

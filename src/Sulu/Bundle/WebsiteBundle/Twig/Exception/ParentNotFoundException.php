<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Exception;

/**
 * Exception for parent not found.
 */
class ParentNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $uuid;

    public function __construct($uuid)
    {
        parent::__construct(sprintf('Parent for "%s" not found (perhaps it is the startpage?)', $uuid));
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }
}

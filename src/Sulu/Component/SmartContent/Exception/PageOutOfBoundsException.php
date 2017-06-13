<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The requested page was out of bounds.
 */
class PageOutOfBoundsException extends NotFoundHttpException
{
    /**
     * @var int
     */
    private $page;

    /**
     * @param int $page
     */
    public function __construct($page)
    {
        parent::__construct(sprintf('Page "%s" out of bounds exception.', $page));

        $this->page = $page;
    }

    /**
     * Returns page.
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }
}

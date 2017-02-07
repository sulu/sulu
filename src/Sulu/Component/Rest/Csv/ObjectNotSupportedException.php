<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Csv;

use FOS\RestBundle\View\View;

/**
 * The view is not supported to create a csv-response.
 */
class ObjectNotSupportedException extends \Exception
{
    /**
     * @var View
     */
    private $view;

    public function __construct(View $view)
    {
        parent::__construct('The view is not supported to create a csv-response.');

        $this->view = $view;
    }

    /**
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }
}

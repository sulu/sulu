<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Rest\Exception\MissingParameterChoiceException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\ParameterDataTypeException;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles content nodes.
 */
class PageController extends NodeController
{
    protected static $relationName = 'pages';

    /**
     * Returns content array by parent or webspace root.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws MissingParameterChoiceException
     * @throws MissingParameterException
     * @throws ParameterDataTypeException
     */
    protected function cgetContent(Request $request)
    {
        if (!$request->query->has('parent')) {
            $request->request->set('webspace-nodes', 'single');
        }

        return parent::cgetContent($request);
    }
}

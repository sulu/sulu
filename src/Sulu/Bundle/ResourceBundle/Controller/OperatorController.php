<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\ResourceBundle\Resource\OperatorManagerInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes operators available through a REST API
 * Class OperatorController.
 */
class OperatorController extends RestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    protected static $entityName = 'SuluResourceBundle:Operator';

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $list = new CollectionRepresentation(
            $this->getManager()->findAllByLocale($locale),
            self::$entityKey
        );

        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * @return OperatorManagerInterface
     */
    protected function getManager()
    {
        return $this->get('sulu_resource.operator_manager');
    }
}

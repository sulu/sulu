<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use Sulu\Bundle\AdminBundle\Widgets\WidgetException;
use Sulu\Bundle\AdminBundle\Widgets\WidgetsHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the Widgets
 *
 * @package Sulu\Bundle\AdminBundle\Controller
 */
class WidgetGroupsController extends Controller
{
    protected $widgetsHandler;

    /**
     * renders a widget group
     * @param String $groupAlias
     * @param Request $request
     * @return Response
     */
    public function groupAction($groupAlias, Request $request) {
        try {
            $groupAlias = str_replace('-', '_', $groupAlias);
            return new Response(
                $this->getWidgetsHandler()->renderWidgetGroup(
                    $groupAlias,
                    $request->query->all()
                )
            );
        } catch (WidgetException $ex) {
            return new Response($ex->getMessage());
        }
    }

    /**
     * Returns the widget handler service
     *
     * @return WidgetsHandlerInterface
     */
    private function getWidgetsHandler()
    {
        if ($this->widgetsHandler === null) {
            $this->widgetsHandler = $this->get('sulu_admin.widgets_handler');
        }
        return $this->widgetsHandler;
    }
}

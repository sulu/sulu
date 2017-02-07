<?php

/*
 * This file is part of Sulu.
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
 * Renders the Widgets.
 */
class WidgetGroupsController extends Controller
{
    protected $widgetsHandler;

    /**
     * renders a widget group.
     *
     * @param string  $groupAlias
     * @param Request $request
     *
     * @return Response
     */
    public function groupAction($groupAlias, Request $request)
    {
        $widgetHandler = $this->getWidgetsHandler();
        $groupAlias = str_replace('-', '_', $groupAlias);

        if (!$widgetHandler->hasWidgetGroup($groupAlias)) {
            return new Response('', 404);
        }

        try {
            $content = $this->getWidgetsHandler()->renderWidgetGroup(
                $groupAlias,
                $request->query->all()
            );

            return new Response($content, $content !== '' ? 200 : 204);
        } catch (WidgetException $ex) {
            return new Response($ex->getMessage(), 400);
        }
    }

    /**
     * Returns the widget handler service.
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

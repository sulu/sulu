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
 * renderes the Widgets
 *
 * @package Sulu\Bundle\AdminBundle\Controller
 */
class WidgetGroupsController extends Controller
{
    protected $widgetsHandler;

    public function contactInfoAction(Request $request)
    {
        $aliases = array(
            'sulu-contact-contact-info'
        );
        try {
            return new Response(
                $this->getWidgetsHandler()->render(
                    $aliases,
                    $request->query->all()
                )
            );
        } catch (WidgetException $ex) {
            return new Response($ex->getMessage());
        }
    }

    public function accountInfoAction(Request $request)
    {
        $aliases = array(
            'sulu-contact-account-info',
            'sulu-contact-contacts'
        );
        try {
            return new Response(
                $this->getWidgetsHandler()->render(
                    $aliases,
                    $request->query->all()
                )
            );
        } catch (WidgetException $ex) {
            return new Response($ex->getMessage());
        }
    }

    public function contactDetailAction(Request $request)
    {
        $aliases = array(
            'sulu-contact-toolbar',
            'sulu-contact-account-address',
            'sulu-contact-table'
        );
        try {
            return new Response(
                $this->getWidgetsHandler()->render(
                    $aliases,
                    $request->query->all()
                )
            );
        } catch (WidgetException $ex) {
            return new Response($ex->getMessage());
        }
    }

    public function accountDetailAction(Request $request)
    {
        $aliases = array(
            'sulu-contact-toolbar',
            'sulu-contact-contacts',
            'sulu-contact-table'
        );
        try {
            return new Response(
                $this->getWidgetsHandler()->render(
                    $aliases,
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

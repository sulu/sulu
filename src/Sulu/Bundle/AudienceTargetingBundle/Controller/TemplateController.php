<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves templates for target groups.
 */
class TemplateController extends Controller
{
    const NUMBER_OF_PRIORITIES = 5;

    /**
     * Returns details template for editing page of a target group.
     *
     * @return Response
     */
    public function targetGroupDetailsAction()
    {
        return $this->render(
            'SuluAudienceTargetingBundle:Template:target-group-details.html.twig',
            [
                'priorities' => $this->retrievePrioritiesForSelect(),
                'webspaces' => $this->retrieveAllWebspacesForSelect(),
            ]
        );
    }

    /**
     * Returns all webspaces in a format that can be displayed by a select.
     *
     * @return array
     */
    private function retrieveAllWebspacesForSelect()
    {
        $webspaces = $this->get('sulu_core.webspace.webspace_manager')->getWebspaceCollection()->getWebspaces();

        $result = [];
        foreach ($webspaces as $webspace) {
            $result[] = [
                'id' => $webspace->getKey(),
                'name' => $webspace->getName(),
            ];
        }

        return $result;
    }

    /**
     * Returns all priorities from 1 to number as defined in constant.
     *
     * @return array
     */
    private function retrievePrioritiesForSelect()
    {
        $result = [];
        for ($i = 1; $i <= static::NUMBER_OF_PRIORITIES; ++$i) {
            $result[] = [
                'id' => $i,
                'name' => $i,
            ];
        }

        return $result;
    }
}

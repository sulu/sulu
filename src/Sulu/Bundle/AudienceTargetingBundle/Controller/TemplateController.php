<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Controller;

use Sulu\Bundle\AudienceTargetingBundle\Rule\RuleInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves templates for target groups.
 */
class TemplateController extends Controller
{
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
     * Returns the overlay for editing the rules in the target group editing page.
     *
     * @return Response
     */
    public function ruleOverlayAction()
    {
        $frequencies = [];
        foreach (array_flip($this->getParameter('sulu_audience_targeting.frequencies')) as $name => $value) {
            $frequencies[] = [
                'id' => $value,
                'name' => $this->get('translator')->trans('sulu_audience_targeting.frequencies.' . $name, [], 'backend'),
            ];
        }

        return $this->render('SuluAudienceTargetingBundle:Template:rule-overlay.html.twig', [
            'frequencies' => $frequencies,
        ]);
    }

    /**
     * Returns a single row for the condition.
     *
     * @return Response
     */
    public function conditionRowAction()
    {
        $rules = [];
        foreach ($this->get('sulu_audience_targeting.rules_collection')->getRules() as $alias => $rule) {
            $rules[] = [
                'id' => $alias,
                'name' => $rule->getName(),
            ];
        }

        return $this->render('SuluAudienceTargetingBundle:Template:condition-row.html.twig', [
            'rules' => $rules,
        ]);
    }

    /**
     * Returns all available condition types in containers with their alias as an id.
     *
     * @return Response
     */
    public function conditionTypesAction()
    {
        $ruleTemplates = array_map(function(RuleInterface $rule) {
            return $rule->getType()->getTemplate();
        }, $this->get('sulu_audience_targeting.rules_collection')->getRules());

        return $this->render('SuluAudienceTargetingBundle:Template:condition-types.html.twig', [
            'ruleTemplates' => $ruleTemplates,
        ]);
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
        for ($i = 1; $i <= $this->getNumberOfPriorities(); ++$i) {
            $result[] = [
                'id' => $i,
                'name' => $i,
            ];
        }

        return $result;
    }

    /**
     * Returns the number of priorities.
     *
     * @return int
     */
    private function getNumberOfPriorities()
    {
        return $this->container->getParameter('sulu_audience_targeting.number_of_priorities');
    }
}

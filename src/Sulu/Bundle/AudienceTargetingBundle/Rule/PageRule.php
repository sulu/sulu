<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Rule;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class PageRule implements RuleInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate(array $options)
    {
        // TODO: Implement evaluate() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans('sulu_audience_targeting.rules.page', [], 'backend');
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return '<div class="grid-12">
            <div data-aura-component="single-internal-link@sulucontent"
                data-aura-instance-name="page-rule"
                data-aura-url="/admin/api/nodes{/uuid}?depth=1&language=<%= locale %>&webspace-node=true"
                data-aura-selected-url="/admin/api/nodes/{uuid}?tree=true&language=<%= locale %>&fields=title,order,published&webspace-nodes=all"
                data-aura-column-navigation-url="/admin/api/nodes?fields=title,order,published&language=<%= locale %>&webspace-nodes=all"
                data-aura-result-key="nodes"
                ></div>
        </div>';
    }
}

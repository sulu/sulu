<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Rule\Type;

class InternalLink implements RuleTypeInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return '<div class="grid-12">
                <div data-condition-name="' . $this->name . '"
                    data-rule-type="internal_link"
                    data-aura-component="single-internal-link@sulucontent"
                    data-aura-instance-name="page-rule"
                    data-aura-url="/admin/api/nodes{/uuid}?depth=1&language=<%= locale %>&webspace-node=true"
                    data-aura-selected-url="/admin/api/nodes/{uuid}?tree=true&language=<%= locale %>&fields=title,order,published&webspace-nodes=all"
                    data-aura-column-navigation-url="/admin/api/nodes?fields=title,order,published&language=<%= locale %>&webspace-nodes=all"
                    data-aura-result-key="nodes"></div>
            </div>';
    }
}

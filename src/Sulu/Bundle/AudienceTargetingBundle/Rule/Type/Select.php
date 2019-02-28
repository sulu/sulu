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

class Select implements RuleTypeInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $options;

    /**
     * @param string $name
     */
    public function __construct($name, $options)
    {
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return '<div class="grid-12">
                <div data-condition-name="' . $this->name . '"
                    data-rule-type="select"
                    data-aura-component="select@husky"
                    data-aura-data=\'' . json_encode($this->options) . '\'
                    ></div>
            </div>';
    }
}

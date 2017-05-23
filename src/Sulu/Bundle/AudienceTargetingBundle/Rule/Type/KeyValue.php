<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Rule\Type;

class KeyValue implements RuleTypeInterface
{
    /**
     * @var string
     */
    private $keyName;

    /**
     * @var string
     */
    private $valueName;

    public function __construct($keyName, $valueName)
    {
        $this->keyName = $keyName;
        $this->valueName = $valueName;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return '<div class="grid-col-6">
                <input class="form-element"
                       type="text"
                       data-rule-type="text"
                       data-condition-name="' . $this->keyName . '" />
            </div>
            <div class="grid-col-6">
                <input class="form-element"
                       type="text"
                       data-rule-type="text"
                       data-condition-name="' . $this->valueName . '" />
            </div>';
    }
}

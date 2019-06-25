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

    /**
     * @var string
     */
    private $keyPlaceholder;

    /**
     * @var string
     */
    private $valuePlaceholder;

    public function __construct($keyName, $valueName, $keyPlaceholder, $valuePlaceHolder)
    {
        $this->keyName = $keyName;
        $this->valueName = $valueName;
        $this->keyPlaceholder = $keyPlaceholder;
        $this->valuePlaceholder = $valuePlaceHolder;
    }

    public function getName(): string
    {
        return 'key_value';
    }

    public function getOptions(): array
    {
        return [
            'keyName' => $this->keyName,
            'valueName' => $this->valueName,
            'keyPlaceholder' => $this->keyPlaceholder,
            'valuePlaceholder' => $this->valuePlaceholder,
        ];
    }
}

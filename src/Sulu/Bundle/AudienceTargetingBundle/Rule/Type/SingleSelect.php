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

class SingleSelect implements RuleTypeInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $options;

    public function __construct(string $name, array $options)
    {
        $this->name = $name;
        $this->options = $options;
    }

    public function getName(): string
    {
        return 'single_select';
    }

    public function getOptions(): array
    {
        return [
            'name' => $this->name,
            'options' => $this->options,
        ];
    }
}

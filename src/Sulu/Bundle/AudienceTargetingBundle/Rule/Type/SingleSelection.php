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

class SingleSelection implements RuleTypeInterface
{
    private string $name;

    private string $resourceKey;

    private string $adapter;

    private string $icon;

    /**
     * @var string[]
     */
    private array $displayProperties;

    private string $emptyText;

    private string $overlayTitle;

    public function __construct(
        string $name,
        string $resourceKey,
        string $adapter,
        string $icon,
        array $displayProperties,
        string $emptyText,
        string $overlayTitle
    ) {
        $this->name = $name;
        $this->resourceKey = $resourceKey;
        $this->adapter = $adapter;
        $this->icon = $icon;
        $this->displayProperties = $displayProperties;
        $this->emptyText = $emptyText;
        $this->overlayTitle = $overlayTitle;
    }

    public function getName(): string
    {
        return 'single_selection';
    }

    public function getOptions(): array
    {
        return [
            'adapter' => $this->adapter,
            'displayProperties' => $this->displayProperties,
            'emptyText' => $this->emptyText,
            'icon' => $this->icon,
            'name' => $this->name,
            'overlayTitle' => $this->overlayTitle,
            'resourceKey' => $this->resourceKey,
        ];
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ResourceMetadata\Form;

class Option
{
    /**
     * @var null|string
     */
    protected $name;

    /**
     * @var string|int|Option[]
     */
    protected $value;

    /**
     * @var ?string
     */
    protected $title;

    /**
     * @var ?string
     */
    protected $placeholder;

    /**
     * @var ?string
     */
    protected $infoText;

    /**
     * @return null|string|int|float
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param null|string|int|float $name
     */
    public function setName($name = null): void
    {
        $this->name = $name;
    }

    /**
     * @return int|string|Option[]
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int|string|Option[] $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function addValueOption(self $option): void
    {
        if (!is_array($this->value)) {
            $this->value = [];
        }

        $this->value[] = $option;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title = null): void
    {
        $this->title = $title;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function setPlaceholder(string $placeholder = null): void
    {
        $this->placeholder = $placeholder;
    }

    public function getInfotext(): ?string
    {
        return $this->infoText;
    }

    public function setInfotext(string $infoText = null): void
    {
        $this->infoText = $infoText;
    }
}

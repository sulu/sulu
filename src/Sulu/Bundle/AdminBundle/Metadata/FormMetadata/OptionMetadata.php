<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use JMS\Serializer\Annotation as Serializer;

class OptionMetadata
{
    public const TYPE_STRING = 'string';

    public const TYPE_COLLECTION = 'collection';

    public const TYPE_EXPRESSION = 'expression';

    /**
     * @var null|string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var bool|int|string|OptionMetadata[]|null
     */
    protected $value;

    /**
     * @var array<string, string>
     */
    #[Serializer\Exclude]
    protected $titles = [];

    /**
     * @var array<string, string>
     */
    #[Serializer\Exclude]
    protected $placeholders = [];

    /**
     * @var array<string, string>
     */
    #[Serializer\Exclude]
    protected $infoTexts = [];

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

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool|int|string|OptionMetadata[]|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param bool|int|string|OptionMetadata[]|null $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function addValueOption(self $option): void
    {
        if (!\is_array($this->value)) {
            $this->value = [];
        }

        $this->value[] = $option;
    }

    public function getTitle(string $locale): ?string
    {
        if (\array_key_exists($locale, $this->titles)) {
            return $this->titles[$locale];
        }

        return \count($this->titles) ? $this->titles[\array_key_first($this->titles)] : null;
    }

    /**
     * @param array<string, string> $titles
     */
    public function setTitles(array $titles): void
    {
        $this->titles = $titles;
    }

    public function setTitle(string $title, string $locale)
    {
        $this->titles[$locale] = $title;
    }

    /**
     * @return array<string, string>
     */
    public function getTitles(): array
    {
        return $this->titles;
    }

    public function getInfotext(string $locale): ?string
    {
        if (\array_key_exists($locale, $this->infoTexts)) {
            return $this->infoTexts[$locale];
        }

        return \count($this->infoTexts) ? $this->infoTexts[\array_key_first($this->infoTexts)] : null;
    }

    /**
     * @param array<string, string> $infoTexts
     */
    public function setInfoTexts(array $infoTexts): void
    {
        $this->infoTexts = $infoTexts;
    }

    /**
     * @return array<string, string>
     */
    public function getInfoTexts(): array
    {
        return $this->infoTexts;
    }

    public function getPlaceholder(string $locale): ?string
    {
        if (\array_key_exists($locale, $this->placeholders)) {
            return $this->placeholders[$locale];
        }

        return \count($this->placeholders) ? $this->placeholders[\array_key_first($this->placeholders)] : null;
    }

    /**
     * @param array<string, string> $placeholders
     */
    public function setPlaceholders(array $placeholders): void
    {
        $this->placeholders = $placeholders;
    }

    /**
     * @return array<string, string>
     */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }
}

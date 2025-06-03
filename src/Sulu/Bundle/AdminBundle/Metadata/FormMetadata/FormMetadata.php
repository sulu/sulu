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

use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\AdminBundle\Metadata\AbstractMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

class FormMetadata extends AbstractMetadata
{
    /**
     * @var array<string, string>
     */
    #[Exclude]
    private $titles = [];

    /**
     * @var ItemMetadata[]
     */
    #[SerializedName('form')]
    #[Exclude(if: "'admin_form_metadata_keys_only' in context.getAttribute('groups')")]
    private $items = [];

    /**
     * @var SchemaMetadata
     */
    #[Exclude(if: "'admin_form_metadata_keys_only' in context.getAttribute('groups')")]
    private $schema;

    /**
     * @var TemplateMetadata|null
     */
    #[Exclude()]
    private $template;

    /**
     * @var string
     */
    #[Exclude(if: "'admin_form_metadata_keys_only' in context.getAttribute('groups')")]
    private $key;

    /**
     * @var TagMetadata[]
     */
    #[Exclude(if: "'admin_form_metadata_keys_only' in context.getAttribute('groups')")]
    protected $tags = [];

    /**
     * The resources from which this structure was loaded (useful for debugging).
     *
     * @var array<string>
     */
    #[Exclude()]
    protected $resources = [];

    public function __construct()
    {
        $this->schema = new SchemaMetadata();
    }

    /**
     * @deprecated since 3.0, use setName() instead
     */
    #[VirtualProperty]
    #[SerializedName('name')]
    #[Exclude(if: "'admin_form_metadata_keys_only' in context.getAttribute('groups')")]
    public function getName(): string
    {
        return $this->key;
    }

    public function setKey(string $key)
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setTitle(string $title, string $locale)
    {
        $this->titles[$locale] = $title;
    }

    /**
     * @param array<string, string> $titles
     */
    public function setTitles(array $titles)
    {
        $this->titles = $titles;
    }

    /**
     * @return array<string, string>
     */
    public function getTitles(): array
    {
        return $this->titles;
    }

    public function getTitle(string $locale): string
    {
        if (isset($this->titles[$locale])) {
            return $this->titles[$locale];
        }

        return \count($this->titles)
            ? $this->titles[\array_key_first($this->titles)]
            : ($this->key ? \ucfirst(\str_replace(['_', '-'], ' ', $this->key)) : '');
    }

    /**
     * @return ItemMetadata[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param ItemMetadata[] $items
     */
    public function setItems(array $items)
    {
        $this->items = $items;
    }

    public function addItem(ItemMetadata $item): void
    {
        $this->items[$item->getName()] = $item;
    }

    public function setSchema(SchemaMetadata $schema)
    {
        $this->schema = $schema;
    }

    public function getSchema(): SchemaMetadata
    {
        return $this->schema;
    }

    public function setTemplate(?TemplateMetadata $template)
    {
        $this->template = $template;
    }

    public function getTemplate(): ?TemplateMetadata
    {
        return $this->template;
    }

    /**
     * @return TagMetadata[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return TagMetadata[]
     */
    public function getTagsByName(string $name): array
    {
        $tags = [];
        foreach ($this->getTags() as $tag) {
            if ($tag->getName() === $name) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }

    public function hasTag(string $name): bool
    {
        foreach ($this->getTags() as $tag) {
            if ($tag->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    public function findTag(string $name): ?TagMetadata
    {
        foreach ($this->getTags() as $tag) {
            if ($tag->getName() === $name) {
                return $tag;
            }
        }

        return null;
    }

    public function addTag(TagMetadata $tag): void
    {
        $this->tags[] = $tag;
    }

    /**
     * @param TagMetadata[] $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function addResource(string $resource): void
    {
        $this->resources[] = $resource;
    }

    /**
     * @param array<string> $resources
     */
    public function setResources(array $resources): void
    {
        $this->resources = $resources;
    }

    /**
     * @return array<string>
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    public function merge(self $otherForm): FormMetadata
    {
        $mergedForm = new self();
        $mergedForm->setKey($this->getKey());
        if ($this->titles) {
            $mergedForm->setTitles($this->titles);
        }

        $mergedForm->setTags(\array_merge($this->getTags(), $otherForm->getTags()));
        $mergedForm->setItems(\array_merge($this->getItems(), $otherForm->getItems()));
        $mergedForm->setResources(\array_merge($this->getResources(), $otherForm->getResources()));
        $mergedForm->setSchema($this->getSchema()->merge($otherForm->getSchema()));

        return $mergedForm;
    }

    /**
     * @internal no backwards compatibility promise is given for this method it could be removed or changed at any time
     *
     * @return FieldMetadata[]
     */
    public function getFlatFieldMetadata(): array
    {
        $items = [];
        foreach ($this->getItems() as $item) {
            if ($item instanceof SectionMetadata) {
                foreach ($item->getFlatFieldMetadata() as $subItem) {
                    $items[] = $subItem;
                }
            } elseif ($item instanceof FieldMetadata) {
                $items[] = $item;
            }
        }

        return $items;
    }
}

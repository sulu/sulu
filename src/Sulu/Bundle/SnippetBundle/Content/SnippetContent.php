<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Content;

use PHPCR\NodeInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\PropertyInterface;
use PHPCR\PropertyType;
use Sulu\Component\Content\ContentTypeInterface;
use PHPCR\Util\UUIDHelper;
use Sulu\Component\Content\Structure\Snippet;

/**
 * ContentType for Snippets
 */
class SnippetContent extends ComplexContentType
{
    /**
     * @var ContentMapperInterface
     */
    protected $contentMapper;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var StructureResolverInterface
     */
    protected $structureResolver;

    /**
     * @var array
     */
    private $snippetCache = array();

    /**
     * Constructor
     */
    public function __construct(
        ContentMapperInterface $contentMapper,
        StructureResolverInterface $structureResolver,
        $template
    ) {
        $this->contentMapper = $contentMapper;
        $this->structureResolver = $structureResolver;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return ContentTypeInterface::PRE_SAVE;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set data to given property
     * @param array $data
     * @param PropertyInterface $property
     */
    protected function setData($data, PropertyInterface $property)
    {
        $refs = isset($data['ids']) ? $data['ids'] : array();
        $ids = array();
        if (is_array($refs)) {
            foreach ($refs as $i => $ref) {
                // see https://github.com/jackalope/jackalope/issues/248
                if (UUIDHelper::isUUID($i)) {
                    $ref = $i;
                }

                $ids[] = $ref;
            }
        }

        $data['ids'] = $ids;
        $property->setValue($data);
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $refs = $node->getPropertyValueWithDefault($property->getName(), array());
        $this->setData(array('ids' => $refs), $property);
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview($data, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $this->setData($data, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {

        $snippetReferences = array();
        $values = $property->getValue();

        $values = array_merge(
            array(
                'ids' => array(),
            ),
            $values
        );

        foreach ((array)$values['ids'] as $value) {
            if ($value instanceof Snippet) {
                $snippetReferences[] = $value->getUuid();
            } elseif (is_array($value) && array_key_exists('uuid', $value) && UUIDHelper::isUUID($value['uuid'])) {
                $snippetReferences[] = $value['uuid'];
            } elseif (UUIDHelper::isUUID($value)) {
                $snippetReferences[] = $value;
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Property value must either be a UUID or a Snippet, "%s" given.',
                        gettype($value)
                    )
                );
            }
        }

        $node->setProperty($property->getName(), $snippetReferences, PropertyType::REFERENCE);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultParams()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        $webspaceKey = $property->getStructure()->getWebspaceKey();
        $locale = $property->getStructure()->getLanguageCode();

        $refs = $property->getValue();
        $contentData = array();
        foreach ($this->loadSnippets($refs, $webspaceKey, $locale) as $snippet) {
            $contentData[] = $snippet['view'];
        }

        return $contentData;
    }

    /**
     * {@inheritDoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $webspaceKey = $property->getStructure()->getWebspaceKey();
        $locale = $property->getStructure()->getLanguageCode();

        $refs = $property->getValue();
        $contentData = array();
        foreach ($this->loadSnippets($refs, $webspaceKey, $locale) as $snippet) {
            $contentData[] = $snippet['content'];
        }

        return $contentData;
    }

    /**
     * load snippet and serialize them
     *
     * additionally cache it by id in this class
     */
    private function loadSnippets($ids, $webspaceKey, $locale)
    {
        $snippets = array();
        foreach ($ids as $i => $ref) {
            if (!array_key_exists($ref, $this->snippetCache)) {
                $snippet = $this->contentMapper->load($ref, $webspaceKey, $locale);
                $resolved = $this->structureResolver->resolve($snippet);
                $resolved['view']['template'] = $snippet->getKey();

                $this->snippetCache[$ref] = $resolved;
            }

            $snippets[] = $this->snippetCache[$ref];
        }

        return $snippets;
    }
}

<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Content\SmartContentContainer;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\PropertyInterface;

/**
 * ContentType for TextEditor
 */
class SmartContent extends ComplexContentType
{
    /**
     * @var NodeRepositoryInterface
     */
    private $nodeRepository;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var string
     */
    private $template;

    function __construct(NodeRepositoryInterface $nodeRepository, TagManagerInterface $tagManager, $template)
    {
        $this->nodeRepository = $nodeRepository;
        $this->tagManager = $tagManager;
        $this->template = $template;
    }

    /**
     * returns a template to render a form
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * returns type of ContentType
     * PRE_SAVE or POST_SAVE
     * @return int
     */
    public function getType()
    {
        return self::PRE_SAVE;
    }

    /**
     * @param PropertyInterface $property
     * @param $data
     */
    protected function setData($data, PropertyInterface $property)
    {
        $smartContent = new SmartContentContainer($this->nodeRepository, $this->tagManager);
        $smartContent->setConfig($data);
        $property->setValue($smartContent);
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey)
    {
        $data = json_decode($node->getPropertyValueWithDefault($property->getName(), '{}'), true);

        if (!empty($data['tags'])) {
            $data['tags'] = $this->tagManager->resolveTagIds($data['tags']);
        }

        $this->setData($data, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview($data, PropertyInterface $property, $webspaceKey)
    {
        $this->setData($data, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function write(NodeInterface $node, PropertyInterface $property, $userId, $webspaceKey)
    {
        $value = $property->getValue();

        if (!empty($value['tags'])) {
            $value['tags'] = $this->tagManager->resolveTagNames($value['tags']);
        }

        $node->setProperty($property->getName(), json_encode($value));
    }

    /**
     * remove property from given node
     * @param NodeInterface $node
     * @param PropertyInterface $property
     */
    public function remove(NodeInterface $node, PropertyInterface $property)
    {
        // TODO: Implement remove() method.
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\ContentTypeInterface;

/**
 * Content Type for target groups from the audience targeting.
 */
class AudienceTargetingGroups extends ComplexContentType implements ContentTypeExportInterface
{
    /**
     * Responsible for persisting the categories in the database.
     *
     * @var TargetGroupRepositoryInterface
     */
    private $targetGroupRepository;

    public function __construct(TargetGroupRepositoryInterface $targetGroupRepository)
    {
        $this->targetGroupRepository = $targetGroupRepository;
    }

    /**
     * returns type of ContentType
     * PRE_SAVE or POST_SAVE.
     *
     * @return int
     */
    public function getType()
    {
        return ContentTypeInterface::PRE_SAVE;
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $ids = $node->getPropertyValueWithDefault($property->getName(), []);
        $property->setValue($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $audienceTargetGroupIds = $property->getValue();
        if (!is_array($audienceTargetGroupIds) || empty($audienceTargetGroupIds)) {
            return [];
        }

        return $this->targetGroupRepository->findByIds($audienceTargetGroupIds);
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
        $node->setProperty($property->getName(), $property->getValue());
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
     * returns a template to render a form.
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'SuluAudienceTargetingBundle:Template:content-types/audience_targeting_groups.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function exportData($propertyValue)
    {
        if (is_array($propertyValue) && count($propertyValue) > 0) {
            return json_encode($propertyValue);
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $property->setValue(json_decode($value));
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\SmartContent;

use Sulu\Bundle\AudienceTargetingBundle\Rule\TargetGroupEvaluatorInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;

class QueryBuilder implements ContentQueryBuilderInterface
{
    /**
     * @var ContentQueryBuilderInterface
     */
    private $innerQueryBuilder;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var TargetGroupEvaluatorInterface
     */
    private $targetGroupEvaluator;

    /**
     * @var string
     */
    private $languageNamespace;

    /**
     * @param ContentQueryBuilderInterface $innerQueryBuilder
     * @param StructureManagerInterface $structureManager
     * @param TargetGroupEvaluatorInterface $targetGroupEvaluator
     * @param string $languageNamespace
     */
    public function __construct(
        ContentQueryBuilderInterface $innerQueryBuilder,
        StructureManagerInterface $structureManager,
        TargetGroupEvaluatorInterface $targetGroupEvaluator,
        $languageNamespace
    ) {
        $this->innerQueryBuilder = $innerQueryBuilder;
        $this->structureManager = $structureManager;
        $this->targetGroupEvaluator = $targetGroupEvaluator;
        $this->languageNamespace = $languageNamespace;
    }

    /**
     * Build query.
     *
     * @param string $webspaceKey
     * @param string[] $locales
     *
     * @return string
     */
    public function build($webspaceKey, $locales)
    {
        list($sql, $additionalFields) = $this->innerQueryBuilder->build($webspaceKey, $locales);

        $structure = $this->structureManager->getStructure('excerpt');

        $targetGroup = $this->targetGroupEvaluator->evaluate();

        if (!$targetGroup) {
            return [$sql, $additionalFields];
        }

        foreach ($locales as $locale) {
            $property = new TranslatedProperty(
                $structure->getProperty('audience_targeting_groups'),
                $locale,
                $this->languageNamespace,
                'excerpt'
            );
            $sql = str_replace(
                'WHERE ',
                'WHERE page.[' . $property->getName() . '] = ' . $targetGroup->getId() . ' AND ',
                $sql
            );
        }

        return [$sql, $additionalFields];
    }

    /**
     * initialize query builder.
     *
     * @param array $options
     */
    public function init(array $options)
    {
        return $this->innerQueryBuilder->init($options);
    }

    /**
     * Returns if unpublished pages are loaded.
     *
     * @return bool
     */
    public function getPublished()
    {
        return $this->innerQueryBuilder->getPublished();
    }
}

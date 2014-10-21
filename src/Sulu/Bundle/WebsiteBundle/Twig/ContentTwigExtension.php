<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig;

use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Provide Interface to load content
 */
class ContentTwigExtension extends \Twig_Extension
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    function __construct(
        ContentMapperInterface $contentMapper,
        StructureResolverInterface $structureResolver,
        RequestAnalyzerInterface $requestAnalyzer
    ) {
        $this->contentMapper = $contentMapper;
        $this->structureResolver = $structureResolver;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(new \Twig_SimpleFunction('content_load', array($this, 'load')));
    }

    /**
     * Returns resolved content for uuid
     * @param string $uuid
     * @param bool $loadGhostContent
     * @return array
     */
    public function load($uuid, $loadGhostContent = true)
    {
        $contentStructure = $this->contentMapper->load(
            $uuid,
            $this->requestAnalyzer->getCurrentWebspace()->getKey(),
            $this->requestAnalyzer->getCurrentLocalization()->getLocalization(),
            $loadGhostContent
        );

        return $this->structureResolver->resolve($contentStructure);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_website_content';
    }
}

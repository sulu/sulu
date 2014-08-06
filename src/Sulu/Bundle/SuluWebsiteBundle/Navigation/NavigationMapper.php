<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Navigation;

use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * {@inheritdoc}
 */
class NavigationMapper implements NavigationMapperInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    function __construct(ContentMapperInterface $contentMapper)
    {
        $this->contentMapper = $contentMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigation($parent, $webspace, $language, $depth = 1)
    {
        $contents = $this->contentMapper->loadByParent($parent, $webspace, $language, $depth, false, true, true);

        return $this->generateNavigation($contents, $webspace, $language);
    }

    /**
     * {@inheritdoc}
     */
    public function getMainNavigation($webspace, $language, $depth = 1)
    {
        return $this->getNavigation(null, $webspace, $language, $depth);
    }

    /**
     * {@inheritdoc}
     */
    public function getBreadcrumb($uuid, $webspace, $language)
    {
        $breadcrumbItems = $this->contentMapper->loadBreadcrumb($uuid, $language, $webspace);

        $result = array();
        foreach ($breadcrumbItems as $item) {
            $result[] = $this->contentMapper->load($item->getUuid(), $webspace, $language);
        }
        $result[] = $this->contentMapper->load($uuid, $webspace, $language);

        return $this->generateNavigation($result, $webspace, $language);
    }

    /**
     * @param StructureInterface[] $contents
     * @param string $webspace
     * @param string $language
     * @return NavigationItem[]
     */
    private function generateNavigation($contents, $webspace, $language)
    {
        $result = array();

        foreach ($contents as $content) {
            $children = array();
            if (is_array($content->getChildren()) && sizeof($content->getChildren()) > 0) {
                $children = $this->generateNavigation($content->getChildren(), $webspace, $language);
            }
            if ($content->getPublishedState() && $content->getNavigation() !== false) {
                $url = $content->getResourceLocator();
                $title = $content->getNodeName();

                $result[] = new NavigationItem(
                    $content, $title, $url, $children, $content->getUuid(), $content->getNodeType()
                );
            }
        }

        return $result;
    }
}

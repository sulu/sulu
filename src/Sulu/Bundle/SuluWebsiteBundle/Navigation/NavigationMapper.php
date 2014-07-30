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
use Sulu\Component\Content\StructureInterface;

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
     * returns navigation for given parent
     * @param string $parent uuid of parent node
     * @param $webspace
     * @param $language
     * @param int $depth
     * @param boolean $preview
     * @return NavigationItem[]
     */
    public function getNavigation($parent, $webspace, $language, $depth = 1, $preview = false)
    {
        $contents = $this->contentMapper->loadByParent($parent, $webspace, $language, $depth, false, true, true);

        return $this->generateNavigation($contents, $preview);
    }

    /**
     * returns navigation from root
     * @param int $depth
     * @param string $webspace
     * @param string $language
     * @param int $depth
     * @param boolean $preview
     * @return NavigationItem[]
     */
    public function getMainNavigation($webspace, $language, $depth = 1, $preview = false)
    {
        return $this->getNavigation(null, $webspace, $language, $depth, $preview);
    }

    /**
     * @param StructureInterface[] $contents
     * @param boolean $preview
     * @return NavigationItem[]
     */
    private function generateNavigation($contents, $preview)
    {
        $result = array();

        foreach ($contents as $content) {
            $children = array();
            if (is_array($content->getChildren()) && sizeof($content->getChildren()) > 0) {
                $children = $this->generateNavigation($content->getChildren(), $preview);
            }
            if (
                ($preview || ($content->getPublishedState() && $content->getNavigation() !== false)) &&
                $content->hasProperty('sulu.rlp')
            ) {
                $url = $content->getPropertyByTagName('sulu.rlp')->getValue();
                $title = $content->getPropertyByTagName('sulu.node.name')->getValue();
                $result[] = new NavigationItem(
                    $content, $title, $url, $children, $content->getUuid()
                );
            }
        }

        return $result;
    }
}

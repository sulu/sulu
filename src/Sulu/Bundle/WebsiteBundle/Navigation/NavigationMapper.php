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

        return $this->generateNavigation($contents, $preview, $webspace, $language);
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
     * @param string $webspace
     * @param string $language
     * @return NavigationItem[]
     */
    private function generateNavigation($contents, $preview, $webspace, $language)
    {
        $result = array();

        foreach ($contents as $content) {
            $children = array();
            if (is_array($content->getChildren()) && sizeof($content->getChildren()) > 0) {
                $children = $this->generateNavigation($content->getChildren(), $preview, $webspace, $language);
            }
            if (($preview || ($content->getPublishedState() && $content->getNavigation() !== false))) {
                $url = $content->getPropertyByTagName('sulu.rlp')->getValue();
                $title = $content->getPropertyByTagName('sulu.node.name')->getValue();

                // FIXME copy from ContentPathTwigExtension (centralize in a own service)
                if ($content->getNodeType() === Structure::NODE_TYPE_EXTERNAL_LINK) {
                    // FIXME URL schema
                    $url = 'http://' . $url;
                } elseif ($content->getNodeType() === Structure::NODE_TYPE_INTERNAL_LINK) {
                    $linkPage = $this->contentMapper->load($url, $webspace, $language);
                    $url = $linkPage->getPropertyValueByTagName('sulu.rlp');
                    $title = $linkPage->getPropertyByTagName('sulu.node.name')->getValue();
                }
                $result[] = new NavigationItem(
                    $content, $title, $url, $children, $content->getUuid()
                );
            }
        }

        return $result;
    }
}

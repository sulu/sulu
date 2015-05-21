<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure;

use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Type;
use Sulu\Component\Content\PageInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Util\ArrayableInterface;

/**
 * This structure represents a page in the CMS.
 */
abstract class Page extends Structure implements PageInterface
{
    /**
     * template to render content.
     *
     * @var string
     * @Type("string")
     */
    private $view;

    /**
     * controller to render content.
     *
     * @var string
     * @Type("string")
     */
    private $controller;

    /**
     * time to cache content.
     *
     * @var int
     * @Type("integer")
     */
    private $cacheLifeTime;

    /**
     * defines in which navigation context assigned.
     *
     * @var string[]
     * @Type("array")
     */
    private $navContexts;

    /**
     * state of node.
     *
     * @var int
     * @Type("integer")
     */
    private $nodeState;

    /**
     * @var array
     * @Type("array")
     */
    private $ext = array();

    /**
     * @var string
     * @Type("string")
     */
    private $originTemplate;

    /**
     * content node that holds the internal link.
     *
     * @var StructureInterface
     * @Exclude()
     */
    private $internalLinkContent;

    /**
     * @var string[]
     * @Type("array")
     */
    private $urls;

    /**
     * @param $key string
     * @param $view string
     * @param $controller string
     * @param int $cacheLifeTime
     * @param array $metaData
     */
    public function __construct($key, $view, $controller, $cacheLifeTime = 604800, $metaData = array())
    {
        parent::__construct($key, $metaData);

        $this->view = $view;
        $this->controller = $controller;
        $this->cacheLifeTime = $cacheLifeTime;

        // default state is test
        $this->nodeState = StructureInterface::STATE_TEST;

        // default hide in navigation
        $this->navContexts = array();
    }

    /**
     * @return string
     */
    public function getOriginTemplate()
    {
        return $this->originTemplate;
    }

    /**
     * @param string $originTemplate
     */
    public function setOriginTemplate($originTemplate)
    {
        $this->originTemplate = $originTemplate;
    }

    /**
     * twig template of template definition.
     *
     * @return string
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * controller which renders the template definition.
     *
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * cacheLifeTime of template definition.
     *
     * @return int
     */
    public function getCacheLifeTime()
    {
        return $this->cacheLifeTime;
    }

    /**
     * @param int $state
     *
     * @return int
     */
    public function setNodeState($state)
    {
        $this->nodeState = $state;
    }

    /**
     * returns state of node.
     *
     * @return int
     */
    public function getNodeState()
    {
        return $this->nodeState;
    }

    /**
     * returns true if this node is shown in navigation.
     *
     * @return string[]
     */
    public function getNavContexts()
    {
        return $this->navContexts;
    }

    /**
     * @param string[] $navContexts
     */
    public function setNavContexts($navContexts)
    {
        $this->navContexts = $navContexts;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceLocator()
    {
        if (
            $this->getNodeType() === Structure::NODE_TYPE_INTERNAL_LINK &&
            $this->getInternalLinkContent() !== null &&
            $this->getInternalLinkContent()->hasTag('sulu.rlp')
        ) {
            return $this->getInternalLinkContent()->getPropertyValueByTagName('sulu.rlp');
        } elseif ($this->getNodeType() === Structure::NODE_TYPE_EXTERNAL_LINK) {
            // FIXME URL schema
            return 'http://' . $this->getPropertyByTagName('sulu.rlp')->getValue();
        } elseif ($this->hasTag('sulu.rlp')) {
            return $this->getPropertyValueByTagName('sulu.rlp');
        }

        return;
    }

    /**
     * @return string[]
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @param string[] $resourceLocators
     */
    public function setUrls($resourceLocators)
    {
        $this->urls = $resourceLocators;
    }

    /**
     * returns an array of property value pairs.
     *
     * @param bool $complete True if result should be representation of full node
     *
     * @return array
     */
    public function toArray($complete = true)
    {
        $result = array_merge(
            parent::toArray($complete),
            array(
                'path' => $this->path,
                'nodeState' => $this->getNodeState(),
                'publishedState' => $this->getPublishedState(),
                'navContexts' => $this->getNavContexts(),
                'originTemplate' => $this->getOriginTemplate(),
            )
        );

        if ($complete === true) {
            $result['published'] = $this->getPublished();
            $result['ext'] = $this->extToArray();
        }

        return $result;
    }

    /**
     * @return StructureInterface
     */
    public function getInternalLinkContent()
    {
        return $this->internalLinkContent;
    }

    /**
     * @param StructureInterface $internalLinkContent
     */
    public function setInternalLinkContent($internalLinkContent)
    {
        $this->internalLinkContent = $internalLinkContent;
    }

    private function extToArray()
    {
        $result = array();
        foreach ($this->ext as $key => $value) {
            if ($value instanceof ArrayableInterface) {
                $result[$key] = $value->toArray();
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getExt()
    {
        return $this->ext;
    }

    /**
     * {@inheritDoc}
     */
    public function setExt($ext)
    {
        $this->ext = $ext;
    }

    public function copyFrom(StructureInterface $structure)
    {
        if ($structure instanceof self) {
            $this->setNodeState($structure->getNodeState());
            $this->setUrls($structure->getUrls());
            $this->setExt($structure->getExt());
            $this->setOriginTemplate($structure->getOriginTemplate());
            $this->setIsShadow($structure->getIsShadow());
            $this->setShadowBaseLanguage($structure->getShadowBaseLanguage());
            $this->setConcreteLanguages($structure->getConcreteLanguages());
        }

        parent::copyFrom($structure);
    }
}

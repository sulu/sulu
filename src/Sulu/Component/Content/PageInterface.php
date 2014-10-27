<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use DateTime;
use Sulu\Component\Content\StructureExtension\StructureExtensionInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Structure for template
 */
interface PageInterface extends StructureInterface
{
    /**
     * twig template of template definition
     * @return string
     */
    public function getView();

    /**
     * controller which renders the template definition
     * @return string
     */
    public function getController();

    /**
     * cacheLifeTime of template definition
     * @return int
     */
    public function getCacheLifeTime();

    /**
     * @return string
     */
    public function getOriginTemplate();

    /**
     * @param string $originTemplate
     */
    public function setOriginTemplate($originTemplate);

    /**
     * returns true if this node is shown in navigation
     * @return string[]
     */
    public function getNavContexts();

    /**
     * @param string[] $navContexts
     */
    public function setNavContexts($navContexts);

    /**
     * @return StructureExtensionInterface[]
     */
    public function getExt();

    /**
     * @param $data
     * @return array
     */
    public function setExt($data);

    /**
     * returns content node that holds the internal link
     * @return StructureInterface
     */
    public function getInternalLinkContent();

    /**
     * set content node that holds the internal link
     * @param StructureInterface $internalLinkContent
     */
    public function setInternalLinkContent($internalLinkContent);

    /**
     * @return boolean
     */
    public function getInternal();

    /**
     * @param boolean $internal
     */
    public function setInternal($internal);

    /**
     * returns state of node
     * @return int
     */
    public function getNodeState();

    /**
     * @param int $state
     * @return int
     */
    public function setNodeState($state);
}

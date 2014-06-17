<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use PHPCR\NodeInterface;

/**
 * interface for content mapper extension
 * @package Sulu\Component\Content\Mapper
 */
interface ContentMapperExtensionInterface
{

    /**
     * set current language code to translates properties
     * @param $languageCode
     * @param $languageNamespace
     * @param $namespace
     */
    public function setLanguageCode($languageCode, $languageNamespace, $namespace);

    /**
     * save data to node
     * @param NodeInterface $node
     * @param $webspaceKey
     * @param $languageCode
     * @return mixed
     */
    public function save(NodeInterface $node, $webspaceKey, $languageCode);

    /**
     * load data from node
     * @param NodeInterface $node
     * @param string $webspaceKey
     * @param string $languageCode
     * @return mixed
     */
    public function load(NodeInterface $node, $webspaceKey, $languageCode);

}

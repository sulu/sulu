<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Template;

/**
 * Interface for Template resolver
 * @package Sulu\Component\Content\Template
 */
interface TemplateResolverInterface
{
    /**
     * Resolves template for different node types
     * @param integer $nodeType
     * @param string $templateKey
     * @return string
     */
    public function resolve($nodeType, $templateKey);
} 

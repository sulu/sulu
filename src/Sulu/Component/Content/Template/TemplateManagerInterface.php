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

use Sulu\Component\Content\StructureInterface;

interface TemplateManagerInterface
{
    /**
     * Returns a list of existing templates
     * @return string[]
     */
    public function getTemplates();

    /**
     * Returns all templates implemented in the given template
     * @param string $theme
     * @return string[]
     */
    public function getTemplatesByTheme($theme);

    /**
     * Dump template with given key
     * @param string $key
     * @return StructureInterface
     */
    public function dump($key);

    /**
     * Dumps all existing Templates to Structure cache classes
     * @return StructureInterface[]
     */
    public function dumpAll();

    /**
     * Dumps given Templates to Structure cache classes
     * @param string[] $templates
     * @return StructureInterface[]
     */
    public function dumpTemplates($templates);
} 

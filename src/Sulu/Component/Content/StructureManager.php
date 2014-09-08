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

use Sulu\Component\Content\StructureExtension\StructureExtensionInterface;
use Sulu\Component\Content\Template\TemplateManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * generates subclasses of structure to match template definitions.
 * this classes will be cached in Symfony cache
 */
class StructureManager implements StructureManagerInterface
{
    /**
     * @var TemplateManagerInterface
     */
    private $templateManager;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * contains all extension
     * @var array
     */
    private $extensions = array();

    function __construct(TemplateManagerInterface $templateManager, WebspaceManagerInterface $webspaceManager)
    {
        $this->templateManager = $templateManager;
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getStructure($key)
    {
        return $this->templateManager->dump($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getStructures($wespaceKey = null)
    {
        if ($wespaceKey === null) {
            return $this->templateManager->dumpAll();
        } else {
            $templates = $this->webspaceManager->findWebspaceByKey($wespaceKey)->getTemplates();

            return $this->templateManager->dumpTemplates($templates);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addExtension(StructureExtensionInterface $extension, $template = 'all')
    {
        if (!isset($this->extensions[$template])) {
            $this->extensions[$template] = array();
        }

        $this->extensions[$template][] = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions($key)
    {
        $extensions = isset($this->extensions['all']) ? $this->extensions['all'] : array();
        if (isset($this->extensions[$key])) {
            $extensions = array_merge($extensions, $this->extensions[$key]);
        }

        return $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function hasExtension($key, $name)
    {
        $extensions = $this->getExtensions($key);

        return array_key_exists($name, $extensions);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension($key, $name)
    {
        $extensions = $this->getExtensions($key);

        return $extensions[$name];
    }
}

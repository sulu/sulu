<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\StructureProvider;

use Doctrine\Common\Cache\Cache;
use Liip\ThemeBundle\ActiveTheme;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Provide templates which are implemented in a single webspace.
 */
class WebspaceStructureProvider implements WebspaceStructureProviderInterface
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ActiveTheme
     */
    private $activeTheme;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param \Twig_Environment         $twig
     * @param StructureManagerInterface $structureManager
     * @param WebspaceManagerInterface  $webspaceManager
     * @param ActiveTheme               $activeTheme
     * @param Cache                     $cache
     */
    public function __construct(
        \Twig_Environment $twig,
        StructureManagerInterface $structureManager,
        WebspaceManagerInterface $webspaceManager,
        ActiveTheme $activeTheme,
        Cache $cache
    ) {
        $this->twig = $twig;
        $this->structureManager = $structureManager;
        $this->webspaceManager = $webspaceManager;
        $this->activeTheme = $activeTheme;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getStructures($webspaceKey)
    {
        if (!$this->cache->contains($webspaceKey)) {
            return $this->loadStructures($webspaceKey);
        }

        $keys = $this->cache->fetch($webspaceKey);

        return array_map(
            function ($key) {
                return $this->structureManager->getStructure($key);
            },
            $keys
        );
    }

    private function loadStructures($webspaceKey)
    {
        $before = $this->activeTheme->getName();
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $this->activeTheme->setName($webspace->getTheme()->getKey());

        $structures = [];
        $keys = [];
        foreach ($this->structureManager->getStructures() as $page) {
            /* @var PageBridge $page */
            $template = sprintf('%s.html.twig', $page->getView());
            if ($this->templateExists($template)) {
                $keys[] = $page->getKey();
                $structures[] = $page;
            }
        }
        $this->activeTheme->setName($before);

        $this->cache->save($webspaceKey, $keys);

        return $structures;
    }

    /**
     * checks if a template with given name exists.
     *
     * @param string $template
     *
     * @return bool
     */
    protected function templateExists($template)
    {
        $loader = $this->twig->getLoader();
        if ($loader instanceof \Twig_ExistsLoaderInterface) {
            return $loader->exists($template);
        }

        try {
            $loader->getSource($template);

            return true;
        } catch (\Twig_Error_Loader $e) {
            return false;
        }
    }
}

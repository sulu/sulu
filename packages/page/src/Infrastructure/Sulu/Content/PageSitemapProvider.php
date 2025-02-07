<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Infrastructure\Sulu\Sitemap\ContentSitemapProvider;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;

/**
 * @extends ContentSitemapProvider<PageDimensionContentInterface, PageInterface>
 */
class PageSitemapProvider extends ContentSitemapProvider
{
    public function __construct(EntityManagerInterface $entityManager, WebspaceManagerInterface $webspaceManager, string $kernelEnvironment, string $contentRichEntityClass, string $routeClass, string $alias)
    {
        parent::__construct($entityManager, $webspaceManager, $kernelEnvironment, $contentRichEntityClass, $routeClass, $alias);
    }

    protected function getEntityIdField(): string
    {
        return 'uuid';
    }
}

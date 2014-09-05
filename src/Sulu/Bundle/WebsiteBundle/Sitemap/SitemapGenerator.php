<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

use Jackalope\Query\Row;
use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryResultInterface;
use PHPCR\RepositoryException;
use Sulu\Bundle\WebsiteBundle\ContentQuery\ContentQueryBuilderInterface;
use Sulu\Bundle\WebsiteBundle\ContentQuery\ContentQueryInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Template\TemplateResolverInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Generates a sitemap structure for xml or html
 */
class SitemapGenerator implements SitemapGeneratorInterface
{
    /**
     * @var ContentQueryInterface
     */
    private $contentQuery;

    /**
     * @var  WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ContentQueryBuilderInterface
     */
    private $contentQueryBuilder;

    function __construct(
        ContentQueryInterface $contentQuery,
        WebspaceManagerInterface $webspaceManager,
        ContentQueryBuilderInterface $contentQueryBuilder
    ) {
        $this->contentQuery = $contentQuery;
        $this->webspaceManager = $webspaceManager;
        $this->contentQueryBuilder = $contentQueryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function generateAllLocals($webspaceKey, $flat = false)
    {
        $locales = array();
        foreach ($this->webspaceManager->findWebspaceByKey($webspaceKey)->getAllLocalizations() as $localizations) {
            $locales[] = $localizations->getLocalization();
        }

        return $this->contentQuery->execute($webspaceKey, $locales, $this->contentQueryBuilder, $flat);
    }

    /**
     * {@inheritdoc}
     */
    public function generate($webspaceKey, $locale, $flat = false)
    {
        return $this->contentQuery->execute($webspaceKey, array($locale), $this->contentQueryBuilder, $flat);
    }
}

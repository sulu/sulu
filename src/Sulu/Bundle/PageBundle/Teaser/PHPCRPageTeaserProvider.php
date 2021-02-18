<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Teaser;

use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Contracts\Translation\TranslatorInterface;

class PHPCRPageTeaserProvider implements TeaserProviderInterface
{
    /**
     * @var ContentQueryExecutorInterface
     */
    private $contentQueryExecutor;

    /**
     * @var ContentQueryBuilderInterface
     */
    private $contentQueryBuilder;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var bool
     */
    private $showDrafts;

    /**
     * @var array<string, int>
     */
    private $permissions;

    /**
     * @param bool $showDrafts Parameter "sulu_document_manager.show_drafts"
     * @param array<string, int> $permissions Parameter "sulu_security.permissions"
     */
    public function __construct(
        ContentQueryExecutorInterface $contentQueryExecutor,
        ContentQueryBuilderInterface $contentQueryBuilder,
        TranslatorInterface $translator,
        bool $showDrafts,
        array $permissions
    ) {
        $this->contentQueryExecutor = $contentQueryExecutor;
        $this->contentQueryBuilder = $contentQueryBuilder;
        $this->translator = $translator;
        $this->showDrafts = $showDrafts;
        $this->permissions = $permissions;
    }

    public function getConfiguration(): TeaserConfiguration
    {
        return new TeaserConfiguration(
            $this->translator->trans('sulu_page.page', [], 'admin'),
            'pages',
            'column_list',
            ['title'],
            $this->translator->trans('sulu_page.single_selection_overlay_title', [], 'admin'),
            PageAdmin::EDIT_FORM_VIEW,
            ['id' => 'id', 'attributes/webspaceKey' => 'webspace']
        );
    }

    /**
     * @param string[] $ids
     * @param string $locale
     *
     * @return Teaser[]
     */
    public function find(array $ids, $locale, ?string $webspaceKey = null): array
    {
        if (null === $webspaceKey) {
            throw new \InvalidArgumentException(
                'The "PHPCRPageTeaserProvider" requires as $webspaceKey to work as expected'
            );
        }

        if (0 === \count($ids)) {
            return [];
        }

        $pages = $this->loadPages($ids, $locale, $webspaceKey);
        $result = [];

        foreach ($pages as $pageData) {
            $result[] = new Teaser(
                $pageData['id'],
                'pages',
                $locale,
                ($pageData['excerptTitle'] ?? null) ?: $pageData['title'] ?? null,
                $pageData['excerptDescription'] ?? null,
                $pageData['excerptMore'] ?? null,
                $pageData['url'] ?? null,
                ($media = $pageData['excerptImages'][0] ?? null) ? $media->getId() : null,
                $this->getAttributes($pageData)
            );
        }

        return $result;
    }

    /**
     * @param string[] $ids
     * @param string $locale
     *
     * @return mixed[]
     */
    protected function loadPages(array $ids, $locale, string $webspaceKey): array
    {
        $this->contentQueryBuilder->init(
            [
                'ids' => $ids,
                'properties' => [
                    new PropertyParameter('excerptTitle', 'excerpt.title'),
                    new PropertyParameter('excerptDescription', 'excerpt.description'),
                    new PropertyParameter('excerptMore', 'excerpt.more'),
                    new PropertyParameter('excerptImages', 'excerpt.images'),
                ],
                'published' => !$this->showDrafts,
            ]
        );

        return $this->contentQueryExecutor->execute(
            $webspaceKey,
            [$locale],
            $this->contentQueryBuilder,
            true,
            -1,
            null,
            null,
            false,
            $this->permissions[PermissionTypes::VIEW]
        );
    }

    /**
     * @param array<string, mixed> $pageData
     *
     * @return array<string, mixed>
     */
    protected function getAttributes(array $pageData): array
    {
        return [
            'structureType' => $pageData['template'] ?? null,
            'webspaceKey' => $pageData['webspaceKey'] ?? null,
        ];
    }
}

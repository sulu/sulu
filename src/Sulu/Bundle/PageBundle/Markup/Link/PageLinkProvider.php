<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Markup\Link;

use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageLinkProvider implements LinkProviderInterface
{
    public function __construct(
        protected ContentRepositoryInterface $contentRepository,
        protected WebspaceManagerInterface $webspaceManager,
        protected RequestStack $requestStack,
        private TranslatorInterface $translator,
        protected string $environment,
        private AccessControlManagerInterface $accessControlManager,
        private ?TokenStorageInterface $tokenStorage = null
    ) {
    }

    public function getConfiguration()
    {
        /** @var LinkConfigurationBuilder $configurationBuilder */
        $configurationBuilder = LinkConfigurationBuilder::create()
            ->setTitle($this->translator->trans('sulu_page.pages', [], 'admin'))
            ->setResourceKey('pages')
            ->setListAdapter('column_list')
            ->setDisplayProperties(['title'])
            ->setOverlayTitle($this->translator->trans('sulu_page.single_selection_overlay_title', [], 'admin'))
            ->setEmptyText($this->translator->trans('sulu_page.no_page_selected', [], 'admin'))
            ->setIcon('su-document');

        return $configurationBuilder;
    }

    public function preload(array $hrefs, $locale, $published = true)
    {
        $request = $this->requestStack->getCurrentRequest();
        $scheme = 'http';
        $domain = null;
        if ($request) {
            $scheme = $request->getScheme();
            $domain = $request->getHost();
        }

        $contents = $this->contentRepository->findByUuids(
            \array_unique(\array_values($hrefs)),
            $locale,
            MappingBuilder::create()
                ->setResolveUrl(true)
                ->addProperties(['title', 'published'])
                ->setOnlyPublished($published)
                ->setHydrateGhost(false)
                ->getMapping()
        );

        $contents = \array_filter($contents, function(Content $content) {
            $webspaceKey = $content->getWebspaceKey();
            $targetWebspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
            $security = $targetWebspace->getSecurity();
            $system = $security ? $security->getSystem() : null;

            if (!$targetWebspace->hasWebsiteSecurity()) {
                return true;
            }

            $userPermissions = $this->accessControlManager->getUserPermissionByArray(
                $content->getLocale(),
                PageAdmin::SECURITY_CONTEXT_PREFIX . $webspaceKey,
                $content->getPermissions(),
                $this->getCurrentUser(),
                $system
            );

            return !isset($userPermissions['view']) || $userPermissions['view'];
        });

        return \array_map(
            function(Content $content) use ($locale, $scheme, $domain) {
                return $this->getLinkItem($content, $locale, $scheme, $domain);
            },
            $contents
        );
    }

    /**
     * Returns new link item.
     *
     * @param string $locale
     * @param string $scheme
     *
     * @return LinkItem
     */
    protected function getLinkItem(Content $content, $locale, $scheme, $domain = null)
    {
        $published = !empty($content->getPropertyWithDefault('published'));
        $url = $this->webspaceManager->findUrlByResourceLocator(
            $content->getUrl(),
            $this->environment,
            $locale,
            $content->getWebspaceKey(),
            $domain,
            $scheme
        );

        return new LinkItem($content->getId(), $content->getPropertyWithDefault('title'), $url, $published);
    }

    /**
     * Returns current user or null if no user is loggedin.
     *
     * @return UserInterface|null
     */
    private function getCurrentUser()
    {
        if (!$this->tokenStorage) {
            return null;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            return $user;
        }

        return null;
    }
}

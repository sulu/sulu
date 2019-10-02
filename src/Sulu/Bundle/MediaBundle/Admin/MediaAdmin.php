<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Localization\Manager\LocalizationManager;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MediaAdmin extends Admin
{
    const SECURITY_CONTEXT = 'sulu.media.collections';

    const MEDIA_OVERVIEW_VIEW = 'sulu_media.overview';

    const EDIT_FORM_VIEW = 'sulu_media.form';

    const EDIT_FORM_DETAILS_VIEW = 'sulu_media.form.details';

    const EDIT_FORM_FORMATS_VIEW = 'sulu_media.form.formats';

    const EDIT_FORM_HISTORY_VIEW = 'sulu_media.form.history';

    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker,
        LocalizationManager $localizationManager,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
        $this->localizationManager = $localizationManager;
        $this->urlGenerator = $urlGenerator;
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $media = new NavigationItem('sulu_media.media');
            $media->setPosition(30);
            $media->setIcon('su-image');
            $media->setView(static::MEDIA_OVERVIEW_VIEW);
            $media->addChildView(static::EDIT_FORM_VIEW);
            $media->addChildView(static::EDIT_FORM_DETAILS_VIEW);
            $media->addChildView(static::EDIT_FORM_FORMATS_VIEW);
            $media->addChildView(static::EDIT_FORM_HISTORY_VIEW);

            $navigationItemCollection->add($media);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureViews(ViewCollection $viewCollection): void
    {
        $mediaLocales = $this->localizationManager->getLocales();

        $toolbarActions = [];

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $toolbarActions[] = new ToolbarAction('sulu_admin.save');
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE)) {
            $toolbarActions[] = new ToolbarAction('sulu_admin.delete');
        }

        if ($this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createViewBuilder(
                        static::MEDIA_OVERVIEW_VIEW,
                        '/collections/:locale/:id?',
                        'sulu_media.overview'
                    )
                    ->setOption('locales', $mediaLocales)
                    ->setOption('permissions', [
                        'add' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::ADD),
                        'delete' => $this->securityChecker->hasPermission(
                            static::SECURITY_CONTEXT,
                            PermissionTypes::DELETE
                        ),
                        'edit' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT),
                    ])
                    ->setAttributeDefault('locale', $mediaLocales[0])
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createResourceTabViewBuilder(static::EDIT_FORM_VIEW, '/media/:locale/:id')
                    ->setResourceKey('media')
                    ->addLocales($mediaLocales)
                    ->setTitleProperty('title')
            );
            $viewCollection->add(
                $this->viewBuilderFactory->createFormViewBuilder(static::EDIT_FORM_DETAILS_VIEW, '/details')
                    ->setResourceKey('media')
                    ->setFormKey('media_details')
                    ->setTabTitle('sulu_media.information_taxonomy')
                    ->addToolbarActions($toolbarActions)
                    ->setParent(static::EDIT_FORM_VIEW)
                    ->setBackView(static::MEDIA_OVERVIEW_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createViewBuilder(static::EDIT_FORM_FORMATS_VIEW, '/formats', 'sulu_media.formats')
                    ->setOption('tabTitle', 'sulu_media.formats')
                    ->setParent(static::EDIT_FORM_VIEW)
            );
            $viewCollection->add(
                $this->viewBuilderFactory
                    ->createViewBuilder(static::EDIT_FORM_HISTORY_VIEW, '/history', 'sulu_media.history')
                    ->setOption('tabTitle', 'sulu_media.history')
                    ->setParent(static::EDIT_FORM_VIEW)
            );
        }
    }

    public function getSecurityContexts()
    {
        return [
            'Sulu' => [
                'Media' => [
                    static::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                        PermissionTypes::SECURITY,
                    ],
                    'sulu.media.system_collections' => [
                        PermissionTypes::VIEW,
                    ],
                ],
            ],
        ];
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_media';
    }

    public function getConfig(): ?array
    {
        return [
            'endpoints' => [
                'image_format' => $this->urlGenerator->generate(
                    'sulu_media.redirect',
                    ['id' => ':id']
                ),
            ],
            'media_permissions' => [
                'add' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::ADD),
                'delete' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::DELETE),
                'edit' => $this->securityChecker->hasPermission(static::SECURITY_CONTEXT, PermissionTypes::EDIT),
                'security' => $this->securityChecker->hasPermission(
                    static::SECURITY_CONTEXT,
                    PermissionTypes::SECURITY
                ),
            ],
        ];
    }
}

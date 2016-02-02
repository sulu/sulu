<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class MediaContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @param SecurityCheckerInterface $securityChecker
     */
    public function __construct(SecurityCheckerInterface $securityChecker)
    {
        $this->securityChecker = $securityChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = [])
    {
        $files = new ContentNavigationItem('content-navigation.media.files');
        $files->setAction('files');
        $files->setPosition(10);
        $files->setComponent('collections/edit/files@sulumedia');

        return [$files];
    }
}

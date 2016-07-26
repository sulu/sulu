<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\JsConfigInterface;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;

/**
 * Provides id for avatar-collection.
 */
class JsConfigAvatarCollection implements JsConfigInterface
{
    /**
     * @var SystemCollectionManagerInterface
     */
    private $systemCollectionManager;

    public function __construct(SystemCollectionManagerInterface $systemCollectionManager)
    {
        $this->systemCollectionManager = $systemCollectionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return [
            'contactAvatarCollection' => $this->systemCollectionManager->getSystemCollection('sulu_contact.contact'),
            'accountAvatarCollection' => $this->systemCollectionManager->getSystemCollection('sulu_contact.account'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu.contact.form';
    }
}

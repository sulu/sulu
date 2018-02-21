<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Component\HttpCache\HandlerInvalidateReferenceInterface;

/**
 * Invalidate references when account/contact are persisted.
 */
class CacheInvalidationListener
{
    /**
     * @var HandlerInvalidateReferenceInterface
     */
    private $invalidationHandler;

    public function __construct(HandlerInvalidateReferenceInterface $invalidationHandler)
    {
        $this->invalidationHandler = $invalidationHandler;
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $object = $eventArgs->getObject();
        if ($object instanceof MediaInterface) {
            $this->invalidate($object);
        } elseif ($object instanceof File) {
            $this->invalidate($object->getMedia());
        } elseif ($object instanceof FileVersion) {
            $this->invalidate($object->getFile()->getMedia());
        } elseif ($object instanceof FileVersionMeta) {
            $this->invalidate($object->getFileVersion()->getFile()->getMedia());
        }
    }

    private function invalidate(MediaInterface $media)
    {
        $this->invalidationHandler->invalidateReference('media', $media->getId());
    }
}

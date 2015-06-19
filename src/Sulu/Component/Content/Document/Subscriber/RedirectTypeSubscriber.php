<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\PropertyType;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;

class RedirectTypeSubscriber implements EventSubscriberInterface
{
    const REDIRECT_TYPE_FIELD = 'nodeType';
    const INTERNAL_FIELD = 'internal_link';
    const EXTERNAL_FIELD = 'external';

    public static function getSubscribedEvents()
    {
        return [
            Events::METADATA_LOAD => 'handleMetadataLoad',
        ];
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectionClass()->isSubclassOf(RedirectTypeBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('redirectType', array(
            'encoding' => 'system_localized',
            'property' => self::REDIRECT_TYPE_FIELD,
            'default' => RedirectType::NONE,
        ));
        $metadata->addFieldMapping('redirectExternal', array(
            'encoding' => 'system_localized',
            'property' => self::EXTERNAL_FIELD,
        ));
        $metadata->addFieldMapping('redirectTarget', array(
            'encoding' => 'system_localized',
            'property' => self::INTERNAL_FIELD,
            'type' => 'reference',
        ));
    }
}

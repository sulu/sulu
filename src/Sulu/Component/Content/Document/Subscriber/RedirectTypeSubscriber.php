<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use PHPCR\PropertyType;

class RedirectTypeSubscriber implements EventSubscriberInterface
{
    const REDIRECT_TYPE_FIELD = 'nodeType';
    const INTERNAL_FIELD = 'internal_link';
    const EXTERNAL_FIELD = 'external';

    private $documentRegistry;

    /**
     * @param PropertyEncoder $encoder
     * @param DocumentAccessor $accessor
     * @param ProxyFactory $proxyFactory
     */
    public function __construct(
        DocumentRegistry $documentRegistry
    )
    {
        $this->documentRegistry = $documentRegistry;
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::METADATA_LOAD => 'handleMetadataLoad',
        );
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectedClass()->isSubclassOf(OrderBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping(self::REDIRECT_TYPE_FIELD, array(
            'encoding' => 'system_localized',
            'property' => self::REDIRECT_TYPE_FIELD,
        ));
        $metadata->addFieldMapping(self::EXTERNAL_FIELD, array(
            'encoding' => 'system_localized',
            'property' => self::EXTERNAL_FIELD,
        ));
        $metadata->addFieldMapping(self::INTERNAL_FIELD, array(
            'encoding' => 'system_localized',
            'property' => self::INTERNAL_FIELD,
            'type' => 'reference',
        ));
    }
}

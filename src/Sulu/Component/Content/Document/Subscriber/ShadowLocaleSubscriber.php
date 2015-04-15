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
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;

class ShadowLocaleSubscriber extends AbstractMappingSubscriber
{
    const SHADOW_ENABLED_FIELD = 'shadow-on';
    const SHADOW_LOCALE_FIELD = 'shadow-base';

    private $inspector;

    /**
     * @param PropertyEncoder $encoder
     * @param DocumentInspector $inspector
     */
    public function __construct(
        PropertyEncoder $encoder,
        DocumentInspector $inspector
    )
    {
        parent::__construct($encoder);
        $this->inspector = $inspector;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($document)
    {
        return $document instanceof ShadowLocaleBehavior;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::PERSIST => array(
                // before resourceSegment and content
                array('handlePersistUpdateUrl', 20),
                array('handlePersist', 0),
            ),
            Events::HYDRATE => array('handleHydrate', 0),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function doHydrate(HydrateEvent $event)
    {
        $value = $event->getNode()->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::SHADOW_ENABLED_FIELD, $event->getLocale()),
            false
        );
        $event->getDocument()->setShadowLocaleEnabled($value);

        $value = $event->getNode()->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::SHADOW_LOCALE_FIELD, $event->getLocale()),
            null
        );
        $event->getDocument()->setShadowLocale($value);
    }

    /**
     * {@inheritDoc}
     */
    public function doPersist(PersistEvent $event)
    {
        $event->getNode()->setProperty(
            $this->encoder->localizedSystemName(self::SHADOW_ENABLED_FIELD, $event->getLocale()),
            $event->getDocument()->isShadowLocaleEnabled() ? : null
        );

        $event->getNode()->setProperty(
            $this->encoder->localizedSystemName(self::SHADOW_LOCALE_FIELD, $event->getLocale()),
            $event->getDocument()->getShadowLocale()
        );
    }

    /**
     * If this is a shadow document, update the URL to that of the shadowed document
     *
     * @param PersistEvent $event
     */
    public function handlePersistUpdateUrl(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document->isShadowLocaleEnabled()) {
            return;
        }

        $node = $event->getNode();
        $structure = $this->inspector->getStructure($document);

        if (false === $structure->hasPropertyWithTagName('sulu.rlp')) {
            return;
        }

        $locatorProperty = $structure->getPropertyByTagName('sulu.rlp');

        $shadowLocator = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(
                $locatorProperty->getName(), $document->getShadowLocale()
            ),
            null
        );

        if (!$shadowLocator) {
            return;
        }

        $event->getAccessor()->set(
            'resourceSegment',
            $shadowLocator
        );
    }
}

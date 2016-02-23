<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\NodeInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShadowLocaleSubscriber implements EventSubscriberInterface
{
    const SHADOW_ENABLED_FIELD = 'shadow-on';
    const SHADOW_LOCALE_FIELD = 'shadow-base';

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    public function __construct(
        PropertyEncoder $encoder
    ) {
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::METADATA_LOAD => 'handleMetadataLoad',
            Events::PERSIST => [
                // before resourceSegment and content
                ['handlePersistUpdateUrl', 20],
                ['handlePersist', 15],
            ],
            Events::HYDRATE => [
                ['handleHydrate', 390],
            ],
        ];
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (!$metadata->getReflectionClass()->isSubclassOf(ShadowLocaleBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('shadowLocaleEnabled', [
            'property' => self::SHADOW_ENABLED_FIELD,
            'encoding' => 'system_localized',
            'mapped' => false,
        ]);

        $metadata->addFieldMapping('shadowLocale', [
            'property' => self::SHADOW_LOCALE_FIELD,
            'encoding' => 'system_localized',
            'mapped' => false,
        ]);
    }

    /**
     * Update the locale to the shadow locale, if it is enabled.
     *
     * Note that this should happen before the fallback locale has been resolved
     *
     * @param AbstractMappingEvent $event
     */
    public function handleHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ShadowLocaleBehavior) {
            return;
        }

        $node = $event->getNode();
        $locale = $event->getLocale();
        $shadowLocaleEnabled = $this->getShadowLocaleEnabled($node, $locale);
        $document->setShadowLocaleEnabled($shadowLocaleEnabled);

        if (!$shadowLocaleEnabled) {
            return;
        }

        $shadowLocale = $this->getShadowLocale($node, $locale);
        $document->setShadowLocale($shadowLocale);
        $event->getContext()->getRegistry()->updateLocale($document, $shadowLocale, $locale);
        $event->setLocale($shadowLocale);
    }

    /**
     * {@inheritdoc}
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ShadowLocaleBehavior) {
            return;
        }

        if (!$event->getLocale()) {
            return;
        }

        if ($document->isShadowLocaleEnabled()) {
            $this->validateShadow($event->getContext()->getInspector(), $document);
        }

        $event->getNode()->setProperty(
            $this->encoder->localizedSystemName(self::SHADOW_ENABLED_FIELD, $event->getLocale()),
            $document->isShadowLocaleEnabled() ?: null
        );

        $event->getNode()->setProperty(
            $this->encoder->localizedSystemName(self::SHADOW_LOCALE_FIELD, $event->getLocale()),
            $document->getShadowLocale()
        );
    }

    /**
     * If this is a shadow document, update the URL to that of the shadowed document.
     *
     * TODO: This is about caching and should be handled somewhere else.
     *
     * @param PersistEvent $event
     */
    public function handlePersistUpdateUrl(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ShadowLocaleBehavior) {
            return;
        }

        if (!$document->isShadowLocaleEnabled()) {
            return;
        }

        $node = $event->getNode();
        $structure = $event->getContext()->getInspector()->getStructureMetadata($document);

        if (false === $structure->hasPropertyWithTagName('sulu.rlp')) {
            return;
        }

        $locatorProperty = $structure->getPropertyByTagName('sulu.rlp');

        if ($node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(
                $locatorProperty->getName(), $document->getLocale()
            ),
            null
        )) {
            return;
        }

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

    private function getShadowLocaleEnabled(NodeInterface $node, $locale)
    {
        return (bool) $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::SHADOW_ENABLED_FIELD, $locale),
            false
        );
    }

    private function getShadowLocale(NodeInterface $node, $locale)
    {
        return $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::SHADOW_LOCALE_FIELD, $locale),
            null
        );
    }

    private function validateShadow(DocumentInspector $inspector, ShadowLocaleBehavior $document)
    {
        if ($document->getLocale() === $document->getShadowLocale()) {
            throw new \RuntimeException(sprintf(
                'Document cannot be a shadow of itself for locale "%s"',
                $document->getLocale()
            ));
        }

        $locales = $inspector->getConcreteLocales($document);
        if (!in_array($document->getShadowLocale(), $locales)) {
            $inspector->getNode($document)->revert();
            throw new \RuntimeException(sprintf(
                'Attempting to create shadow for "%s" on a non-concrete locale "%s" for document at "%s". Concrete languages are "%s"',
                $document->getLocale(),
                $document->getShadowLocale(),
                $inspector->getPath($document),
                implode('", "', $locales)
            ));
        }
    }
}

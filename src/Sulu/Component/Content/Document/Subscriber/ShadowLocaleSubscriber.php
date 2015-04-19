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

use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;

class ShadowLocaleSubscriber extends AbstractMappingSubscriber
{
    const SHADOW_ENABLED_FIELD = 'shadow-on';
    const SHADOW_LOCALE_FIELD = 'shadow-base';

    private $inspector;
    private $registry;

    /**
     * @param PropertyEncoder   $encoder
     * @param DocumentInspector $inspector
     */
    public function __construct(
        PropertyEncoder $encoder,
        DocumentInspector $inspector,
        DocumentRegistry $registry
    ) {
        parent::__construct($encoder);
        $this->inspector = $inspector;
        $this->registry = $registry;
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
                array('handlePersist', 15),
            ),
            Events::HYDRATE => array(
                array('handleHydrate', 390),
            ),
        );
    }

    /**
     * Update the locale to the shadow locale, if it is enabled
     *
     * Note that this should happen before the fallback locale has been resolved
     *
     * @param HydrateEvent $event
     */
    public function doHydrate(AbstractMappingEvent $event)
    {
        $node = $event->getNode();
        $locale = $event->getLocale();
        $document = $event->getDocument();

        $shadowLocaleEnabled = $this->getShadowLocaleEnabled($node, $locale);
        $document->setShadowLocaleEnabled($shadowLocaleEnabled);

        if (!$shadowLocaleEnabled) {
            return;
        }

        $document->setShadowLocale($this->getShadowLocale($node, $locale));

        $shadowLocale = $this->getShadowLocale($node, $locale);
        $this->registry->updateLocale($document, $shadowLocale, $locale);
        $event->setLocale($shadowLocale);
    }

    /**
     * {@inheritDoc}
     */
    public function doPersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if ($document->isShadowLocaleEnabled()) {
            $this->validateShadow($document);
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
     * If this is a shadow document, update the URL to that of the shadowed document
     *
     * TODO: This is about caching and should be handled somewhere else.
     *
     * @param PersistEvent $event
     */
    public function handlePersistUpdateUrl(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

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

    private function getShadowLocaleEnabled(NodeInterface $node, $locale)
    {
        return (boolean) $node->getPropertyValueWithDefault(
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

    private function validateShadow(ShadowLocaleBehavior $document)
    {
        if ($document->getLocale() === $document->getShadowLocale()) {
            throw new \RuntimeException(sprintf(
                'Document cannot be a shadow of itself for locale "%s"',
                $document->getLocale()
            ));
        }

        $locales = $this->inspector->getConcreteLocales($document);
        if (!in_array($document->getShadowLocale(), $locales)) {
            $this->inspector->getNode($document)->revert();
            throw new \RuntimeException(sprintf(
                'Attempting to create shadow for "%s" on a non-concrete locale "%s" for document at "%s". Concrete languages are "%s"',
                $document->getLocale(),
                $document->getShadowLocale(),
                $this->inspector->getPath($document),
                implode('", "', $locales)
            ));
        }
    }
}

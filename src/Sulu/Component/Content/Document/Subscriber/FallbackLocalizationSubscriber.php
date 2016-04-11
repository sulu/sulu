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

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\LocalizationFinderInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set a fallback locale for the document if necessary.
 */
class FallbackLocalizationSubscriber implements EventSubscriberInterface
{
    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var LocalizationFinderInterface
     */
    private $localizationFinder;

    public function __construct(
        PropertyEncoder $encoder,
        LocalizationFinderInterface $localizationFinder
    ) {
        $this->encoder = $encoder;
        $this->localizationFinder = $localizationFinder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // needs to happen after the node and document has been initially registered
            // but before any mapping takes place.
            Events::HYDRATE => ['handleHydrate', 400],
        ];
    }

    /**
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        // we currently only support fallback on StructureBehavior implementors
        // because we use the template key to determine localization status
        if (!$document instanceof StructureBehavior) {
            return;
        }

        $locale = $event->getLocale();

        if (!$locale) {
            return;
        }

        $manager = $event->getManager();

        $newLocale = $this->getAvailableLocalization($manager->getInspector(), $manager->getRegistry(), $document, $locale);
        $event->setLocale($newLocale);

        if ($newLocale === $locale) {
            return;
        }

        $registry = $manager->getRegistry();
        if ($event->getOption('load_ghost_content', true) === true) {
            $registry->updateLocale($document, $newLocale, $locale);

            return;
        }

        $registry->updateLocale($document, $locale, $locale);
    }

    /**
     * Return available localizations.
     *
     * @param StructureBehavior $document
     * @param string            $locale
     *
     * @return string
     */
    private function getAvailableLocalization(DocumentInspector $inspector, DocumentRegistry $registry, StructureBehavior $document, $locale)
    {
        $availableLocales = $inspector->getLocales($document);

        if (in_array($locale, $availableLocales)) {
            return $locale;
        }

        $fallbackLocale = null;

        if ($document instanceof WebspaceBehavior) {
            $fallbackLocale = $this->localizationFinder->findAvailableLocale(
                $inspector->getWebspace($document),
                $availableLocales,
                $locale
            );
        }

        if (!$fallbackLocale) {
            $fallbackLocale = reset($availableLocales);
        }

        if (!$fallbackLocale) {
            $fallbackLocale = $registry->getDefaultLocale();
        }

        return $fallbackLocale;
    }
}

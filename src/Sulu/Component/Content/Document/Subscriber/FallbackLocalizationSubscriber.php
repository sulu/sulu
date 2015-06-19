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

use PHPCR\NodeInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;

/**
 * Set a fallback locale for the document if necessary
 *
 * TODO: Most of this code is legacy. It seems to me that this could be
 *       much simpler and more efficient.
 */
class FallbackLocalizationSubscriber implements EventSubscriberInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    public function __construct(
        PropertyEncoder $encoder,
        WebspaceManagerInterface $webspaceManager,
        DocumentInspector $inspector,
        DocumentRegistry $documentRegistry
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->encoder = $encoder;
        $this->inspector = $inspector;
        $this->documentRegistry = $documentRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            // needs to happen after the node and document has been initially registered
            // but before any mapping takes place.
            Events::HYDRATE => array('handleHydrate', 400),
        );
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

        $newLocale = $this->getAvailableLocalization($document, $locale);
        $event->setLocale($newLocale);

        if ($newLocale === $locale) {
            return;
        }

        if ($event->getOption('load_ghost_content', true) === true) {
            $this->documentRegistry->updateLocale($document, $newLocale, $locale);

            return;
        }

        $this->documentRegistry->updateLocale($document, $locale, $locale);
    }

    /**
     * Return available localizations
     *
     * @param StructureBehavior $document
     * @param string $locale
     *
     * @return string
     */
    public function getAvailableLocalization(StructureBehavior $document, $locale)
    {
        $availableLocales = $this->inspector->getLocales($document);

        if (in_array($locale, $availableLocales)) {
            return $locale;
        }

        $fallbackLocale = null;

        if ($document instanceof WebspaceBehavior) {
            $fallbackLocale = $this->getWebspaceLocale(
                $this->inspector->getWebspace($document),
                $availableLocales,
                $locale
            );
        }

        if (!$fallbackLocale) {
            $fallbackLocale = reset($availableLocales);
        }

        if (!$fallbackLocale) {
            $fallbackLocale = $this->documentRegistry->getDefaultLocale();
        }

        return $fallbackLocale;
    }

    /**
     * @param string $webspaceName
     * @param string[] $availableLocales
     * @param string $locale
     *
     * @return string
     */
    private function getWebspaceLocale($webspaceName, $availableLocales, $locale)
    {
        if (!$webspaceName) {
            return null;
        }

        // get localization object for querying parent localizations
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceName);
        $localization = $webspace->getLocalization($locale);

        if (null === $localization) {
            return null;
        }

        $resultLocalization = null;

        // find first available localization in parents
        $resultLocalization = $this->findAvailableParentLocalization(
            $availableLocales,
            $localization
        );

        // find first available localization in children, if no result is found yet
        if (!$resultLocalization) {
            $resultLocalization = $this->findAvailableChildLocalization(
                $availableLocales,
                $localization
            );
        }

        // find any localization available, if no result is found yet
        if (!$resultLocalization) {
            $resultLocalization = $this->findAvailableLocalization(
                $availableLocales,
                $webspace->getLocalizations()
            );
        }

        if (!$resultLocalization) {
            return null;
        }

        return $resultLocalization->getLocalization();
    }

    /**
     * Finds the next available parent-localization in which the node has a translation
     *
     * @param string[] $availableLocales
     * @param Localization $localization The localization to start the search for
     *
     * @return null|Localization
     */
    private function findAvailableParentLocalization(
        array $availableLocales,
        Localization $localization
    ) {
        do {
            if (in_array($localization->getLocalization(), $availableLocales)) {
                return $localization;
            }

            // try to load parent and stop if there is no parent
            $localization = $localization->getParent();
        } while ($localization != null);

        return null;
    }

    /**
     * Finds the next available child-localization in which the node has a translation
     *
     * @param string[] $availableLocales
     * @param Localization $localization The localization to start the search for
     *
     * @return null|Localization
     */
    private function findAvailableChildLocalization(
        array $availableLocales,
        Localization $localization
    ) {
        $childrenLocalizations = $localization->getChildren();

        if (!empty($childrenLocalizations)) {
            foreach ($childrenLocalizations as $childrenLocalization) {
                // return the localization if a translation exists in the child localization
                if (in_array($childrenLocalization->getLocalization(), $availableLocales)) {
                    return $childrenLocalization;
                }

                // recursively call this function for checking children
                return $this->findAvailableChildLocalization($availableLocales, $childrenLocalization);
            }
        }

        // return null if nothing was found
        return null;
    }

    /**
     * Finds any localization, in which the node is translated
     *
     * @param string[] $availableLocales
     * @param Localization[] $localizations The available localizations
     *
     * @return null|Localization
     */
    private function findAvailableLocalization(
        array $availableLocales,
        array $localizations
    ) {
        foreach ($localizations as $localization) {

            if (in_array($localization->getLocalization(), $availableLocales)) {
                return $localization;
            }

            $children = $localization->getChildren();

            if ($children) {
                return $this->findAvailableLocalization($availableLocales, $children);
            }
        }

        return null;
    }
}

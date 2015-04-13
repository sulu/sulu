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
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Structure\Property;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\DocumentInspector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;

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
    )
    {
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

    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        // we currently only support fallback on ContentBehavior implementors
        // because we use the template key to determine localization status
        if (!$document instanceof ContentBehavior) {
            return;
        }

        $node = $event->getNode();
        $locale = $event->getLocale();

        if (!$locale) {
            return;
        }

        $newLocale = $this->getAvailableLocalization($node, $document, $locale);

        if ($newLocale === $locale) {
            return;
        }

        $this->documentRegistry->updateLocale($document, $newLocale);
        $event->setLocale($newLocale);
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableLocalization(NodeInterface $node, $document, $locale)
    {
        $structureTypeName = $this->encoder->localizedSystemName(ContentSubscriber::STRUCTURE_TYPE_FIELD, $locale);

        // check if it already is the correct localization
        if ($node->hasProperty($structureTypeName)) {
            return $locale;
        }

        $webspace = $this->inspector->getWebspace($document);

        if ($webspace) {
            $locale = $this->getWebspaceLocale($node, $webspace, $locale);
        }

        if (!$locale) {
            $locales = $this->inspector->getLocales($document);
            $locale = reset($locales);
        }

        if (!$locale) {
            throw new \RuntimeException(sprintf(
                'Could not find any localizations for document at "%s". This should not happen.',
                $node->getPath()
            ));
        }

        return $locale;
    }

    private function getWebspaceLocale($node, $webspaceKey, $locale)
    {
        // get localization object for querying parent localizations
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $localization = $webspace->getLocalization($locale);

        if (null === $localization) {
            return null;
        }

        $resultLocalization = null;

        // find first available localization in parents
        $resultLocalization = $this->findAvailableParentLocalization(
            $node,
            $localization
        );

        // find first available localization in children, if no result is found yet
        if (!$resultLocalization) {
            $resultLocalization = $this->findAvailableChildLocalization(
                $node,
                $localization
            );
        }

        // find any localization available, if no result is found yet
        if (!$resultLocalization) {
            $resultLocalization = $this->findAvailableLocalization(
                $node,
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
     * @param NodeInterface $node The node, which properties will be checked
     * @param Localization $localization The localization to start the search for
     *
     * @return Localization|null
     */
    private function findAvailableParentLocalization(
        NodeInterface $node,
        Localization $localization
    )
    {
        do {
            $propertyName = $this->getPropertyName($localization->getLocalization());

            if ($node->hasProperty($propertyName)) {
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
     * @param NodeInterface $node The node, which properties will be checked
     * @param Localization $localization The localization to start the search for
     * @param TranslatedProperty $property The property which will be checked for the translation
     * @return null|Localization
     */
    private function findAvailableChildLocalization(
        NodeInterface $node,
        Localization $localization
    )
    {
        $childrenLocalizations = $localization->getChildren();

        if (!empty($childrenLocalizations)) {
            foreach ($childrenLocalizations as $childrenLocalization) {
                $propertyName = $this->getPropertyName($childrenLocalization->getLocalization());
                // return the localization if a translation exists in the child localization
                if ($node->hasProperty($propertyName)) {
                    return $childrenLocalization;
                }

                // recursively call this function for checking children
                return $this->findAvailableChildLocalization($node, $childrenLocalization);
            }
        }

        // return null if nothing was found
        return null;
    }

    /**
     * Finds any localization, in which the node is translated
     * @param NodeInterface $node The node, which properties will be checkec
     * @param array $localizations The available localizations
     * @param TranslatedProperty $property The property to check
     * @return null|Localization
     */
    private function findAvailableLocalization(
        NodeInterface $node,
        array $localizations
    )
    {
        foreach ($localizations as $localization) {
            $propertyName = $this->getPropertyName($localization->getLocalization());

            if ($node->hasProperty($propertyName)) {
                return $localization;
            }

            $children = $localization->getChildren();

            if ($children) {
                return $this->findAvailableLocalization($node, $children);
            }
        }

        return null;
    }

    private function getPropertyName($locale)
    {
        return $this->encoder->localizedSystemName(ContentSubscriber::STRUCTURE_TYPE_FIELD, $locale);
    }
}

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

class RedirectTypeSubscriber extends AbstractMappingSubscriber
{
    const REDIRECT_TYPE_FIELD = 'nodeType';
    const INTERNAL_FIELD = 'internal_link';
    const EXTERNAL_FIELD = 'external';

    private $proxyFactory;
    private $documentRegistry;

    /**
     * @param PropertyEncoder $encoder
     * @param DocumentAccessor $accessor
     * @param ProxyFactory $proxyFactory
     */
    public function __construct(
        PropertyEncoder $encoder, 
        ProxyFactory $proxyFactory,
        DocumentRegistry $documentRegistry
    )
    {
        parent::__construct($encoder);
        $this->proxyFactory = $proxyFactory;
        $this->documentRegistry = $documentRegistry;
    }

    public function supports($document)
    {
        return $document instanceof RedirectTypeBehavior;
    }

    /**
     * @param HydrateEvent $event
     */
    public function doHydrate(AbstractMappingEvent $event)
    {
        $node = $event->getNode();
        $document = $event->getDocument();

        $redirectType = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::REDIRECT_TYPE_FIELD, $event->getLocale()),
            RedirectType::NONE
        );
        $document->setRedirectType($redirectType);

        if ($redirectType === RedirectType::INTERNAL) {
            $internalNode = $node->getPropertyValueWithDefault(
                $this->encoder->localizedSystemName(self::INTERNAL_FIELD, $event->getLocale()),
                null
            );

            if ($internalNode) {
                $document->setRedirectTarget($this->proxyFactory->createProxyForNode($document, $internalNode));
            }
        }

        if ($redirectType === RedirectType::EXTERNAL) {
            $externalUrl = $node->getPropertyValueWithDefault(
                $this->encoder->localizedSystemName(self::EXTERNAL_FIELD, $event->getLocale()),
                null
            );
            $document->setRedirectExternal($externalUrl);
        }
    }

    /**
     * @param PersistEvent $event
     */
    public function doPersist(PersistEvent $event)
    {
        $node = $event->getNode();
        $document = $event->getDocument();

        $node->setProperty(
            $this->encoder->localizedSystemName(self::REDIRECT_TYPE_FIELD, $event->getLocale()),
            $document->getRedirectType() ? : RedirectType::NONE,
            PropertyType::LONG
        );

        $node->setProperty(
            $this->encoder->localizedSystemName(self::EXTERNAL_FIELD, $event->getLocale()),
            $document->getRedirectExternal()
        );

        $internalDocument = $document->getRedirectTarget();
        if (!$internalDocument) {
            return;
        }

        $internalNode = $this->documentRegistry->getNodeForDocument($internalDocument);

        // TODO: This should not be a UUID
        $node->setProperty(
            $this->encoder->localizedSystemName(self::INTERNAL_FIELD, $event->getLocale()),
            $internalNode
        );

        $this->doHydrate($event);
    }
}

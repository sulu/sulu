<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\EventListener;

use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

/**
 * Extends webspace serialization process.
 */
class WebspaceSerializeEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    public function __construct(WebspaceManagerInterface $webspaceManager, $environment)
    {
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $webspace = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!($webspace instanceof Webspace)) {
            return;
        }

        $this->appendPortalInformation($webspace, $context, $visitor);
        $this->appendCustomUrls($webspace, $context, $visitor);
    }

    /**
     * Extract portal-information and add them to serialization.
     *
     * @param Webspace $webspace
     * @param Context $context
     * @param JsonSerializationVisitor $visitor
     */
    private function appendPortalInformation(Webspace $webspace, Context $context, JsonSerializationVisitor $visitor)
    {
        $portalInformation = $this->webspaceManager->getPortalInformationsByWebspaceKey(
            $this->environment,
            $webspace->getKey()
        );

        $portalInformation = $context->accept(array_values($portalInformation));
        $visitor->addData('portalInformation', $portalInformation);

        $urls = [];
        foreach ($webspace->getPortals() as $portal) {
            $environment = $portal->getEnvironment($this->environment);
            $urls = array_merge($urls, $environment->getUrls());
        }
        $urls = $event->getContext()->accept($urls);
        $visitor->addData('urls', $urls);
    }

    /**
     * Extract custom-url and add them to serialization.
     *
     * @param Webspace $webspace
     * @param Context $context
     * @param JsonSerializationVisitor $visitor
     */
    private function appendCustomUrls(Webspace $webspace, Context $context, JsonSerializationVisitor $visitor)
    {
        $customUrls = [];
        foreach ($webspace->getPortals() as $portal) {
            $customUrls = array_merge($customUrls, $portal->getEnvironment($this->environment)->getCustomUrls());
        }

        $customUrls = $context->accept($customUrls);
        $visitor->addData('customUrls', $customUrls);
    }
}

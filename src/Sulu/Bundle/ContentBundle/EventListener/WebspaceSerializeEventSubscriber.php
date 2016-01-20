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
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $event->getVisitor();

        if (!($webspace instanceof Webspace)) {
            return;
        }

        $portalInformation = $this->webspaceManager->getPortalInformationsByWebspaceKey(
            $this->environment,
            $webspace->getKey()
        );

        $portalInformation = $event->getContext()->accept(array_values($portalInformation));
        $visitor->addData('portalInformation', $portalInformation);

        $urls = [];
        foreach ($webspace->getPortals() as $portal) {
            $environment = $portal->getEnvironment($this->environment);
            $urls = array_merge($urls, $environment->getUrls());
        }
        $urls = $event->getContext()->accept($urls);
        $visitor->addData('urls', $urls);
    }
}

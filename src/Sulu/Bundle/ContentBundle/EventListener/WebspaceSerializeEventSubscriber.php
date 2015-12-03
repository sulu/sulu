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
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;

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

        $portalInformation = $this->webspaceManager->getPortalInformations($this->environment);

        $portalInformation = $event->getContext()->accept(
            array_filter(
                array_values($portalInformation),
                function (PortalInformation $information) use ($webspace) {
                    return $information->getWebspaceKey() === $webspace->getKey();
                }
            )
        );

        // FIXME dirty hack to avoid same "id" for datagrid
        $i = 0;
        $portalInformation = array_map(
            function ($item) use(&$i) {
                $item['id'] = ++$i;

                return $item;
            },
            $portalInformation
        );

        $visitor->addData('portalInformation', $portalInformation);
    }
}

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
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Url\WebspaceUrlProviderInterface;
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
     * @var WebspaceUrlProviderInterface
     */
    private $webspaceUrlProvider;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        WebspaceUrlProviderInterface $webspaceUrlProvider,
        $environment
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->webspaceUrlProvider = $webspaceUrlProvider;
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
        $this->appendUrls($webspace, $context, $visitor);
        $this->appendCustomUrls($webspace, $context, $visitor);

        $visitor->addData('allLocalizations', $webspace->getAllLocalizations());
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
    }

    /**
     * Extract urls and add them to serialization.
     *
     * @param Webspace $webspace
     * @param Context $context
     * @param JsonSerializationVisitor $visitor
     */
    private function appendUrls(Webspace $webspace, Context $context, JsonSerializationVisitor $visitor)
    {
        $urls = $this->webspaceUrlProvider->getUrls($webspace, $this->environment);
        $urls = $context->accept($urls);
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
            $customUrls = array_merge(
                $customUrls,
                $this->getCustomUrlsForEnvironment($portal, $portal->getEnvironment($this->environment), $context)
            );
        }

        $customUrls = $context->accept($customUrls);
        $visitor->addData('customUrls', $customUrls);
    }

    /**
     * Returns custom-url data with the connected locales.
     *
     * @param Portal $portal
     * @param Environment $environment
     * @param Context $context
     *
     * @return array
     */
    private function getCustomUrlsForEnvironment(Portal $portal, Environment $environment, Context $context)
    {
        $customUrls = [];
        foreach ($environment->getCustomUrls() as $customUrl) {
            $customUrl = $context->accept($customUrl);
            $customUrl['locales'] = $context->accept($portal->getLocalizations());
            $customUrls[] = $customUrl;
        }

        return $customUrls;
    }
}

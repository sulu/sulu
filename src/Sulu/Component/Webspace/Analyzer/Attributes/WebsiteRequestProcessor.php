<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Attributes;

use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts attributes from request for the sulu-website.
 */
class WebsiteRequestProcessor implements RequestProcessorInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var ReplacerInterface
     */
    private $replacer;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        ContentMapperInterface $contentMapper,
        ReplacerInterface $replacer,
        $environment
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->contentMapper = $contentMapper;
        $this->replacer = $replacer;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $host = $request->getHttpHost();
        $url = $host . $request->getPathInfo();
        foreach ($this->webspaceManager->getPortalInformations($this->environment) as $portalInformation) {
            $portalUrl = $this->replacer->replaceHost($portalInformation->getUrl(), $host);
            $portalInformation->setUrl($portalUrl);
            $portalRedirect = $this->replacer->replaceHost($portalInformation->getRedirect(), $host);
            $portalInformation->setRedirect($portalRedirect);
        }

        $portalInformations = $this->webspaceManager->findPortalInformationsByUrl(
            $url,
            $this->environment
        );

        if (count($portalInformations) === 0) {
            return new RequestAttributes();
        }

        usort(
            $portalInformations,
            function (PortalInformation $a, PortalInformation $b) {
                if ($a->getPriority() === $b->getPriority()) {
                    return strlen($a->getUrl()) < strlen($b->getUrl());
                }

                return $a->getPriority() < $b->getPriority();
            }
        );

        /** @var PortalInformation $portalInformation */
        $portalInformation = reset($portalInformations);

        return new RequestAttributes(['portalInformation' => $portalInformation]);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(RequestAttributes $attributes)
    {
        if (null === $attributes->getAttribute('portalInformation')) {
            throw new UrlMatchNotFoundException($attributes->getAttribute('requestUri'));
        }

        return true;
    }
}

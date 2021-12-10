<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Attributes;

use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
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

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        ContentMapperInterface $contentMapper,
        $environment
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->contentMapper = $contentMapper;
        $this->environment = $environment;
    }

    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $host = $requestAttributes->getAttribute('host');
        $url = $host . $requestAttributes->getAttribute('path');

        $portalInformations = $this->webspaceManager->findPortalInformationsByUrl(
            $url,
            $this->environment
        );

        if (0 === \count($portalInformations)) {
            return new RequestAttributes();
        }

        \usort(
            $portalInformations,
            function(PortalInformation $a, PortalInformation $b) {
                if ($a->getPriority() === $b->getPriority()) {
                    return \strlen($a->getUrl()) < \strlen($b->getUrl());
                }

                return $a->getPriority() < $b->getPriority();
            }
        );

        /** @var PortalInformation $portalInformation */
        $portalInformation = \reset($portalInformations);

        return new RequestAttributes(['portalInformation' => $portalInformation]);
    }

    public function validate(RequestAttributes $attributes)
    {
        if (null === $attributes->getAttribute('portalInformation')) {
            $portalUrls = [];
            foreach ($this->webspaceManager->getPortalInformations() as $portalInformation) {
                $portalUrls[] = $attributes->getAttribute('scheme') . '://'
                    . $portalInformation->getUrl();
            }

            $fullUrl = $attributes->getAttribute('scheme') . '://'
                . $attributes->getAttribute('host')
                . (!\in_array($attributes->getAttribute('port'), ['80', '443'], true) ? ':' . $attributes->getAttribute('port') : '')
                    . $attributes->getAttribute('path');

            throw new UrlMatchNotFoundException(
                $fullUrl,
                $portalUrls
            );
        }

        return true;
    }
}

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
use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts attributes from request for the sulu-website.
 */
class WebsiteRequestProcessor extends AbstractRequestProcessor
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

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $url = $request->getHost() . $request->getPathInfo();
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
                return $a->getType() > $b->getType();
            }
        );

        /** @var PortalInformation $portalInformation */
        $portalInformation = reset($portalInformations);

        return $this->processPortalInformation(
            $request,
            $portalInformation,
            ['urlExpression' => $portalInformation->getUrlExpression()]
        );
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

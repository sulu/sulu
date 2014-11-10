<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig;

use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * provides the content_path function to generate real urls for frontend
 * @package Sulu\Bundle\WebsiteBundle\Twig
 */
class ContentPathTwigExtension extends \Twig_Extension
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        ContentMapperInterface $contentMapper,
        WebspaceManagerInterface $webspaceManager,
        $environment,
        RequestAnalyzerInterface $requestAnalyzer = null
    ) {
        $this->contentMapper = $contentMapper;
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('content_path', array($this, 'contentPathFunction')),
            new \Twig_SimpleFunction('content_root_path', array($this, 'contentRootPathFunction'))
        );
    }

    /**
     * generates real url for given content
     * @param string $url
     * @param string $webspaceKey
     * @param string $locale
     * @return string
     */
    public function contentPathFunction($url, $webspaceKey = null, $locale = null)
    {
        if (
            $webspaceKey !== null &&
            $this->requestAnalyzer
        ) {
            $portalUrls = $this->webspaceManager->findUrlsByResourceLocator(
                $url,
                $this->environment,
                $locale ? : $this->requestAnalyzer->getCurrentLocalization()->getLocalization(),
                $webspaceKey
            );

            if (sizeof($portalUrls) > 0) {
                return rtrim($portalUrls[0], '/');
            }
        } elseif (strpos($url, '/') === 0 && $this->requestAnalyzer) {
            return rtrim($this->requestAnalyzer->getCurrentResourceLocatorPrefix() . $url, '/');
        }

        return $url;
    }

    /**
     * generates real root url
     * @param boolean $full if TRUE the full url will be returned, if FALSE only the current prefix is returned
     * @return string
     */
    public function contentRootPathFunction($full = false)
    {
        if ($this->requestAnalyzer !== null) {
            if ($full) {
                return $this->requestAnalyzer->getCurrentPortalUrl();
            } else {
                return $this->requestAnalyzer->getCurrentResourceLocatorPrefix();
            }
        } else {
            return '/';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_website_content_path';
    }
}

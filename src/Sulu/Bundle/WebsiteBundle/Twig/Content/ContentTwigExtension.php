<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Content;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Exception\ParentNotFoundException;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Interface to load content.
 */
class ContentTwigExtension extends AbstractExtension implements ContentTwigExtensionInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ?SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * Constructor.
     */
    public function __construct(
        ContentMapperInterface $contentMapper,
        StructureResolverInterface $structureResolver,
        SessionManagerInterface $sessionManager,
        RequestAnalyzerInterface $requestAnalyzer,
        LoggerInterface $logger = null,
        SecurityCheckerInterface $securityChecker = null
    ) {
        $this->contentMapper = $contentMapper;
        $this->structureResolver = $structureResolver;
        $this->sessionManager = $sessionManager;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->logger = $logger ?: new NullLogger();
        $this->securityChecker = $securityChecker;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_content_load', [$this, 'load']),
            new TwigFunction('sulu_content_load_parent', [$this, 'loadParent']),
        ];
    }

    public function load($uuid)
    {
        if (!$uuid) {
            return;
        }

        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();

        try {
            $contentStructure = $this->contentMapper->load(
                $uuid,
                $this->requestAnalyzer->getWebspace()->getKey(),
                $locale
            );

            if ($this->securityChecker
                && !$this->securityChecker->hasPermission(
                    new SecurityCondition(
                        PageAdmin::SECURITY_CONTEXT_PREFIX . $contentStructure->getWebspaceKey(),
                        $locale,
                        SecurityBehavior::class,
                        $uuid
                    ),
                    PermissionTypes::VIEW
                )
            ) {
                return null;
            }

            return $this->structureResolver->resolve($contentStructure);
        } catch (DocumentNotFoundException $e) {
            $this->logger->error((string) $e);

            return;
        }
    }

    public function loadParent($uuid)
    {
        $session = $this->sessionManager->getSession();
        $contentsNode = $this->sessionManager->getContentNode($this->requestAnalyzer->getWebspace()->getKey());
        $node = $session->getNodeByIdentifier($uuid);

        if ($node->getDepth() <= $contentsNode->getDepth()) {
            throw new ParentNotFoundException($uuid);
        }

        return $this->load($node->getParent()->getIdentifier());
    }
}

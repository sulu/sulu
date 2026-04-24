<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Repository;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Component\Webspace\Manager\Dumper\PhpWebspaceCollectionDumper;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceCollectionBuilder;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Domain\Model\PageInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;

class WebspaceRepository implements WebspaceRepositoryInterface
{
    private ?WebspaceCollection $webspaceCollection = null;

    /**
     * @param array{
     *    cache_class: string,
     *    cache_dir: ?string,
     *    base_class: string,
     *    debug: bool,
     * } $options
     */
    public function __construct(
        private MetadataProviderRegistry $metadataProviderRegistry,
        private WebspaceCollectionBuilder $webspaceCollectionBuilder,
        private RequestStack $requestStack,
        private RequestContext $requestContext,
        private ReplacerInterface $urlReplacer,
        private string $environment,
        private array $options
    ) {
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    private function getWebspaceCollection(): WebspaceCollection
    {
        if (null === $this->webspaceCollection) {
            /** @var class-string<WebspaceCollection> $class */
            $class = $this->options['cache_class'];
            $cache = new ConfigCache(
                $this->options['cache_dir'] . '/' . $class . '.php',
                $this->options['debug']
            );

            if (!$cache->isFresh()) {
                $metadataProvider = $this->metadataProviderRegistry->getMetadataProvider('form');
                $metadata = $metadataProvider->getMetadata(PageInterface::TEMPLATE_TYPE, 'en', []);
                \assert($metadata instanceof TypedFormMetadata, \sprintf('Expected TypedFormMetadata instance for "%s" metadata.', PageInterface::TEMPLATE_TYPE));

                $availableTemplates = \array_map(
                    static fn (FormMetadata $formMetadata) => $formMetadata->getKey(),
                    $metadata->getForms()
                );

                $webspaceCollection = $this->webspaceCollectionBuilder->build($availableTemplates);

                $dumper = new PhpWebspaceCollectionDumper($webspaceCollection);
                $cache->write(
                    $dumper->dump(
                        [
                            'cache_class' => $class,
                            'base_class' => $this->options['base_class'],
                        ]
                    ),
                    $webspaceCollection->getResources()
                );
            }

            require_once $cache->getPath();

            $this->webspaceCollection = new $class();

            $currentRequest = $this->requestStack->getCurrentRequest();

            $host = $currentRequest ? $currentRequest->getHost() : $this->requestContext->getHost();
            foreach ($this->webspaceCollection->getPortalInformations($this->environment) as $portalInformation) {
                $portalInformation->setUrl($this->urlReplacer->replaceHost($portalInformation->getUrl(), $host));
                $portalInformation->setUrlExpression(
                    $this->urlReplacer->replaceHost($portalInformation->getUrlExpression(), $host)
                );
                $portalInformation->setRedirect(
                    $this->urlReplacer->replaceHost($portalInformation->getRedirect(), $host)
                );
            }
        }

        return $this->webspaceCollection;
    }

    public function findWebspaceByKey(string $key): ?Webspace
    {
        return $this->getWebspaceCollection()->getWebspace($key);
    }

    public function findPortalByKey(string $key): ?Portal
    {
        return $this->getWebspaceCollection()->getPortal($key);
    }

    public function findAllWebspaces(): array
    {
        return $this->getWebspaceCollection()->getWebspaces();
    }

    public function findAllPortals(): array
    {
        return $this->getWebspaceCollection()->getPortals();
    }

    public function findAllPortalInformations(?array $types = null): array
    {
        return $this->getWebspaceCollection()->getPortalInformations($this->environment, $types);
    }
}

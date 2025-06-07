<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DataCollector;

use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class SuluCollector extends DataCollector
{
    public function __construct(
        private string $kernelEnvironment = 'dev'
    ) {
    }

    public function data(string|int $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        if (!$request->attributes->has('_sulu')) {
            return;
        }

        /** @var RequestAttributes $requestAttributes */
        $requestAttributes = $request->attributes->get('_sulu');

        /** @var ?Webspace $webspace */
        $webspace = $requestAttributes->getAttribute('webspace');
        /** @var ?Portal $portal */
        $portal = $requestAttributes->getAttribute('portal');
        $segment = $requestAttributes->getAttribute('segment');

        $this->data['match_type'] = $requestAttributes->getAttribute('matchType');
        $this->data['redirect'] = $requestAttributes->getAttribute('redirect');
        $this->data['portal_url'] = $requestAttributes->getAttribute('portalUrl');
        $this->data['segment'] = $requestAttributes->getAttribute('segment');

        if ($webspace) {
            $this->data['webspace'] = $webspace->toArray();
            unset($this->data['webspace']['portals']);
            $this->flattenLocalization($this->data['webspace']['localizations']);
        }

        if ($portal) {
            $this->data['portal'] = $portal->toArray();
            $this->data['portal']['environments'] = \array_combine(
                \array_column($this->data['portal']['environments'] ?? [], 'type'),
                $this->data['portal']['environments'] ?? [],
            );
            $this->flattenLocalization($this->data['portal']['localizations']);
            $this->data['environment'] = $portal->getEnvironment($this->kernelEnvironment);
        }

        if ($segment) {
            $this->data['segment'] = $segment->toArray();
        }

        $this->data['localization'] = $requestAttributes->getAttribute('localization');
        $this->data['resource_locator'] = $requestAttributes->getAttribute('resourceLocator');
        $this->data['resource_locator_prefix'] = $requestAttributes->getAttribute('resourceLocatorPrefix');

        $structure = null;
        if ($request->attributes->has('object')) {
            $object = $request->attributes->get('object');
            if ($object instanceof PageDimensionContent) {
                $page = $object->getResource();

                $structure = [
                    'id' => $page->getUuid(),
                    'class' => $page::class,
                    'dimensionClass' => $object::class,
                    'nodeState' => $object->getStage(),
                    'locale' => $object->getLocale(),
                    'navContexts' => $object->getNavigationContexts(),
                    'published' => $object->getWorkflowPublished(),
                    'ghostLocale' => $object->getGhostLocale(),
                    'template' => $object->getTemplateKey(),
                    'creator' => $page->getCreator(),
                    'changer' => $page->getChanger(),
                    'created' => $page->getCreated(),
                    'changed' => $page->getChanged(),
                ];
            }
        }
        $this->data['structure'] = $structure;
    }

    /**
     * @param array<array{language: string, default: bool}>|null $localizations
     */
    private function flattenLocalization(?array &$localizations): void
    {
        if (null === $localizations) {
            return;
        }
        foreach ($localizations as &$localization) {
            $localization = (string) $localization['language'] . ($localization['default'] ? ' (default)' : '');
        }
    }

    public function getName(): string
    {
        return 'sulu';
    }

    public function reset(): void
    {
        $this->data = [];
    }
}

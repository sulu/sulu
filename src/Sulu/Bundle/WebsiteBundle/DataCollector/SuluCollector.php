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

use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Util\ArrayableInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * @internal no backwards compatibility promise is given for this class it can be removed at any time
 */
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

        $requestAttributes = $request->attributes->get('_sulu');
        if (!$requestAttributes instanceof RequestAttributes) {
            return;
        }

        /** @var ?Webspace $webspace */
        $webspace = $requestAttributes->getAttribute('webspace');
        /** @var ?Portal $portal */
        $portal = $requestAttributes->getAttribute('portal');
        $segment = $requestAttributes->getAttribute('segment');

        $this->data['match_type'] = $requestAttributes->getAttribute('matchType');
        $this->data['redirect'] = $requestAttributes->getAttribute('redirect');
        $this->data['portal_url'] = $requestAttributes->getAttribute('portalUrl');
        $this->data['segment'] = $requestAttributes->getAttribute('segment');

        if ($webspace instanceof ArrayableInterface) {
            $this->data['webspace'] = $webspace->toArray();
            unset($this->data['webspace']['portals']);
            $this->flattenLocalization($this->data['webspace']['localizations']);
        }

        if ($portal instanceof Portal) {
            $this->data['portal'] = $portal->toArray();
            $environments = $this->data['portal']['environments'] ?? [];
            $this->data['portal']['environments'] = \array_combine(\array_column($environments, 'type'), $environments);
            $this->flattenLocalization($this->data['portal']['localizations']);
            $this->data['environment'] = $portal->getEnvironment($this->kernelEnvironment);
        }

        if ($segment instanceof ArrayableInterface) {
            $this->data['segment'] = $segment->toArray();
        }

        $this->data['localization'] = $requestAttributes->getAttribute('localization');
        $this->data['resource_locator'] = $requestAttributes->getAttribute('resourceLocator');
        $this->data['resource_locator_prefix'] = $requestAttributes->getAttribute('resourceLocatorPrefix');

        $structure = null;
        if ($request->attributes->has('object')) {
            $object = $request->attributes->get('object');

            $structure = [];
            if ($object instanceof DimensionContentInterface) {
                $resource = $object->getResource();

                $structure['id'] = $resource->getId();
                $structure['class'] = $resource::class;
                $structure['dimensionClass'] = $object::class;
                $structure['nodeState'] = $object->getStage();
                $structure['locale'] = $object->getLocale();
                $structure['availableLocales'] = $object->getAvailableLocales();
                $structure['ghostLocale'] = $object->getGhostLocale();

                if ($resource instanceof AuditableInterface) {
                    $structure['created'] = $this->renderUserAndTimeStamp($resource->getCreator(), $resource->getCreated());
                    $structure['changed'] = $this->renderUserAndTimeStamp($resource->getChanger(), $resource->getChanged());
                }
            }

            if ($object instanceof TemplateInterface) {
                $structure['template'] = $object->getTemplateKey();
            }

            if ($object instanceof WorkflowInterface) {
                $structure['published'] = $object->getWorkflowPublished();
            }

            if ($object instanceof PageDimensionContentInterface) {
                $structure['navContexts'] = $object->getNavigationContexts();
            }

            if ($object instanceof ShadowInterface) {
                $structure['shadowLocales'] = $object->getShadowLocales();
            }
        }
        $this->data['structure'] = $structure;
    }

    private function renderUserAndTimeStamp(?UserInterface $user, \DateTimeInterface $timestamp): string
    {
        $userName = '(unknown user)';
        if (null !== $user) {
            $userName = $user->getFullName();
        }

        return $userName . ' @ ' . $timestamp->format('Y-m-d H:i:s');
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

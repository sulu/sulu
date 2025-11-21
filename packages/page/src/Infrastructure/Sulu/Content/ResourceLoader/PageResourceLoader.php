<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content\ResourceLoader;

use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class PageResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'page';

    /**
     * @param mixed[]|null $permissions
     */
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private readonly ?Security $security,
        private readonly ?array $permissions,
        private readonly RequestAnalyzerInterface $requestAnalyzer,
    ) {
    }

    /**
     * @param string[] $ids
     */
    public function load(array $ids, ?string $locale, array $params = []): array
    {
        /** @var array{uuids: array<string>, locale?: string, stage?: string, accessControl?: array{user: UserInterface|null, permission: int}} $filters */
        $filters = ['uuids' => $ids];

        if (($params['filters'] ?? null) && \is_array($params['filters'])) {
            /** @var array{locale?: string, stage?: string, accessControl?: array{user: UserInterface|null, permission: int}} $paramsFilters */
            $paramsFilters = $params['filters'];
            $filters = \array_merge($filters, $paramsFilters);
        }

        $webspace = $this->requestAnalyzer->getWebspace();
        // @phpstan-ignore booleanAnd.leftAlwaysTrue
        if ($webspace && $webspace->hasWebsiteSecurity() && $this->security && $this->permissions && !isset($filters['accessControl'])) {
            $user = $this->security->getUser();
            $permission = $this->permissions[PermissionTypes::VIEW] ?? null;

            if (\is_int($permission)) {
                $filters['accessControl'] = [
                    'user' => $user instanceof UserInterface ? $user : null,
                    'permission' => $permission,
                ];
            }
        }

        $result = $this->pageRepository->findBy($filters);

        $mappedResult = [];
        foreach ($result as $page) {
            $mappedResult[$page->getId()] = $page;
        }

        return $mappedResult;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}

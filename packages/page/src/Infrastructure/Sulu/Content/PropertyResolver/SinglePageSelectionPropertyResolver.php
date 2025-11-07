<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver;

use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Content\ResourceLoader\PageResourceLoader;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal if you need to override this service, create a new service with based on PropertyResolverInterface instead of extending this class
 *
 * @final
 */
class SinglePageSelectionPropertyResolver implements PropertyResolverInterface
{
    /**
     * @param mixed[]|null $permissions
     */
    public function __construct(
        private readonly ?Security $security,
        private readonly ?array $permissions,
        private readonly RequestAnalyzerInterface $requestAnalyzer,
    ) {
    }

    /**
     * @param array{
     *     resourceLoader?: string,
     *     properties?: array<string, mixed>|null,
     * } $params
     */
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        if (!\is_string($data)) {
            return ContentView::create(null, ['id' => null, ...$params]);
        }

        /** @var array{locale: string, stage: string, permissionConfig?: array{user: UserInterface|null, permission: int}} $filters */
        $filters = [
            'locale' => $locale,
            'stage' => 'live',
        ];

        $webspace = $this->requestAnalyzer->getWebspace();
        // @phpstan-ignore booleanAnd.leftAlwaysTrue (PHPDoc lies, getWebspace() can return null)
        if ($webspace && $webspace->hasWebsiteSecurity() && $this->security && $this->permissions) {
            $user = $this->security->getUser();
            $permission = $this->permissions[PermissionTypes::VIEW] ?? null;

            if (\is_int($permission)) {
                $filters['permissionConfig'] = [
                    'user' => $user instanceof UserInterface ? $user : null,
                    'permission' => $permission,
                ];
            }
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? PageResourceLoader::getKey();

        return ContentView::createResolvableWithReferences(
            id: $data,
            resourceLoaderKey: $resourceLoaderKey,
            resourceKey: PageInterface::RESOURCE_KEY,
            view: [
                'id' => $data,
                ...$params,
            ],
            priority: 150,
            metadata: [
                'properties' => $params['properties'] ?? null,
                'filters' => $filters,
            ]
        );
    }

    public static function getType(): string
    {
        return 'single_page_selection';
    }
}

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

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader;

use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class MediaResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'media';

    /**
     * @param mixed[]|null $permissions
     */
    public function __construct(
        private MediaManagerInterface $mediaManager,
        private readonly ?Security $security,
        private readonly ?array $permissions,
        private readonly RequestAnalyzerInterface $requestAnalyzer,
    ) {
    }

    public function load(array $ids, ?string $locale, array $params = []): array
    {
        $permission = null;

        /** @var Webspace|null $webspace */
        $webspace = $this->requestAnalyzer->getWebspace();
        if ($webspace && $webspace->hasWebsiteSecurity() && $this->security && $this->permissions) {
            $permission = $this->permissions[PermissionTypes::VIEW] ?? null;
        }

        $result = $this->mediaManager->getByIds($ids, (string) $locale, $permission); // @phpstan-ignore arguments.count

        $mappedResult = [];
        foreach ($result as $media) {
            $mappedResult[$media->getId()] = $media;
        }

        return $mappedResult;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}

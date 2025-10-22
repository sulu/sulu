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

namespace Sulu\CustomUrl\Application\Mapper;

use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;

class CustomUrlMapper implements CustomUrlMapperInterface
{
    public function mapCustomUrlData(CustomUrlInterface $customUrl, array $data): void
    {
        $shouldGenerateRoutes = false;

        if (\array_key_exists('title', $data) && \is_string($data['title'])) {
            $customUrl->setTitle($data['title']);
        }
        if (\array_key_exists('published', $data)) {
            $customUrl->setPublished((bool) $data['published']);
        }
        if (\array_key_exists('baseDomain', $data) && \is_string($data['baseDomain'])) {
            $customUrl->setBaseDomain($data['baseDomain']);
            $shouldGenerateRoutes = true;
        }
        if (\array_key_exists('domainParts', $data) && \is_array($data['domainParts'])) {
            /** @var array<string> $domainParts */
            $domainParts = $data['domainParts'];
            $customUrl->setDomainParts($domainParts);
            $shouldGenerateRoutes = true;
        }
        if (\array_key_exists('targetLocale', $data) && \is_string($data['targetLocale'])) {
            $customUrl->setTargetLocale($data['targetLocale']);
        }
        if (\array_key_exists('targetDocument', $data) && \is_string($data['targetDocument'])) {
            $customUrl->setTargetDocument($data['targetDocument']);
        }
        if (\array_key_exists('canonical', $data)) {
            $customUrl->setCanonical((bool) $data['canonical']);
        }
        if (\array_key_exists('redirect', $data)) {
            $customUrl->setRedirect((bool) $data['redirect']);
        }
        if (\array_key_exists('noFollow', $data)) {
            $customUrl->setNoFollow((bool) $data['noFollow']);
        }
        if (\array_key_exists('noIndex', $data)) {
            $customUrl->setNoIndex((bool) $data['noIndex']);
        }

        if ($shouldGenerateRoutes) {
            $customUrl->generateRoutes();
        }
    }
}

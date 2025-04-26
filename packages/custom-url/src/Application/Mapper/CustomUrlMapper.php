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
        if (\array_key_exists('title', $data)) {
            $customUrl->setTitle($data['title']);
        }
        if (\array_key_exists('published', $data)) {
            $customUrl->setPublished((bool) $data['published']);
        }
        if (\array_key_exists('baseDomain', $data)) {
            $customUrl->setBaseDomain($data['baseDomain']);
        }
        if (\array_key_exists('domainParts', $data)) {
            $customUrl->setDomainParts($data['domainParts']);
        }
        if (\array_key_exists('targetLocale', $data)) {
            $customUrl->setTargetLocale($data['targetLocale']);
        }
        if (\array_key_exists('targetDocument', $data)) {
            $customUrl->setTargetDocument($data['targetDocument']);
        }
        if (\array_key_exists('canonical', $data)) {
            $customUrl->setCanonical($data['canonical']);
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
    }
}

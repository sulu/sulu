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

namespace Sulu\Content\Application\PropertyResolver\Resolver;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Content\Application\ContentResolver\Value\ContentView;

/**
 * Implement this interface for resolvers that need direct access to the field metadata.
 */
interface PropertyResolverMetadataAwareInterface
{
    /**
     * @param array<string, mixed> $params
     */
    public function resolve(mixed $data, string $locale, FieldMetadata $metadata, array $params = []): ContentView;

    public static function getType(): string;
}

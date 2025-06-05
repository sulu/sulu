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

namespace Sulu\Article\Infrastructure\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;

/**
 * @internal
 */
final class MetadataLoader
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        /** @var ClassMetadataInfo<object> $metadata */
        $metadata = $event->getClassMetadata();
        $reflection = $metadata->getReflectionClass();

        if ($reflection->implementsInterface(AdditionalWebspacesInterface::class)) {
            $this->addField($metadata, 'customizeWebspaceSettings', 'boolean', ['nullable' => false, 'options' => ['default' => false]]);
            $this->addField($metadata, 'additionalWebspaces', 'json', ['nullable' => true, 'options' => ['jsonb' => true]]);
        }
    }

    /**
     * @param ClassMetadataInfo<object> $metadata
     * @param array<string, mixed> $mapping
     */
    private function addField(ClassMetadataInfo $metadata, string $name, string $type = 'string', array $mapping = []): void
    {
        if ($metadata->hasField($name)) {
            return;
        }

        $nullable = true;
        if ('boolean' === $type) {
            $nullable = false;
        }

        $metadata->mapField(\array_merge([
            'fieldName' => $name,
            'columnName' => $name,
            'type' => $type,
            'nullable' => $nullable,
        ], $mapping));
    }
}
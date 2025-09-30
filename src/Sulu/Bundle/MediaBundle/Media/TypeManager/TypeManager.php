<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\TypeManager;

/**
 * Class TypeManager
 * Default Type Manager used to get correct media type by a mime type.
 */
final readonly class TypeManager implements TypeManagerInterface
{
    /**
     * @param array<array{mimeTypes: array<string>, type: string}> $mediaTypes
     */
    public function __construct(
        private array $mediaTypes,
    ) {
    }

    public function getMediaType(?string $fileMimeType): ?string
    {
        if (null === $fileMimeType) {
            return null;
        }

        foreach (\array_reverse($this->mediaTypes) as $mediaType) {
            foreach ($mediaType['mimeTypes'] as $mimeType) {
                if (\fnmatch($mimeType, $fileMimeType)) {
                    return $mediaType['type'];
                }
            }
        }

        return null;
    }
}

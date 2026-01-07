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

namespace Sulu\Content\Domain\Exception;

use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ContentNotFoundException extends \Exception
{
    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $dimensionAttributes
     */
    public function __construct(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $attributeKeys = \array_keys($dimensionAttributes);

        $availableAttributes = [];
        foreach ($contentRichEntity->getDimensionContents() as $dimensionContent) {
            if (null === $dimensionContent->getLocale()) {
                continue;
            }
            $attributes = [];
            foreach ($attributeKeys as $key) {
                $value = $propertyAccessor->getValue($dimensionContent, $key);
                $attributes[] = $key . '=' . (\is_scalar($value) ? (string) $value : '');
            }
            $availableAttributes[] = '[' . \implode(', ', $attributes) . ']';
        }

        $requestedAttributes = [];
        foreach ($dimensionAttributes as $key => $value) {
            $requestedAttributes[] = $key . '=' . (\is_scalar($value) ? (string) $value : '');
        }

        $message = \sprintf(
            'No content found for attributes [%s] with id: "%s". Available attributes: %s',
            \implode(', ', $requestedAttributes),
            $contentRichEntity->getId(),
            \implode(', ', $availableAttributes),
        );

        parent::__construct($message);
    }
}

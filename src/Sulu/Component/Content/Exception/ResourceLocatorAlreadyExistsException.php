<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

use Sulu\Component\Rest\Exception\RestExceptionInterface;
use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;

class ResourceLocatorAlreadyExistsException extends \Exception implements RestExceptionInterface, TranslationErrorMessageExceptionInterface
{
    /**
     * @param string $resourceLocator
     * @param string $path
     */
    public function __construct(
        private $resourceLocator,
        private $path,
        ?\Throwable $previous = null
    ) {
        $this->resourceLocator = $resourceLocator;
        $this->path = $path;

        parent::__construct(
            \sprintf(
                'The ResouceLocator "%s" already exists at the node "%s". Please choose a different resource locator'
                . ' or delete the existing one before reassigning it.',
                $this->resourceLocator,
                $this->path
            ),
            static::EXCEPTION_CODE_RESOURCE_LOCATOR_ALREADY_EXISTS,
            $previous
        );
    }

    /**
     * Returns the resource locator that already existed.
     *
     * @return string
     */
    public function getResourceLocator()
    {
        return $this->resourceLocator;
    }

    /**
     * Returns the path of the route node already holding the existing resource locator.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_page.resource_locator_assigned_to_other_page';
    }

    public function getMessageTranslationParameters(): array
    {
        return ['{resourceLocator}' => $this->resourceLocator];
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Exception;

use Exception;
use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;

/**
 * An instance of this exception signals that a specific key is already assigned to another category.
 */
class CategoryKeyNotUniqueException extends Exception implements TranslationErrorMessageExceptionInterface
{
    /**
     * @var mixed
     */
    private $categoryKey;

    /**
     * CategoryNotFoundException constructor.
     */
    public function __construct($categoryKey)
    {
        parent::__construct(\sprintf('The category key "%s" is already in use.', $categoryKey));

        $this->categoryKey = $categoryKey;
    }

    /**
     * @return mixed Key which is already used
     */
    public function getCategoryKey()
    {
        return $this->categoryKey;
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_category.key_assigned_to_other_category';
    }

    /**
     * @return array<string, mixed>
     */
    public function getMessageTranslationParameters(): array
    {
        return ['{key}' => $this->categoryKey];
    }
}

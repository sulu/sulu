<?php

namespace Sulu\Bundle\TagBundle\Exception;

class InvalidTagNameException extends \Exception
{
    public function __construct(string $tagName)
    {
        parent::__construct(
            sprintf('"%s" is not a valid name for tags!', $tagName)
        );
    }
}

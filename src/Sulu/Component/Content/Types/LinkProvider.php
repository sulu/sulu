<?php

declare(strict_types=1);

namespace Sulu\Component\Content\Types;

use Sulu\Component\Content\SimpleContentType;

/**
 * Link selection content type for linking to different providers.
 */
class LinkProvider extends SimpleContentType
{
    public function __construct()
    {
        parent::__construct('LinkProvider');
    }

    /**
     * {@inheritdoc}
     */
    protected function encodeValue($value)
    {
        return \json_encode($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function decodeValue($value)
    {
        if (null === $value) {
            return null;
        }

        return \json_decode($value, true);
    }
}

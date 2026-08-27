<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Exception;

/**
 * An instance of this exception signals that a resource has no view configured
 * for the requested resource view (e.g. "list" or "detail") under
 * `sulu_admin.resources.<resourceKey>.views`.
 */
class ResourceViewNotFoundException extends \Exception
{
    public function __construct(private string $resourceKey, private string $resourceView)
    {
        parent::__construct(
            \sprintf(
                'The resource "%s" has no view configured for "%s" (sulu_admin.resources.%s.views.%s).',
                $resourceKey,
                $resourceView,
                $resourceKey,
                $resourceView
            )
        );
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function getResourceView(): string
    {
        return $this->resourceView;
    }
}

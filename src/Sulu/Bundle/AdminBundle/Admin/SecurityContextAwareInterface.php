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

namespace Sulu\Bundle\AdminBundle\Admin;

/**
 * System (string) -> Section (string) -> Context (string) -> Permissions (list of strings).
 *
 * @phpstan-type SecurityContextInfo array<string, array<string, array<string, string[]>>>
 */
interface SecurityContextAwareInterface
{
    /**
     * Returns all the security contexts, which are available in the concrete bundle.
     *
     * @return SecurityContextInfo
     */
    public function getSecurityContexts();

    /**
     * Returns all the security contexts, which are available in the concrete bundle.
     *
     * @return SecurityContextInfo
     */
    public function getSecurityContextsWithPlaceholder();
}

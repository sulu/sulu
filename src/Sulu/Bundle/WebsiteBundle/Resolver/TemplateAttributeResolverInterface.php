<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

/**
 * Interface for template attributes resolver.
 */
interface TemplateAttributeResolverInterface
{
    /**
     * Returns all needed template attributes.
     *
     * @param array $customParameters
     *
     * @return array
     */
    public function resolve($customParameters = []);
}

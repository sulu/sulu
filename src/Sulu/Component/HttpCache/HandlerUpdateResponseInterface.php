<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache;

use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Update the response given by the website with headers that
 * will pass caching details to the proxy caching server.
 */
interface HandlerUpdateResponseInterface extends HandlerInterface
{
    /**
     * Update the response based on the given structure.
     *
     * @param Response           $response
     * @param StructureInterface $structure
     */
    public function updateResponse(Response $response, StructureInterface $structure);
}

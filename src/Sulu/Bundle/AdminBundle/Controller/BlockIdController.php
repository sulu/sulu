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

namespace Sulu\Bundle\AdminBundle\Controller;

use Sulu\Bundle\AdminBundle\Application\BlockIdGenerator\BlockIdGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for generating unique block IDs.
 * Used by the frontend to generate IDs when creating or duplicating blocks.
 */
class BlockIdController
{
    public function __construct(
        private readonly BlockIdGeneratorInterface $blockIdGenerator,
    ) {
    }

    public function generateAction(Request $request): Response
    {
        $length = $request->query->getInt('length', 1);

        // Generate IDs and always return array format
        $blockIds = [];
        for ($i = 0; $i < $length; ++$i) {
            $blockIds[] = ['id' => $this->blockIdGenerator->generateId()];
        }

        return new JsonResponse(['_embedded' => ['blockIds' => $blockIds]]);
    }
}

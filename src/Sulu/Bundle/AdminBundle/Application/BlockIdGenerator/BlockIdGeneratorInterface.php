<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Application\BlockIdGenerator;

interface BlockIdGeneratorInterface
{
    /**
     * Generate a unique block ID.
     *
     * The ID should be unique within the context of a page/article/snippet.
     * It is used for anchor navigation and future shadow structure features.
     */
    public function generateId(): string;
}

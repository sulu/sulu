<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Reference\Provider;

use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

interface DocumentReferenceProviderInterface
{
    public function updateReferences(UuidBehavior&TitleBehavior&StructureBehavior $document, string $locale): ReferenceCollector;

    public function removeReferences(UuidBehavior $document, ?string $locale = null): void;
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Domain\Exception;

use Sulu\Component\Rest\Exception\RemoveDependantResourcesFoundException;

class RemoveCollectionDependantResourcesFoundException extends RemoveDependantResourcesFoundException
{
    public function getTitleTranslationKey(): string
    {
        return 'sulu_media.delete_collection_dependant_warning_title';
    }

    public function getDetailTranslationKey(): string
    {
        return 'sulu_media.delete_collection_dependant_warning_detail';
    }
}

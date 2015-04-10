<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Bundle\MediaBundle\Media\TypeManager;

use Sulu\Bundle\MediaBundle\Entity\MediaType;

interface TypeManagerInterface
{
    const ENTITY_CLASS_MEDIATYPE = 'Sulu\Bundle\MediaBundle\Entity\MediaType';
    const ENTITY_NAME_MEDIATYPE = 'SuluMediaBundle:MediaType';

    /**
     * @param int $id
     * @return MediaType
     */
    public function get($id);

    /**
     * @param string $fileMimeType
     * @return integer
     */
    public function getMediaType($fileMimeType);
}

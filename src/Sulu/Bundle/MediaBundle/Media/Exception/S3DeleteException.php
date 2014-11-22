<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Exception;

/**
 * @package Sulu\Bundle\MediaBundle\Media\Exception
 */
class S3DeleteException extends MediaException
{
    /**
     * @param string $id
     */
    public function __construct($id)
    {
        parent::__construct('Media with the ID ' . $id . ' was not found', self::EXCEPTION_CODE_S3_DELETE_EXCEPTION);
    }
}

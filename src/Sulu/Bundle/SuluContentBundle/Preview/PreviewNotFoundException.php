<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Sulu\Component\Rest\Exception\RestException;

class PreviewNotFoundException extends RestException
{

    /**
     * @var int
     */
    private $userId;
    /**
     * @var string
     */
    private $contentUuid;

    function __construct($userId, $contentUuid)
    {
        parent::__construct(printf('Preview of user %s and content %s not found', $userId, $contentUuid));

        $this->contentUuid = $contentUuid;
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getContentUuid()
    {
        return $this->contentUuid;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }
}

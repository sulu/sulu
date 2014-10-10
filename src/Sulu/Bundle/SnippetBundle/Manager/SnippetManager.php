<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Manager;

use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * manages snippets
 */
class SnippetManager
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    function __construct($contentMapper, $sessionManager)
    {
        $this->contentMapper = $contentMapper;
        $this->sessionManager = $sessionManager;
    }

    public function getAll($locale)
    {
        $snippetNode = $this->sessionManager->getSnippetNode();
        // FIXME no webspace
        $result = $this->contentMapper->loadByParent($snippetNode->getIdentifier(), '???', $locale);

        return $result;
    }
} 

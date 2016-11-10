<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Exception;

use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;
use Sulu\Component\Rest\Exception\RestException;

/**
 * Keyword is used already.
 */
class KeywordNotUniqueException extends RestException
{
    /**
     * @var Keyword
     */
    private $keyword;

    public function __construct(KeywordInterface $keyword)
    {
        parent::__construct(
            sprintf('The keyword "%s" is already in use.', $keyword->getKeyword()),
            2001
        );

        $this->keyword = $keyword;
    }

    /**
     * @return Keyword
     */
    public function getKeyword()
    {
        return $this->keyword;
    }
}

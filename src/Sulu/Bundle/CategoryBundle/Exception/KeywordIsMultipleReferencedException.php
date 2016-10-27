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
 * Keyword is used in multiple categories and translations.
 */
class KeywordIsMultipleReferencedException extends RestException
{
    /**
     * @var Keyword
     */
    private $keyword;

    public function __construct(KeywordInterface $keyword)
    {
        parent::__construct(
            sprintf('The keyword "%s" is used in multiple categories or translations.', $keyword->getKeyword()),
            2002
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

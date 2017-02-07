<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Entity;

use Sulu\Component\Persistence\Repository\RepositoryInterface;

/**
 * Interface for keyword repository.
 */
interface KeywordRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns keyword.
     *
     * @param int $id
     *
     * @return KeywordInterface
     */
    public function findById($id);

    /**
     * Returns keyword.
     *
     * @param string $keyword
     * @param string $locale
     *
     * @return KeywordInterface
     */
    public function findByKeyword($keyword, $locale);
}

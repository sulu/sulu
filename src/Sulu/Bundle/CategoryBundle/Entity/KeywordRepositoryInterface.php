<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Entity;

use Sulu\Component\Persistence\Repository\RepositoryInterface;

/**
 * Interface for keyword repository.
 *
 * @extends RepositoryInterface<KeywordInterface>
 */
interface KeywordRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns keyword.
     *
     * @param int $id
     *
     * @return KeywordInterface|null
     */
    public function findById($id);

    /**
     * Returns keyword.
     *
     * @param string $keyword
     * @param string $locale
     *
     * @return KeywordInterface|null
     */
    public function findByKeyword($keyword, $locale);
}

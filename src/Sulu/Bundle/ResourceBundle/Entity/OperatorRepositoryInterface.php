<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Entity;

/**
 * The interface for the operator entity repository
 * Interface OperatorRepositoryInterface.
 */
interface OperatorRepositoryInterface
{
    /**
     * Searches for all operatory by locale.
     *
     * @param $locale
     *
     * @return mixed
     */
    public function findAllByLocale($locale);
}

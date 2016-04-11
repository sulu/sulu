<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

use Sulu\Component\Persistence\Repository\RepositoryInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Defines the interface for a UserRepository.
 */
interface UserRepositoryInterface extends UserProviderInterface, RepositoryInterface
{
    /**
     * initializes the UserRepository.
     *
     * @param string                   $suluSystem      The standard sulu system
     * @param RequestAnalyzerInterface $requestAnalyzer The RequestAnalyzer is required for getting the current security
     *
     * @return
     */
    public function init($suluSystem, RequestAnalyzerInterface $requestAnalyzer = null);

    /**
     * Returns the user with the given id.
     *
     * @param int $id The user to find
     *
     * @return UserInterface
     */
    public function findUserById($id);
}

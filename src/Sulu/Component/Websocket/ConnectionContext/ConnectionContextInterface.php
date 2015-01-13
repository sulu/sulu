<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\ConnectionContext;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\QueryString;
use Sulu\Component\Security\UserInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Represents a client websocket-session
 */
interface ConnectionContextInterface
{

    /**
     * Returns query of the upgrade request
     * @return QueryString
     */
    public function getQuery();

    /**
     * Returns upgrade request
     * @return RequestInterface
     */
    public function getRequest();

    /**
     * Returns session of the upgrade request
     * @return SessionInterface
     */
    public function getSession();

    /**
     * Returns token for given firewall
     * @param string $firewall
     * @return TokenInterface|null
     */
    public function getToken($firewall);

    /**
     * Returns user for given firewall
     * @param string $firewall
     * @return UserInterface|null
     */
    public function getUser($firewall);

    /**
     * Returns unique id for session
     * @return string
     */
    public function getId();

    /**
     * Indicates that the context is valid
     * @return boolean
     */
    public function isValid();

    /**
     * Returns parameters for current session
     * @return ParameterBag
     */
    public function getParameters();
}

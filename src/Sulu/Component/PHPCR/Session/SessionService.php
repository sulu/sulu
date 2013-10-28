<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\Session;


use PHPCR\CredentialsInterface;
use PHPCR\RepositoryFactoryInterface;
use PHPCR\RepositoryInterface;
use PHPCR\SessionInterface;
use PHPCR\SimpleCredentials;

class SessionService implements SessionServiceInterface
{
    /**
     * @var RepositoryFactoryInterface
     */
    private $factory;

    /**
     * @var string
     */
    private $parameters;

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var CredentialsInterface
     */
    private $credentials;

    /**
     * @var SessionInterface
     */
    private $session;

    function __construct($factoryClass, $url = 'http://localhost:8080/server', $user = 'admin', $password = 'admin')
    {
        $this->parameters = array('jackalope.jackrabbit_uri' => $url);
        $this->factory = new $factoryClass();
        $this->repository = $this->factory->getRepository($this->parameters);
        $this->credentials = new SimpleCredentials($user, $password);

        $this->session = $this->repository->login($this->credentials);
    }

    /**
     * returns a valid session to interact with a phpcr database
     * @param string $key
     * @return SessionInterface
     */
    public function getSession($key = 'default')
    {
        return $this->session;
    }
}

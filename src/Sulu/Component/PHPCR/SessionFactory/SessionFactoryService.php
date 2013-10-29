<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\SessionFactory;


use PHPCR\CredentialsInterface;
use PHPCR\RepositoryFactoryInterface;
use PHPCR\RepositoryInterface;
use PHPCR\SessionInterface;
use PHPCR\SimpleCredentials;

class SessionFactoryService implements SessionFactoryInterface
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

    function __construct($factory, $options)
    {
        $this->options = $this->getOptions($options);

        $this->parameters = array('jackalope.jackrabbit_uri' => $options['url']);
        $this->factory = $factory;
        $this->repository = $this->factory->getRepository($this->parameters);
        $this->credentials = new SimpleCredentials($options['user'], $options['password']);

        $this->session = $this->repository->login($this->credentials, $options['workspace']);
    }

    private function getOptions($options)
    {
        $defaults = array(
            'url' => 'http://localhost:8080/server',
            'username' => 'admin',
            'password' => 'admin',
            'workspace' => 'default'
        );
        return array_merge($defaults, $options);
    }

    /**
     * returns a valid session to interact with a phpcr database
     * @param string $key
     * @return SessionInterface
     */
    public function getSession($key = 'default')
    {
        // TODO create session for key
        return $this->session;
    }
}

<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\SessionManager;


use PHPCR\CredentialsInterface;
use PHPCR\NodeInterface;
use PHPCR\RepositoryFactoryInterface;
use PHPCR\RepositoryInterface;
use PHPCR\SessionInterface;
use PHPCR\SimpleCredentials;

class SessionManager implements SessionManagerInterface
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

    function __construct(RepositoryFactoryInterface $factory, $options)
    {
        $this->options = $this->getOptions($options);

        $this->parameters = array('jackalope.jackrabbit_uri' => $options['url']);
        $this->factory = $factory;
        $this->repository = $this->factory->getRepository($this->parameters);
        $this->credentials = new SimpleCredentials($options['username'], $options['password']);

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
     * @return SessionInterface
     */
    public function getSession()
    {
        // TODO create session for key
        return $this->session;
    }

    /**
     * returns the route node for given webspace
     * @param string $webspaceKey
     * @return NodeInterface
     */
    public function getRouteNode($webspaceKey = 'default')
    {
    }

    /**
     * returns the content node for given webspace
     * @param string $webspaceKey
     * @return NodeInterface
     */
    public function getContentNode($webspaceKey = 'default')
    {
    }
}

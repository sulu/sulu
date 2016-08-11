<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Session;

use PHPCR\SessionInterface;

/**
 * This class knows about the default and the live session, and should be used if data is written on nodes directly.
 */
class SessionManager implements SessionManagerInterface
{
    /**
     * @var SessionInterface
     */
    private $defaultSession;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    public function __construct(SessionInterface $defaultSession, SessionInterface $liveSession)
    {
        $this->defaultSession = $defaultSession;
        $this->liveSession = $liveSession;
    }

    /**
     * {@inheritdoc}
     */
    public function setNodeProperty($nodePath, $propertyName, $value)
    {
        $this->setNodePropertyForSession($this->defaultSession, $nodePath, $propertyName, $value);
        $this->setNodePropertyForSession($this->liveSession, $nodePath, $propertyName, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->defaultSession->save();
        $this->liveSession->save();
    }

    /**
     * Sets the property of the node at the given path to the given value. The change is only applied to the given
     * session.
     *
     * @param SessionInterface $session
     * @param string $nodePath
     * @param string $propertyName
     * @param mixed $value
     */
    private function setNodePropertyForSession(SessionInterface $session, $nodePath, $propertyName, $value)
    {
        $session->getNode($nodePath)->setProperty($propertyName, $value);
    }
}

<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Bundle\ContentBundle\Preview;

use Ratchet\ConnectionInterface;
use Sulu\Component\Security\UserInterface;
use Sulu\Component\Websocket\ConnectionContext\ConnectionContext;

/**
 * Connection context for preview
 */
class PreviewConnectionContext extends ConnectionContext
{
    const FIREWALL = 'admin';

    function __construct(ConnectionInterface $conn)
    {
        parent::__construct($conn);
    }

    /**
     * Return content uuid of current session
     * @return string
     */
    public function getContentUuid()
    {
        return $this->getParameters()->get('uuid');
    }

    /**
     * Set content uuid of current session
     * @param string $uuid
     */
    public function setContentUuid($uuid)
    {
        $this->getParameters()->set('uuid', $uuid);
    }

    /**
     * Return locale of current session
     * @return string
     */
    public function getLocale()
    {
        return $this->getParameters()->get('locale');
    }

    /**
     * Set locale of current session
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->getParameters()->set('locale', $locale);
    }

    /**
     * Return webspacekey of current session
     * @return string
     */
    public function getWebspaceKey()
    {
        return $this->getParameters()->get('webspace');
    }

    /**
     * Set locale of current session
     * @param string $webspaceKey
     */
    public function setWebspaceKey($webspaceKey)
    {
        $this->getParameters()->set('webspace', $webspaceKey);
    }

    /**
     * Returns user of admin firewall
     * @return null|UserInterface
     */
    public function getAdminUser()
    {
        return $this->getUser(self::FIREWALL);
    }

    /**
     * Clear preview session
     */
    public function clearSession()
    {
        $this->getParameters()->remove('uuid');
        $this->getParameters()->remove('locale');
        $this->getParameters()->remove('webspace');
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        return ($this->getUser(self::FIREWALL) !== null);
    }

    /**
     * Indicates that the preview is started and parameters set correctly
     * @return bool
     */
    public function hasContextParameters()
    {
        return (
            $this->getContentUuid() !== null &&
            $this->getLocale() !== null &&
            $this->getWebspaceKey() !== null
        );
    }
}

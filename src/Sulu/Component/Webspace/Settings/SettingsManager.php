<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Settings;

use PHPCR\NodeInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * Manages settings on top the webspace node.
 */
class SettingsManager implements SettingsManagerInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    public function __construct(SessionManagerInterface $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save($webspaceKey, $key, $data)
    {
        $propertyName = $this->propertyName($key);

        $value = $data;
        if (!($data instanceof NodeInterface)) {
            $value = json_encode($data);
        }

        $this->sessionManager->getWebspaceNode($webspaceKey)->setProperty($propertyName, $value);

        $this->sessionManager->getSession()->save();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($webspaceKey, $key)
    {
        $propertyName = $this->propertyName($key);
        if (!$this->sessionManager->getWebspaceNode($webspaceKey)->hasProperty($propertyName)) {
            return;
        }

        $property = $this->sessionManager->getWebspaceNode($webspaceKey)->getProperty($propertyName);
        $property->remove();

        $this->sessionManager->getSession()->save();
    }

    /**
     * {@inheritdoc}
     */
    public function load($webspaceKey, $key)
    {
        $propertyName = $this->propertyName($key);

        $value = $this->sessionManager->getWebspaceNode($webspaceKey)->getPropertyValueWithDefault(
            $propertyName,
            'null'
        );

        if ($value instanceof NodeInterface) {
            return $value;
        }

        return json_decode($value, true);
    }

    /**
     * {@inheritdoc}
     */
    public function loadString($webspaceKey, $key)
    {
        $propertyName = $this->propertyName($key);
        $property = $this->sessionManager->getWebspaceNode($webspaceKey)->getProperty($propertyName);

        if (!$property) {
            return;
        }

        return $property->getString();
    }

    /**
     * Returns phpcr-propertyname for given key.
     *
     * @param string $key
     *
     * @return string
     */
    private function propertyName($key)
    {
        return sprintf('settings:%s', $key);
    }
}

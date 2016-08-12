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
use Sulu\Bundle\DocumentManagerBundle\Session\SessionManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface as DeprecatedSessionManagerInterface;

/**
 * Manages settings on top the webspace node.
 */
class SettingsManager implements SettingsManagerInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var DeprecatedSessionManagerInterface
     */
    private $deprecatedSessionManager;

    public function __construct(
        SessionManagerInterface $sessionManager,
        DeprecatedSessionManagerInterface $deprecatedSessionManager
    ) {
        $this->sessionManager = $sessionManager;
        $this->deprecatedSessionManager = $deprecatedSessionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save($webspaceKey, $key, $data)
    {
        $propertyName = $this->getPropertyName($key);

        $value = $data;
        if (!($data instanceof NodeInterface)) {
            $value = json_encode($data);
        }

        $this->sessionManager->setNodeProperty(
            $this->deprecatedSessionManager->getWebspacePath($webspaceKey),
            $propertyName,
            $value
        );

        $this->sessionManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($webspaceKey, $key)
    {
        $propertyName = $this->getPropertyName($key);

        $this->sessionManager->setNodeProperty(
            $this->deprecatedSessionManager->getWebspacePath($webspaceKey),
            $propertyName,
            null
        );

        $this->sessionManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function load($webspaceKey, $key)
    {
        $propertyName = $this->getPropertyName($key);

        $value = $this->deprecatedSessionManager->getWebspaceNode($webspaceKey)->getPropertyValueWithDefault(
            $propertyName,
            'null'
        );

        return $this->decodeValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function loadString($webspaceKey, $key)
    {
        $propertyName = $this->getPropertyName($key);
        $webspaceNode = $this->deprecatedSessionManager->getWebspaceNode($webspaceKey);
        if (!$webspaceNode->hasProperty($propertyName)) {
            return;
        }

        return $webspaceNode->getProperty($propertyName)->getString();
    }

    /**
     * {@inheritdoc}
     */
    public function loadByWildcard($webspaceKey, $wildcard)
    {
        $properties = $this->deprecatedSessionManager->getWebspaceNode($webspaceKey)->getProperties(
            $this->getPropertyName($wildcard)
        );

        $data = [];
        foreach ($properties as $property) {
            $data[substr($property->getName(), 9)] = $this->decodeValue($property->getValue());
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function loadStringByWildcard($webspaceKey, $wildcard)
    {
        $webspaceNode = $this->deprecatedSessionManager->getWebspaceNode($webspaceKey);

        $properties = $webspaceNode->getProperties($this->getPropertyName($wildcard));

        $data = [];
        foreach ($properties as $property) {
            $data[substr($property->getName(), 9)] = $property->getString();
        }

        return $data;
    }

    /**
     * Returns decoded value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function decodeValue($value)
    {
        if ($value instanceof NodeInterface) {
            return $value;
        }

        return json_decode($value, true);
    }

    /**
     * Returns phpcr-propertyname for given key.
     *
     * @param string $key
     *
     * @return string
     */
    private function getPropertyName($key)
    {
        return sprintf('settings:%s', $key);
    }
}

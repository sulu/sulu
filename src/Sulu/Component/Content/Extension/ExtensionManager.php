<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Extension;

/**
 * Manages extensions
 */
class ExtensionManager implements ExtensionManagerInterface
{
    private $extensions = array();

    /**
     * {@inheritdoc}
     */
    public function getExtensions($key)
    {
        $extensions = isset($this->extensions['all']) ? $this->extensions['all'] : array();
        if (isset($this->extensions[$key])) {
            $extensions = array_merge($extensions, $this->extensions[$key]);
        }

        return $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function hasExtension($key, $name)
    {
        $extensions = $this->getExtensions($key);

        return array_key_exists($name, $extensions);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension($key, $name)
    {
        $extensions = $this->getExtensions($key);

        return isset($extensions[$name]) ? $extensions[$name] : null;
    }
}

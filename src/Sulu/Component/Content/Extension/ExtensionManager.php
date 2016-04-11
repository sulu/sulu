<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Extension;

/**
 * Manages extensions.
 */
class ExtensionManager implements ExtensionManagerInterface
{
    private $extensions = [];

    /**
     * {@inheritdoc}
     */
    public function getExtensions($structureType)
    {
        $extensions = [];

        if (isset($this->extensions['all'])) {
            $extensions = $this->extensions['all'];
        }

        if (isset($this->extensions[$structureType])) {
            $extensions = array_merge($extensions, $this->extensions[$structureType]);
        }

        return $extensions;
    }

    /**
     * TODO: This is not efficient. The extensions should be indexed by structureType.
     *
     * {@inheritdoc}
     */
    public function hasExtension($structureType, $name)
    {
        $extensions = $this->getExtensions($structureType);

        return isset($extensions[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension($structureType, $name)
    {
        $extensions = $this->getExtensions($structureType);

        if (!isset($extensions[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Extension "%s" has not been registered for structure type "%s", registred extensions: "%s"',
                $name, $structureType, implode('", "', array_keys($extensions))
            ));
        }

        return isset($extensions[$name]) ? $extensions[$name] : null;
    }

    /**
     * {@inheritdoc}
     *
     * TODO: Using "all" here is not a good idea. This means that nobody can create a structureType called "all"
     */
    public function addExtension(ExtensionInterface $extension, $structureType = 'all')
    {
        if (!isset($this->extensions[$structureType])) {
            $this->extensions[$structureType] = [];
        }

        $this->extensions[$structureType][$extension->getName()] = $extension;
    }
}

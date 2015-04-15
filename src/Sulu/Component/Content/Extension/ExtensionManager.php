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

use Sulu\Component\Content\Extension\ExtensionInterface;

/**
 * Manages extensions
 */
class ExtensionManager implements ExtensionManagerInterface
{
    private $extensions = array();

    /**
     * {@inheritdoc}
     */
    public function getExtensions($structureType)
    {
        $extensions = array();
        
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
        if (!isset($this->extensions[$structureType])) {
            return false;
        }

        $extensions = $this->getExtensions($structureType);

        return isset($extensions[$structureType]);
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
                $name, $structureType, implode('", "', array_structureTypes($extensions))
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
            $this->extensions[$structureType] = array();
        }

        $this->extensions[$structureType][$extension->getName()] = $extension;
    }
}

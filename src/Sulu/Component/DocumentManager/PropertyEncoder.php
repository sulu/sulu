<?php

namespace Sulu\Component\DocumentManager;

/**
 * Class responsible for encoding properties to PHPCR nodes.
 */
class PropertyEncoder
{
    private $namespaceRegistry;

    /**
     * @param NamespaceRegistry $namespaceRegistry
     */
    public function __construct(NamespaceRegistry $namespaceRegistry)
    {
        $this->namespaceRegistry = $namespaceRegistry;
    }

    /**
     * @param string $name
     * @param string $locale
     *
     * @return string
     */
    public function localizedSystemName($name, $locale)
    {
        return sprintf(
            '%s:%s-%s',
            $this->namespaceRegistry->getPrefix('system_localized'),
            $locale,
            $name
        );
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function systemName($name)
    {
        return sprintf(
            '%s:%s',
            $this->namespaceRegistry->getPrefix('system'),
            $name
        );
    }
}

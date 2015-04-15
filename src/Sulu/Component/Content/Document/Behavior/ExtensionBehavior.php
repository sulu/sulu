<?php

namespace Sulu\Component\Content\Document\Behavior;

/**
 * Documents implementing this behavior can have extensions applied to their
 * content.
 */
interface ExtensionBehavior extends ContentBehavior
{
    /**
     * Reutrn all extension data.
     *
     * @return array
     */
    public function getExtensionsData();

    /**
     * Set all the extension data
     */
    public function setExtensionsData($extensionData);
    
}

<?php

namespace Sulu\Component\Content\Mapper;

/**
 * Classes implementing this interface can access
 * the PhpcrContext object.
 */
interface ContentContextAwareInterface
{
    /**
     * Set the PhpcrContext
     */
    public function setContentContext(Context $context);

    /**
     * Get the Phpcr Context
     */
    public function getContentContext();
}

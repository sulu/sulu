<?php

namespace Sulu\Component\Content;

/**
 * Classes implementing this interface can access
 * the PhpcrContext object.
 */
interface ContentContextAwareInterface
{
    /**
     * Set the PhpcrContext
     */
    public function setContentContext(ContentContextInterface $context);
}

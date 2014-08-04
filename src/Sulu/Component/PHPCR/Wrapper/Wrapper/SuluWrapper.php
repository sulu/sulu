<?php

namespace Sulu\Component\PHPCR\Wrapper\Wrapper;

use Sulu\Component\PHPCR\Wrapper\WrapperInterface;
use Sulu\Component\PHPCR\Wrapper\WrapperAwareInterface;
use Sulu\Component\Content\ContentContextAwareInterface;
use Sulu\Component\Content\ContentContextInterface;

/**
 * The simple mapper simply maps nodes to the given target
 * class.
 */
class SuluWrapper extends SimpleWrapper implements ContentContextAwareInterface
{
    /**
     * @var ContentContext
     */
    protected $contentContext;

    /**
     * {@inheritDoc}
     */
    public function setContentContext(ContentContextInterface $contentContext)
    {
        $this->contentContext = $contentContext;
    }

    /**
     * {@inheritDoc}
     */
    public function wrap($object, $className)
    {
        $wrappedNode = parent::wrap($object, $className);

        if ($wrappedNode instanceof ContentContextAwareInterface) {
            $wrappedNode->setContentContext($this->contentContext);
        }

        return $wrappedNode;
    }
}

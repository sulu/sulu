<?php

namespace Sulu\Component\PHPCR;

use Sulu\Component\PhpcrDecorator\DecoratorFactoryInterface;
use Sulu\Component\Content\Structure;

class DecoratorFactory implements DecoratorFactoryInterface
{
    public function __construct(StructureManager $structureManager)
    {
        $this->structureManager = $structureManager;
    }

    public function decorate($method, $subject)
    {
        if (!is_object($subject)) {
            return $subject;
        }

        if ($subject instanceof \PHPCR\NodeInterface) {
            return $this->decorateNode($subject);
        }

        return $subject;
    }

    protected function decorateNode($node)
    {
        if ($node->isNodeType('sulu:content')) {
            return new StructureNode($node, $this);
        }
    }
}

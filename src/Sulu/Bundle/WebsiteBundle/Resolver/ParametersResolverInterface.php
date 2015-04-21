<?php

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Content\StructureInterface;

/**
 * Interface to resolve parameters for website rendering
 */
interface ParametersResolverInterface
{
    /**
     * Resolves parameter for website controller
     * @param array $parameters
     * @param StructureInterface $structure
     * @param bool $preview
     * @return mixed
     */
    public function resolve(array $parameters, StructureInterface $structure = null, $preview = false);
}

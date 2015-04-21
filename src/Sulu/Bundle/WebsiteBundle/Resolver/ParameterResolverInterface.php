<?php

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Interface to resolve parameters for website rendering
 */
interface ParameterResolverInterface
{
    /**
     * Resolves parameter for website controller
     * @param array $parameter
     * @param RequestAnalyzerInterface $requestAnalyzer
     * @param StructureInterface $structure
     * @param bool $preview
     * @return mixed
     */
    public function resolve(
        array $parameter,
        RequestAnalyzerInterface $requestAnalyzer = null,
        StructureInterface $structure = null,
        $preview = false
    );
}

<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;


use Sulu\Component\Content\Template\Dumper\PHPTemplateDumper;
use Sulu\Component\Content\Template\TemplateReader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;

/**
 * generates subclasses of structure to match template definitions.
 * this classes will be cached in Symfony cache
 */
class StructureManager extends ContainerAware implements StructureManagerInterface
{
    /**
     * @var LoaderInterface XML Loader to load templates
     */
    private $loader;

    /**
     * @var array
     */
    private $options;

    /**
     * @param LoaderInterface $loader XMLLoader to load xml templates
     * @param array $options
     * @internal param string $defaultPath array with paths to search for templates
     */
    function __construct(LoaderInterface $loader, $options = array())
    {
        $this->loader = $loader;
        $this->setOptions($options);
    }

    /**
     * returns a structure for given key
     * @param $key string
     * @return StructureInterface
     */
    public function getStructure($key)
    {
        $class = ucfirst($key) . $this->options['cache_class_suffix'];
        $cache = new ConfigCache(
            $this->options['cache_dir'] . '/' . $class . '.php',
            $this->options['debug']
        );

        if (!$cache->isFresh()) {

            $path = $this->options['template_dir'] . '/' . $key . '.xml';
            $templateReader = new TemplateReader();
            $result = $templateReader->load($path);

            $resources[] = new FileResource($path);

            $dumper = new PHPTemplateDumper($result);
            $cache->write(
                $dumper->dump(
                    array(
                        'cache_class' => $class,
                        'base_class' => $this->options['base_class']
                    )
                ),
                $resources
            );
        }

        require_once $cache;

        return new $class();
    }

    /**
     * Sets the options for the manager
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = array(
            'template_dir' => null,
            'cache_dir' => null,
            'debug' => false,
            'cache_class_suffix' => 'StructureCache',
            'base_class' => 'Sulu\Component\Content\Structure'
        );

        // overwrite the default values with the given options
        $this->options = array_merge($this->options, $options);
    }
}

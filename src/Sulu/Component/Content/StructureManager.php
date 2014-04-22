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


use Psr\Log\LoggerInterface;
use Sulu\Component\Content\Template\Dumper\PHPTemplateDumper;
use Sulu\Component\Content\Template\Exception\InvalidXmlException;
use Sulu\Component\Content\Template\Exception\TemplateNotFoundException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
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
     * @var Template\Dumper\PHPTemplateDumper
     */
    private $dumper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $options;

    /**
     * @param LoaderInterface $loader XMLLoader to load xml templates
     * @param PHPTemplateDumper $dumper
     * @param LoggerInterface $logger
     * @param array $options
     * @internal param string $defaultPath array with paths to search for templates
     */
    function __construct(
        LoaderInterface $loader,
        PHPTemplateDumper $dumper,
        LoggerInterface $logger,
        $options = array()
    )
    {
        $this->loader = $loader;
        $this->dumper = $dumper;
        $this->logger = $logger;
        $this->setOptions($options);
    }

    /**
     * returns a structure for given key
     * @param $key string
     * @throws Template\Exception\TemplateNotFoundException
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

            try {
                $result = $this->loader->load($path);
                $resources[] = new FileResource($path);
                $cache->write(
                    $this->dumper->dump(
                        $result,
                        array(
                            'cache_class' => $class,
                            'base_class' => $this->options['base_class']
                        )
                    ),
                    $resources
                );
            } catch (\InvalidArgumentException $iae) {
                $this->logger->warning(
                    'The file "' . $path . '" does not match the schema and was skipped'
                );
                throw new TemplateNotFoundException($path, $key);
            } catch (InvalidXmlException $iude) {
                $this->logger->warning(
                    'The file "' . $path . '" defined some invalid properties and was skipped'
                );
                throw new TemplateNotFoundException($path, $key);
            } catch (\Twig_Error $twige) {
                $this->logger->warning(
                    'The file "' . $path . '" content cant be rendered with the template'
                );
                throw new TemplateNotFoundException($path, $key);
            }
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

    /**
     * @return StructureInterface[]
     */
    public function getStructures()
    {
        $result = array();
        $files = glob($this->options['template_dir'].'/*.xml', GLOB_BRACE);
        foreach($files as $file) {
            $key = str_replace($this->options['template_dir'], '', $file);
            $key = str_replace('/', '', $key);
            $key = str_replace('.xml', '', $key);
            try {
                $result[] = $this->getStructure($key);
            } catch (TemplateNotFoundException $ex) {
                $this->logger->warning($ex->getMessage());
            }
        }
        return $result;
    }
}

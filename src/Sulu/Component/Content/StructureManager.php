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
use Sulu\Component\Content\StructureExtension\StructureExtensionInterface;
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
     * contains all extension
     * @var array
     */
    private $extensions = array();

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
        return $this->getStructureByFile($key, $this->getTemplate($key));
    }

    /**
     * Sets the options for the manager
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = array(
            'template_dir' => array(),
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
        foreach ($this->getTemplates() as $file) {
            $fileInfo = pathinfo($file['path']);
            $key = $fileInfo['filename'];

            try {
                $result[] = $this->getStructure($key);
            } catch (TemplateNotFoundException $ex) {
                $this->logger->warning($ex->getMessage());
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function addExtension(StructureExtensionInterface $extension, $template = 'all')
    {
        if (!isset($this->extensions[$template])) {
            $this->extensions[$template] = array();
        }

        $this->extensions[$template][] = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions($key)
    {
        $extensions = isset($this->extensions['all']) ? $this->extensions['all'] : array();
        if (isset($this->extensions[$key])) {
            $extensions = array_merge($extensions, $this->extensions[$key]);
        }

        return $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function hasExtension($key, $name)
    {
        $extensions = $this->getExtensions($key);

        return array_key_exists($name, $extensions);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension($key, $name)
    {
        $extensions = $this->getExtensions($key);

        foreach ($extensions as $extension) {
            if($extension->getName() === $name){
                return $extension;
            }
        }

        return null;
    }

    /**
     * returns structure for given template key and file
     * @param string $key
     * @param string $templateConfig
     * @return StructureInterface
     * @throws Template\Exception\TemplateNotFoundException
     */
    private function getStructureByFile($key, $templateConfig)
    {
        $fileName = $templateConfig['path'];

        $class = str_replace('-', '_', ucfirst($key)) . $this->options['cache_class_suffix'];
        $cache = new ConfigCache(
            $this->options['cache_dir'] . '/' . $class . '.php',
            $this->options['debug']
        );

        if (!$cache->isFresh()) {
            try {
                $result = $this->loader->load($fileName);

                if ($result['key'] !== $key) {
                    throw new TemplateNotFoundException($fileName, $key);
                }

                $resources[] = new FileResource($fileName);
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
            } catch (\InvalidArgumentException $e) {
                $this->logger->warning(
                    'The file "' . $fileName . '" does not match the schema and was skipped'
                );
                throw new TemplateNotFoundException($fileName, $key, $e);
            } catch (InvalidXmlException $e) {
                $this->logger->warning(
                    'The file "' . $fileName . '" defined some invalid properties and was skipped'
                );
                throw new TemplateNotFoundException($fileName, $key, $e);
            } catch (\Twig_Error $e) {
                $this->logger->warning(
                    'The file "' . $fileName . '" content cant be rendered with the template'
                );
                throw new TemplateNotFoundException($fileName, $e);
            }
        }

        require_once $cache;

        /** @var StructureInterface $structure */
        $structure = new $class();
        $structure->setInternal($templateConfig['internal']);

        return $structure;
    }

    /**
     * returns path to template
     * @param $key
     * @return bool|string
     */
    private function getTemplate($key)
    {
        $triedDirs = array();

        foreach ($this->options['template_dir'] as $templateDir) {
            $path = $templateDir['path'] . '/' . $key . '.xml';

            if (file_exists($path)) {
                return array(
                    'path' => $path,
                    'internal' => $templateDir['internal']
                );
            }

            $triedDirs[] = $templateDir['path'];
        }

        throw new \InvalidArgumentException(sprintf(
            'Could not find a template named "%s.xml" in the following directories: %s',
            $key, implode(', ', $triedDirs)
        ));
    }

    /**
     * returns a list of existing templates
     * @return string[]
     */
    private function getTemplates()
    {
        $result = array();
        foreach ($this->options['template_dir'] as $templateDir) {
            foreach (glob($templateDir['path'] . '/*.xml', GLOB_BRACE) as $path) {
                $result[] = array(
                    'path' => $path,
                    'internal' => $templateDir['internal']
                );
            }
        }

        return $result;
    }
}

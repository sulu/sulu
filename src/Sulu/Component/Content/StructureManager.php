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
use Sulu\Component\Content\Structure\Page;
use Sulu\Component\Content\Structure\Snippet;
use Sulu\Component\Content\StructureExtension\StructureExtensionInterface;
use Sulu\Component\Content\Template\Dumper\PhpTemplateDumper;
use Sulu\Component\Content\Template\Exception\InvalidXmlException;
use Sulu\Component\Content\Template\Exception\TemplateNotFoundException;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * generates subclasses of structure to match template definitions.
 * this classes will be cached in Symfony cache.
 */
class StructureManager extends ContainerAware implements StructureManagerInterface
{
    /**
     * @var LoaderInterface XML Loader to load templates
     */
    private $loader;

    /**
     * @var Template\Dumper\PhpTemplateDumper
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
     * contains all extension.
     *
     * @var array
     */
    private $extensions = array();

    /**
     * @param LoaderInterface $loader XMLLoader to load xml templates
     * @param PhpTemplateDumper $dumper
     * @param LoggerInterface $logger
     * @param array $options
     *
     * @internal param string $defaultPath array with paths to search for templates
     */
    public function __construct(
        LoaderInterface $loader,
        PhpTemplateDumper $dumper,
        LoggerInterface $logger,
        $options = array()
    ) {
        $this->loader = $loader;
        $this->dumper = $dumper;
        $this->logger = $logger;
        $this->setOptions($options);
    }

    /**
     * Sets the options for the manager.
     *
     * @param $options
     */
    public function setOptions($options)
    {
        $defaultOptions = array(
            'structure_paths' => array(),
            'cache_dir' => null,
            'debug' => false,
            'page_cache_class_suffix' => 'PageCache',
            'page_base_class' => 'Sulu\Component\Content\Structure\Page',
            'snippet_cache_class_suffix' => 'SnippetCache',
            'snippet_base_class' => 'Sulu\Component\Content\Structure\Snippet',
        );

        // overwrite the default values with the given options
        $this->options = array_merge($defaultOptions, $options);

        // FIXME find better solution for default templates in bundle
        $this->options['structure_paths'] = array_reverse($this->options['structure_paths']);
    }

    /**
     * {@inheritDoc}
     */
    public function getStructure($key, $type = Structure::TYPE_PAGE)
    {
        if (!in_array($type, array(Structure::TYPE_PAGE, Structure::TYPE_SNIPPET))) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown structure type "%s"',
                print_r($type, true)
            ));
        }

        $templateFile = $this->getTemplate($key, $type);

        return $this->getStructureByFile($key, $templateFile, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function getStructures($type = Structure::TYPE_PAGE)
    {
        $result = array();
        foreach ($this->getTemplates($type) as $file) {
            $fileInfo = pathinfo($file['path']);
            $key = $fileInfo['filename'];

            try {
                $result[] = $this->getStructure($key, $type);
            } catch (TemplateNotFoundException $e) {
                $this->logger->warning($e->getMessage());
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

        $this->extensions[$template][$extension->getName()] = $extension;
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

        return isset($extensions[$name]) ? $extensions[$name] : null;
    }

    /**
     * Returns structure for given template key and file.
     *
     * @param string $key
     * @param string $templateConfig
     * @param string $type
     *
     * @throws Template\Exception\TemplateNotFoundException
     * @throws \InvalidArgumentException
     *
     * @return StructureInterface
     */
    private function getStructureByFile($key, $templateConfig, $type)
    {
        if (!in_array($type, array('page', 'snippet'))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid structure type "%s"',
                    $type
                )
            );
        }

        $fileName = $templateConfig['path'];

        $class = str_replace('-', '_', ucfirst($key)) . $this->options[$type . '_cache_class_suffix'];

        $cache = new ConfigCache(
            $this->options['cache_dir'] . '/' . $class . '.php',
            $this->options['debug']
        );

        if (!$cache->isFresh()) {
            try {
                $result = $this->loader->load($fileName, $type);

                if ($result['key'] !== $key) {
                    throw new TemplateNotFoundException($fileName, $key);
                }

                $resources[] = new FileResource($fileName);
                $cache->write(
                    $this->dumper->dump(
                        $result,
                        array(
                            'cache_class' => $class,
                            'base_class' => $this->options[$type . '_base_class'],
                        ),
                        $type
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
     * Returns path to template.
     *
     * @param $key
     *
     * @return bool|string
     */
    private function getTemplate($key, $type)
    {
        $triedDirs = array();

        foreach ($this->options['structure_paths'] as $templateDir) {
            if ($templateDir['type'] != $type) {
                continue;
            }

            $path = $templateDir['path'] . '/' . $key . '.xml';

            if (file_exists($path)) {
                return array(
                    'path' => $path,
                    'internal' => $templateDir['internal'],
                );
            }

            $triedDirs[] = '"' . $templateDir['path'] . '"';
        }

        if (empty($triedDirs)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Could not find any template directories for structure type "%s"',
                    $type
                )
            );
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Could not find a structure template named "%s.xml" of type "%s" in the following directories: %s',
                $key,
                $type,
                implode(', ', $triedDirs)
            )
        );
    }

    /**
     * Returns a list of existing templates.
     *
     * @return string[]
     */
    private function getTemplates($type)
    {
        $result = array();
        foreach ($this->options['structure_paths'] as $templateDir) {
            if ($templateDir['type'] != $type) {
                continue;
            }

            foreach (glob($templateDir['path'] . '/*.xml', GLOB_BRACE) as $path) {
                $result[] = array(
                    'path' => $path,
                    'internal' => $templateDir['internal'],
                );
            }
        }

        return $result;
    }
}

<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Template;

use Liip\ThemeBundle\ActiveTheme;
use Psr\Log\LoggerInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Template\Dumper\PHPTemplateDumper;
use Sulu\Component\Content\Template\Exception\InvalidXmlException;
use Sulu\Component\Content\Template\Exception\TemplateNotFoundException;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Templating\EngineInterface;

/**
 * Manages templates and dumps structure cache classes
 */
class TemplateManager implements TemplateManagerInterface
{
    /**
     * @var PHPTemplateDumper
     */
    private $dumper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * XML Loader to load templates
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @var ActiveTheme
     */
    private $activeTheme;

    /**
     * @var array
     */
    private $templateDirs;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var boolean
     */
    private $debug;

    /**
     * @var string
     */
    private $cacheClassSuffix;

    /**
     * @var string
     */
    private $baseClass;

    function __construct(
        PHPTemplateDumper $dumper,
        LoaderInterface $loader,
        ActiveTheme $activeTheme,
        EngineInterface $engine,
        LoggerInterface $logger
    ) {
        $this->dumper = $dumper;
        $this->loader = $loader;
        $this->activeTheme = $activeTheme;
        $this->engine = $engine;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplates()
    {
        // TODO cache ???
        $result = array();
        foreach ($this->templateDirs as $templateDir) {
            foreach (glob($templateDir['path'] . '/*.xml', GLOB_BRACE) as $path) {
                $result[] = array(
                    'path' => $path,
                    'internal' => $templateDir['internal']
                );
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatesByTheme($theme)
    {
        $result = array();
        foreach ($this->getTemplates() as $template) {
            $fileInfo = pathinfo($template['path']);
            $key = $fileInfo['filename'];

            $structure = $this->dumpFile($key, $template['path'], $template['internal']);

            if ($this->existsInTheme($structure, $theme)) {
                $result[] = $structure->getKey();
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function dump($key)
    {
        list($filename, $internal) = $this->findTemplate($key);

        return $this->dumpFile($key, $filename, $internal);
    }

    /**
     * {@inheritdoc}
     */
    public function dumpAll()
    {
        $result = array();
        foreach ($this->getTemplates() as $template) {
            $fileInfo = pathinfo($template['path']);
            $key = $fileInfo['filename'];

            $result[] = $this->dumpFile($key, $template['path'], $template['internal']);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function dumpTemplates($templates)
    {
        $result = array();
        foreach ($templates as $template) {
            $result[] = $this->dump($template);
        }

        return $result;
    }

    /**
     * Sets the options for the manager
     * @param $options
     */
    public function setOptions($options)
    {
        $defaults = array(
            'template_dir' => array(),
            'cache_dir' => null,
            'debug' => false,
            'cache_class_suffix' => 'StructureCache',
            'base_class' => 'Sulu\Component\Content\Structure'
        );

        // overwrite the default values with the given options
        $options = array_merge($defaults, $options);

        $this->templateDirs = $options['template_dir'];
        $this->cacheDir = $options['cache_dir'];
        $this->debug = $options['debug'];
        $this->cacheClassSuffix = $options['cache_class_suffix'];
        $this->baseClass = $options['base_class'];
    }

    /**
     * Returns path to template
     * @param string $key
     * @throws \InvalidArgumentException
     * @return bool|string
     */
    private function findTemplate($key)
    {
        // TODO cache???
        $triedDirs = array();

        foreach ($this->templateDirs as $templateDir) {
            $path = $templateDir['path'] . '/' . $key . '.xml';

            if (file_exists($path)) {
                return array($path, $templateDir['internal']);
            }

            $triedDirs[] = $templateDir['path'];
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Could not find a template named "%s.xml" in the following directories: %s',
                $key,
                implode(', ', $triedDirs)
            )
        );
    }

    /**
     * Dump given structure
     * @param string $key
     * @param string $filename
     * @param string $internal
     * @return StructureInterface
     * @throws Exception\TemplateNotFoundException
     */
    private function dumpFile($key, $filename, $internal)
    {
        $class = str_replace('-', '_', ucfirst($key)) . $this->cacheClassSuffix;
        $cache = new ConfigCache(
            $this->cacheDir . '/' . $class . '.php',
            $this->debug
        );

        if (!$cache->isFresh()) {
            try {
                $result = $this->loader->load($filename);

                if ($result['key'] !== $key) {
                    throw new TemplateNotFoundException($filename, $key);
                }

                $resources[] = new FileResource($filename);
                $cache->write(
                    $this->dumper->dump(
                        $result,
                        array(
                            'cache_class' => $class,
                            'base_class' => $this->baseClass
                        )
                    ),
                    $resources
                );
            } catch (\InvalidArgumentException $e) {
                $this->logger->warning(
                    'The file "' . $filename . '" does not match the schema and was skipped'
                );
                throw new TemplateNotFoundException($filename, $key, $e);
            } catch (InvalidXmlException $e) {
                $this->logger->warning(
                    'The file "' . $filename . '" defined some invalid properties and was skipped'
                );
                throw new TemplateNotFoundException($filename, $key, $e);
            } catch (\Twig_Error $e) {
                $this->logger->warning(
                    'The file "' . $filename . '" content cant be rendered with the template'
                );
                throw new TemplateNotFoundException($filename, $e);
            }
        }

        require_once $cache;

        /** @var StructureInterface $structure */
        $structure = new $class();
        $structure->setInternal($internal);

        return $structure;
    }

    /**
     * checks if template is implemented in given theme
     */
    private function existsInTheme(StructureInterface $structure, $theme)
    {
        // backing up old theme
        $currentTheme = $this->activeTheme->getName();

        $result = true;
        //$this->activeTheme->setName($theme);
        //$this->engine->exists($structure->getView());

        // restore old theme
        $this->activeTheme->setName($currentTheme);

        return $result;
    }
} 

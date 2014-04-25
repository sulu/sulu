<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Manager;

use Psr\Log\LoggerInterface;
use Sulu\Component\Webspace\Loader\Exception\InvalidUrlDefinitionException;
use Sulu\Component\Webspace\Webspace;
use Sulu\Component\Webspace\WebspaceCollection;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Dumper\PhpWebspaceCollectionDumper;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * This class is responsible for loading, reading and caching the portal configuration files
 * @package Sulu\Bundle\CoreBundle\Portal
 */
class WebspaceManager implements WebspaceManagerInterface
{
    /**
     * @var WebspaceCollection
     */
    private $webspaceCollection;

    /**
     * @var array
     */
    private $options;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoaderInterface $loader, LoggerInterface $logger, $options = array())
    {
        $this->loader = $loader;
        $this->logger = $logger;
        $this->setOptions($options);
    }

    /**
     * Returns the webspace with the given key
     * @param $key string The key to search for
     * @return Webspace
     */
    public function findWebspaceByKey($key)
    {
        return $this->getWebspaceCollection()->getWebspace($key);
    }

    /**
     * Returns the portal with the given key
     * @param string $key The key to search for
     * @return Portal
     */
    public function findPortalByKey($key)
    {
        return $this->getWebspaceCollection()->getPortal($key);
    }

    /**
     * Returns the portal with the given url (which has not necessarily to be the main url)
     * @param string $url The url to search for
     * @param string $environment The environment in which the url should be searched
     * @return array|null
     */
    public function findPortalInformationByUrl($url, $environment)
    {
        foreach ($this->getWebspaceCollection()->getPortalInformations($environment) as $portalUrl => $portalInformation) {
            /** @var Portal $portal */
            $urlPart = $url;

            // search until every slash has been cut
            while (true) {
                if ($portalUrl == $urlPart) {
                    return $portalInformation;
                }

                if (strpos($urlPart, '/') === false) {
                    // no slash left to cut
                    break;
                }

                // cut the string at the last slash
                $urlPart = preg_replace('/(.*)\\/(.*)/', '$1', $urlPart);
            }
        }

        return null;
    }

    /**
     * Returns all possible urls for resourcelocator
     * @param string $resourceLocator
     * @param string $environment
     * @param string $languageCode
     * @param null|string $webspaceKey
     * @return array
     */
    public function findUrlsByResourceLocator($resourceLocator, $environment, $languageCode, $webspaceKey = null)
    {
        $urls = array();
        $portals = $this->getWebspaceCollection()->getPortalInformations($environment);
        foreach ($portals as $url => $portalInformation) {
            $sameLocalization = $portalInformation->getLocalization()->getLocalization() === $languageCode;
            $sameWebspace = $webspaceKey === null || $portalInformation->getWebspace()->getKey() === $webspaceKey;
            if ($sameLocalization && $sameWebspace) {
                // TODO protocol
                $urls[] = 'http://' . $url . $resourceLocator;
            }
        }
        return $urls;
    }

    /**
     * Returns all the webspaces managed by this specific instance
     * @return WebspaceCollection
     */
    public function getWebspaceCollection()
    {
        if ($this->webspaceCollection === null) {
            $class = $this->options['cache_class'];
            $cache = new ConfigCache(
                $this->options['cache_dir'] . '/' . $class . '.php',
                $this->options['debug']
            );

            if (!$cache->isFresh()) {
                $portalCollection = $this->buildWebspaceCollection();
                $dumper = new PhpWebspaceCollectionDumper($portalCollection);
                $cache->write(
                    $dumper->dump(
                        array(
                            'cache_class' => $class,
                            'base_class' => $this->options['base_class']
                        )
                    ),
                    $portalCollection->getResources()
                );
            }

            require_once $cache;

            $this->webspaceCollection = new $class();
        }

        return $this->webspaceCollection;
    }

    /**
     * Sets the options for the manager
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = array(
            'config_dir' => null,
            'cache_dir' => null,
            'debug' => false,
            'cache_class' => 'WebspaceCollectionCache',
            'base_class' => 'WebspaceCollection'
        );

        // overwrite the default values with the given options
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Builds the portal collection from the config
     * @return WebspaceCollection
     */
    protected function buildWebspaceCollection()
    {
        // Find portal configs with symfony finder
        $finder = new Finder();
        $finder->in($this->options['config_dir'])->files()->name('*.xml');

        // Iterate over config files, and add a portal object for each config to the collection
        $collection = new WebspaceCollection();

        foreach ($finder as $file) {
            /** @var SplFileInfo $file */
            try {
                $collection->add($this->loader->load($file->getRealPath()));
                $collection->addResource(new FileResource($file->getRealPath()));
            } catch (\InvalidArgumentException $iae) {
                $this->logger->warning(
                    'The file "' . $file->getRealPath() . '" does not match the schema and was skipped'
                );
            } catch (InvalidUrlDefinitionException $iude) {
                $this->logger->warning(
                    'The file "' . $file->getRealPath() . '" defined some invalid urls and was skipped'
                );
            }
        }

        return $collection;
    }
}

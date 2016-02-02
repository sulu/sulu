<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Translate;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Code;
use Sulu\Bundle\TranslateBundle\Entity\Package;
use Sulu\Bundle\TranslateBundle\Entity\Translation;
use Sulu\Bundle\TranslateBundle\Translate\Exception\PackageNotFoundException;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\Loader;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Loader\XliffFileLoader;

/**
 * Configures and starts an import from an translation catalogue.
 */
class Import
{
    const XLIFF = 0;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * The format of the file to import.
     *
     * @var int
     */
    private $format;

    /**
     * The path to look for translations in each bundle.
     *
     * @var string
     */
    private $path;

    /**
     * The locale to use for the import.
     *
     * @var string
     */
    private $locale;

    /**
     * The locale for which newly created catalogues get set as default.
     *
     * @var string
     */
    private $defaultLocale;

    /**
     * The domain of frontend translation files.
     *
     * @var string
     */
    private $frontendDomain;

    /**
     * The domain of backend translation files.
     *
     * @var string
     */
    private $backendDomain;

    /**
     * @var Output
     */
    private $output;

    /**
     * The path to the file to import.
     *
     * @var string
     */
    private $file;

    /**
     * The name of the package, in which the import will be saved.
     *
     * @var string
     */
    private $name;

    /**
     * The id of the package to override.
     * null if a new package should be created.
     *
     * @var int
     */
    private $packageId;

    public function __construct(EntityManager $em, KernelInterface $kernel)
    {
        $this->em = $em;
        $this->kernel = $kernel;
        $this->output = new NullOutput();
    }

    /**
     * Sets the format of the file to import.
     *
     * @param int $format The format of the file to import
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Sets the path.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sets the locale.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Sets the default locale.
     *
     * @param string $defaultLocale
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Sets the frontend domain.
     *
     * @param string $frontendDomain
     */
    public function setFrontendDomain($frontendDomain)
    {
        $this->frontendDomain = $frontendDomain;
    }

    /**
     * @return string
     */
    public function getFrontendDomain()
    {
        return $this->frontendDomain;
    }

    /**
     * Sets the backend domain.
     *
     * @param string $backendDomain
     */
    public function setBackendDomain($backendDomain)
    {
        $this->backendDomain = $backendDomain;
    }

    /**
     * @return string
     */
    public function getBackendDomain()
    {
        return $this->backendDomain;
    }

    /**
     * Sets the output.
     *
     * @param Output $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return Output
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Returns the format of the file to import.
     *
     * @return int The format of the file to import
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Sets the path to the file to import.
     *
     * @param string $file The path to the file to import
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Returns the file path of the import.
     *
     * @return string The file path of the import
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Sets the name of the package, in which the import will be saved.
     *
     * @param string $name The name of the package
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the package, in which the import will be saved.
     *
     * @return string The name of the package
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the id of the package to override.
     *
     * @param int $packageId
     */
    public function setPackageId($packageId)
    {
        $this->packageId = $packageId;
    }

    /**
     * Returns the id of the package to override.
     *
     * @return int
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * Deletes all translation packages.
     */
    public function resetPackages()
    {
        // load the given package and catalogue
        $packages = $this->em->getRepository('SuluTranslateBundle:Package')->findAll();
        foreach ($packages as $package) {
            $this->em->remove($package);
        }
        $this->em->flush();
    }

    /**
     * Executes the import. Imports a single file.
     *
     * @param bool $backend  True to make translations available in the backend
     * @param bool $frontend True to make translations available in the frontend
     */
    public function executeFromFile($backend = true, $frontend = false)
    {
        // get correct loader according to format
        /** @var LoaderInterface $loader */
        $loader = null;
        switch ($this->getFormat()) {
            case self::XLIFF:
                $loader = new XliffFileLoader();
                break;
        }
        $package = $this->getPackageforFile($this->packageId);
        $this->importFile($package, $loader, null, $this->file, $backend, $frontend, true);
    }

    /**
     * Executes the import. All translations found in the bundles get imported.
     *
     * @param bool $backend  True to import the backend file
     * @param bool $frontend True to import the frontend file
     */
    public function executeFromBundles($backend = true, $frontend = true)
    {
        foreach ($this->kernel->getBundles() as $bundle) {
            // access bundle path directly to ignore bundle inheritance
            $pathToTranslations = $bundle->getPath() . '/' . $this->path;

            if (is_dir($pathToTranslations)) {
                $this->importBundle($bundle, $pathToTranslations, $backend, $frontend);
            }
        }
    }

    /**
     * Imports the translations file for a bundle.
     *
     * @param BundleInterface $bundle
     * @param $path
     * @param bool $backend  True to import the backend file
     * @param bool $frontend True to import the frontend file
     */
    private function importBundle($bundle, $path, $backend, $frontend)
    {
        $this->output->writeln('<info>Bundle:</info> ' . $bundle->getName());

        // get correct loader according to format
        /** @var LoaderInterface $loader */
        $loader = null;
        $extension = '';
        switch ($this->getFormat()) {
            case self::XLIFF:
                $loader = new XliffFileLoader();
                $extension = '.xlf';
                break;
        }

        $package = $this->getPackageforBundle($bundle);
        if ($backend === true) {
            $this->importFile(
                $package, $loader,
                $path, $this->backendDomain . '.' . $this->locale . $extension);
        }
        if ($frontend === true) {
            $this->importFile(
                $package, $loader,
                $path, $this->frontendDomain . '.' . $this->locale . $extension, false, true);
        }

        $this->output->writeln('');
    }

    /**
     * Returns the package for the file import.
     *
     * @param $packageId
     *
     * @return Package
     *
     * @throws Exception\PackageNotFoundException
     */
    private function getPackageforFile($packageId)
    {
        if ($this->packageId == null) {
            // create a new package and catalogue for the import
            $package = new Package();
            $package->setName($this->name);
            $catalogue = new Catalogue();
            if ($this->locale === $this->defaultLocale) {
                $catalogue->setIsDefault(true);
            } else {
                $catalogue->setIsDefault(false);
            }
            $catalogue->setPackage($package);
            $catalogue->setLocale($this->locale);
            $package->addCatalogue($catalogue);
            $this->em->persist($catalogue);
        } else {
            // load the given package and catalogue
            $package = $this->em->getRepository('SuluTranslateBundle:Package')
                ->find($packageId);

            if (!$package) {
                throw new PackageNotFoundException($packageId);
            }

            if ($this->name) {
                $package->setName($this->name);
            }

            if (!$package) {
                // If the given package is not existing throw an exception
                throw new PackageNotFoundException($packageId);
            }
        }
        $this->em->persist($package);
        $this->em->flush();

        return $package;
    }

    /**
     * Looks if a package for a bundles exists and returns it. If not creates a new one.
     *
     * @param BundleInterface $bundle
     *
     * @return Package
     */
    private function getPackageforBundle($bundle)
    {
        $package = $this->em->getRepository('SuluTranslateBundle:Package')
            ->getPackageByName($bundle->getName());
        if (!$package) {
            $package = new Package();
            $package->setName($bundle->getName());
            $this->em->persist($package);
            $this->em->flush($package);
        }

        return $package;
    }

    /**
     * Imports a single file.
     *
     * @param Package         $package  the package to import the file into
     * @param LoaderInterface $loader
     * @param string          $path     The path to the file
     * @param string          $filename The filename
     * @param bool            $backend  True to make the file available in the backend
     * @param bool            $frontend True to make the file available in the frontend
     * @param bool            $throw    If true the methods throws exception if the a file cannot be found
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Translation\Exception\NotFoundResourceException
     */
    private function importFile($package, $loader, $path, $filename, $backend = true, $frontend = false, $throw = false)
    {
        try {
            $this->output->writeln($filename);

            $filePath = ($path) ? $path . '/' . $filename : $filename;
            $file = $loader->load($filePath, $this->locale);

            // find the catalogue from this package matching the given locale
            $catalogue = null;
            $newCatalogue = true;
            foreach ($package->getCatalogues() as $packageCatalogue) {
                /** @var $packageCatalogue Catalogue */
                if ($packageCatalogue->getLocale() === $this->locale) {
                    $catalogue = $packageCatalogue;
                    $newCatalogue = false;
                }
            }

            // if no catalogue is found create a new one
            if ($newCatalogue === true) {
                $catalogue = new Catalogue();
                if ($this->locale === $this->defaultLocale) {
                    $catalogue->setIsDefault(true);
                } else {
                    $catalogue->setIsDefault(false);
                }
                $catalogue->setPackage($package);
                $package->addCatalogue($catalogue);
                $catalogue->setLocale($this->locale);
                $this->em->persist($catalogue);
                $this->em->flush();
            }

            $allMessages = $file->all()['messages'];

            $progress = new ProgressHelper();
            $progress->start($this->output, count($allMessages));

            // loop through all translation units in the file
            foreach ($allMessages as $key => $message) {
                // Check if code is already existing in current catalogue
                if (!$newCatalogue && ($translate = $catalogue->findTranslation($key))) {
                    // Update the old translate
                    $translate->setValue($message);
                } else {
                    // Create new code, if not already existing
                    $code = $package->findCode($key);
                    if (!$code) {
                        $code = new Code();
                        $code->setPackage($package);
                        $code->setCode($key);
                        $code->setBackend($backend);
                        $code->setFrontend($frontend);
                        $this->em->persist($code);
                        $this->em->flush();
                    }

                    // Create new translate
                    $translate = new Translation();
                    $translate->setCode($code);
                    $translate->setValue($message);
                    $translate->setCatalogue($catalogue);

                    $this->em->persist($translate);
                }
                $progress->advance();
            }
            $this->em->flush();
            $progress->finish();
        } catch (\InvalidArgumentException $e) {
            if ($e instanceof NotFoundResourceException) {
                if ($throw === true) {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }
    }
}

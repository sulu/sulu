<?php
/*
 * This file is part of the Sulu CMS.
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
use Symfony\Component\Translation\Loader\XliffFileLoader;

/**
 * Configures and starts an import from an translation catalogue
 * @package Sulu\Bundle\TranslateBundle\Translate
 */
class Import
{
    const XLIFF = 0;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * The path to the file to import
     * @var string
     */
    private $file;

    /**
     * The format of the file to import
     * @var integer
     */
    private $format;

    /**
     * The local with which the file should be imported
     * @var string
     */
    private $locale;

    /**
     * The name of the package, in which the import will be saved
     * @var string
     */
    private $name;

    /**
     * The id of the package to override.
     * null if a new package should be created.
     * @var integer
     */
    private $packageId;

    function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Sets the path to the file to import
     * @param string $file The path to the file to import
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Returns the file path of the import
     * @return string The file path of the import
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Sets the format of the file to import
     * @param int $format The format of the file to import
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Returns the format of the file to import
     * @return int The format of the file to import
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Sets the locale of the import
     * @param string $locale The local of the import
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Returns the local of the import
     * @return string The local of the import
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Sets the name of the package, in which the import will be saved
     * @param string $name The name of the package
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the package, in which the import will be saved
     * @return string The name of the package
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the id of the package to override
     * @param int $packageId
     */
    public function setPackageId($packageId)
    {
        $this->packageId = $packageId;
    }

    /**
     * Returns the id of the package to override
     * @return int
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * Executes the import
     *
     * @throws Symfony\Component\Translation\Exception\NotFoundResourceException if the
     *      given file does not exist
     * @throws Symfony\Component\Translation\Exception\InvalidResourceException if the
     *      given file is not valid
     * @throws Sulu\Bundle\TranslateBundle\Translate\PackageNotFoundException if the
     *      given package cannot be found
     */
    public function execute()
    {
        // get correct loader according to format
        $loader = null;
        switch ($this->getFormat()) {
            case self::XLIFF:
                $loader = new XliffFileLoader();
                break;
        }

        $newCatalogue = true;
        if ($this->getPackageId() == null) {
            // create a new package and catalogue for the import
            $package = new Package();
            $catalogue = new Catalogue();
            $catalogue->setPackage($package);
            $this->em->persist($package);
            $this->em->persist($catalogue);
        } else {
            // load the given package and catalogue
            $package = $this->em->getRepository('SuluTranslateBundle:Package')
                ->find($this->getPackageId());

            if (!$package) {
                // If the given package is not existing throw an exception
                throw new PackageNotFoundException($this->getPackageId());
            }

            // find the catalogue from this package matching the given locale
            $catalogue = null;
            foreach ($package->getCatalogues() as $packageCatalogue) {
                /** @var $packageCatalogue Catalogue */
                if ($packageCatalogue->getLocale() == $this->getLocale()) {
                    $catalogue = $packageCatalogue;
                    $newCatalogue = false;
                }
            }

            // if no catalogue is found create a new one
            if ($newCatalogue) {
                $catalogue = new Catalogue();
                $catalogue->setPackage($package);
                $this->em->persist($catalogue);
            }
        }

        $package->setName($this->getName());
        $catalogue->setLocale($this->getLocale());

        // load the file, and create a new code/translation combination for every message
        $fileCatalogue = $loader->load($this->getFile(), $this->getLocale());
        foreach ($fileCatalogue->all()['messages'] as $key => $message) {
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
                    $code->setBackend(true);
                    $code->setFrontend(true);
                }

                // Create new translate
                $translate = new Translation();
                $translate->setCode($code);
                $translate->setValue($message);
                $translate->setCatalogue($catalogue);

                $this->em->persist($code);
                $this->em->flush(); //FIXME no flush in between, if possible
                $this->em->persist($translate);
            }
        }

        // save all the changes to the database
        $this->em->flush();
    }
}

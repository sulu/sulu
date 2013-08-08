<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
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
 * Configures and starts an import from an translation package
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
     * Executes the import
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

        // create a new package and catalogue for the import
        $package = new Package();
        $package->setName($this->getName());
        $catalogue = new Catalogue();
        $catalogue->setLocale($this->getLocale());
        $catalogue->setPackage($package);

        $this->em->persist($package);
        $this->em->persist($catalogue);

        // load the file, and create a new code/translation combination for every message
        $fileCatalogue = $loader->load($this->getFile(), $this->getLocale());
        foreach ($fileCatalogue->all()['messages'] as $key => $message) {
            $code = new Code();
            $code->setPackage($package);
            $code->setCode($key);
            $code->setBackend(true);
            $code->setFrontend(true);

            $translate = new Translation();
            $translate->setCode($code);
            $translate->setValue($message);
            $translate->setCatalogue($catalogue);

            $this->em->persist($code);
            $this->em->flush(); //FIXME no flush in between, if possible
            $this->em->persist($translate);
        }

        // save all the changes to the database
        $this->em->flush();
    }
}
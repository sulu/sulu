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
use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Configures and starts an export of a translate catalogue
 * @package Sulu\Bundle\TranslateBundle\Translate
 */
class Export {
    const XLIFF = 0;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * The id of the package to export
     * @var integer
     */
    private $packageId;

    /**
     * The locale of the catalogue to export
     * @var string
     */
    private $locale;

    /**
     * The format to export the catalogue in
     * @var string
     */
    private $format;

    /**
     * Filter for the location to export
     * @var string
     */
    private $location;

    /**
     * Defines if the backend translations should be included
     * @var boolean
     */
    private $backend;

    /**
     * The path, to which the file should exported (pointing to a directory)
     * @var string
     */
    private $path;

    /**
     * Defines if the frontend translations should be included
     * @var boolean
     */
    private $frontend;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Set the format, in which the catalogue should be exported
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Returns the format, in which the catalogue should be exported
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Sets the locale of the package, which should be exported
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Returns the locale of the package, which should be exported
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param int $packageId
     */
    public function setPackageId($packageId)
    {
        $this->packageId = $packageId;
    }

    /**
     * @return int
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * Sets the filter for the location
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * Returns the filter for the location
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Sets whether the backend translations should be included in the export or not
     * @param boolean $backend
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;
    }

    /**
     * Returns whether the backend translations should be included in the export or not
     * @return boolean
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Sets whether the frontend translations should be included in the export or not
     * @param boolean $frontend
     */
    public function setFrontend($frontend)
    {
        $this->frontend = $frontend;
    }

    /**
     * Returns whether the frontend translations should be included in the export or not
     * @return boolean
     */
    public function getFrontend()
    {
        return $this->frontend;
    }

    /**
     * Sets the path to the directory, in which the export should be located
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Returns the path to the directory, in which the export should be located.
     * If the path is not explicitly set, the current working directory will be returned
     * @return string
     */
    public function getPath()
    {
        return ($this->path != null) ? $this->path : getcwd();
    }

    public function execute()
    {
        $package = $this->em->getRepository('SuluTranslateBundle:Package')
            ->find($this->getPackageId());
        $translations = $this->em->getRepository('SuluTranslateBundle:Translation')
            ->findFiltered(
                $this->getPackageId(),
                $this->getLocale(),
                $this->getBackend(),
                $this->getFrontend(),
                $this->getLocation()
            );

        // Convert translations to format suitable for Symfony's MessageCatalogue
        $messages = array();
        foreach($translations as $translation) {
            /** @var $translation Translation */
            $messages[$translation->getCode()->getCode()] = $translation->getValue();
        }

        $messageCatalogue = new MessageCatalogue(
            $this->getLocale(),
            array($package->getName() => $messages)
        );

        $dumper = null;
        switch ($this->getFormat()) {
            case self::XLIFF:
                $dumper = new XliffFileDumper();
                break;
        }

        $dumper->dump($messageCatalogue, array('path' => $this->getPath()));
    }
}

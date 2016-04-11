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
use Sulu\Bundle\TranslateBundle\Entity\Translation;
use Sulu\Bundle\TranslateBundle\Translate\Dumper\JsonFileDumper;
use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Configures and starts an export of a translate catalogue.
 */
class Export
{
    const XLIFF = 0;
    const JSON = 1;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * The locale of the catalogue to export.
     *
     * @var string
     */
    private $locale;

    /**
     * The id of the package to export.
     *
     * @var int
     */
    private $packageId;

    /**
     * The format to export the catalogue in.
     *
     * @var string
     */
    private $format;

    /**
     * Filter for the location to export.
     *
     * @var string
     */
    private $location;

    /**
     * Defines if the backend translations should be included.
     *
     * @var bool
     */
    private $backend;

    /**
     * The path, to which the file should exported (pointing to a directory).
     *
     * @var string
     */
    private $path;

    /**
     * The name of the file to export.
     *
     * @var string
     */
    private $filename;

    /**
     * Defines if the frontend translations should be included.
     *
     * @var bool
     */
    private $frontend;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Set the format, in which the catalogue should be exported.
     *
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Sets the filename.
     *
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Returns the name of the file to export.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Returns the format, in which the catalogue should be exported.
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Sets the locale of the package, which should be exported.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Returns the locale of the package, which should be exported.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Sets the filter for the location.
     *
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * Returns the filter for the location.
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Sets whether the backend translations should be included in the export or not.
     *
     * @param bool $backend
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;
    }

    /**
     * Returns whether the backend translations should be included in the export or not.
     *
     * @return bool
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Sets whether the frontend translations should be included in the export or not.
     *
     * @param bool $frontend
     */
    public function setFrontend($frontend)
    {
        $this->frontend = $frontend;
    }

    /**
     * Returns whether the frontend translations should be included in the export or not.
     *
     * @return bool
     */
    public function getFrontend()
    {
        return $this->frontend;
    }

    /**
     * Sets the path to the directory, in which the export should be located.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Returns the path to the directory, in which the export should be located.
     * If the path is not explicitly set, the current working directory will be returned.
     *
     * @return string
     */
    public function getPath()
    {
        return ($this->path != null) ? $this->path : getcwd();
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
     * Executes the export.
     */
    public function execute()
    {
        $translations = $this->em->getRepository('SuluTranslateBundle:Translation')
            ->findFiltered(
                $this->getLocale(),
                $this->getBackend(),
                $this->getFrontend(),
                $this->getLocation(),
                $this->getPackageId()
            );

        // Convert translations to format suitable for Symfony's MessageCatalogue
        $messages = [];
        foreach ($translations as $translation) {
            /** @var $translation Translation */
            if (!array_key_exists($translation->getCode()->getCode(), $messages)) {
                $messages[$translation->getCode()->getCode()] = $translation->getValue();
            } else {
                throw new \InvalidArgumentException($translation->getCode()->getCode() . ', translation-code seems to come up multiple times');
            }
        }

        $messageCatalogue = new MessageCatalogue(
            $this->getLocale(),
            [$this->filename => $messages]
        );

        // Write the file
        $dumper = null;
        switch ($this->getFormat()) {
            case self::XLIFF:
                $dumper = new XliffFileDumper();
                break;
            case self::JSON:
                $dumper = new JsonFileDumper();
                break;
        }

        $dumper->dump($messageCatalogue, [
            'path' => $this->getPath(),
        ]);
    }
}

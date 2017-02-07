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

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Dumper\FileDumper;
use Symfony\Component\Translation\Dumper\JsonFileDumper;
use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator;

/**
 * Configures and starts an export of a translate catalogue.
 */
class Export
{
    const XLIFF = 0;
    const JSON = 1;
    const BACKEND_DOMAIN = 'backend';
    const FRONTEND_DOMAIN = 'frontend';
    const DEFAULT_LOCALE = 'en';

    /**
     * The locale of the catalogue to export.
     *
     * @var string
     */
    private $locale;

    /**
     * The format to export the catalogue in.
     *
     * @var string
     */
    private $format;

    /**
     * Defines if the backend translations should be included.
     *
     * @var bool
     */
    private $backend = true;

    /**
     * Defines if the frontend translations should be included.
     *
     * @var bool
     */
    private $frontend = false;

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
     * The translator to use for the export.
     *
     * @var Translator
     */
    private $translator;

    /**
     * The output to write information about the process to.
     *
     * @var OutputInterface
     */
    private $output;

    public function __construct(Translator $translator, OutputInterface $output)
    {
        $this->translator = $translator;
        $this->output = $output;
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
     * Executes the export by loading the catalogue and writing messages
     * from the defined domains to the defined file.
     */
    public function execute()
    {
        $messages = [];

        /** @var MessageCatalogueInterface $catalogue */
        $catalogue = $this->translator->getCatalogue($this->getLocale());

        if ($this->backend) {
            $messages = array_merge($messages, $this->getMessagesForDomain($catalogue, self::BACKEND_DOMAIN));
        }
        if ($this->frontend) {
            $messages = array_merge($messages, $this->getMessagesForDomain($catalogue, self::FRONTEND_DOMAIN));
        }

        $this->writeMessagesFile($messages);
    }

    /**
     * Gets the messages of a given catalogue and a given domain.
     *
     * @param MessageCatalogueInterface $catalogue The catalogue
     * @param $domain string The domain
     *
     * @return array The messages within the catalogue and the domain
     */
    private function getMessagesForDomain(MessageCatalogueInterface $catalogue, $domain)
    {
        $messages = $catalogue->all($domain);
        while ($catalogue = $catalogue->getFallbackCatalogue()) {
            $messages = array_replace_recursive($catalogue->all($domain), $messages);
        }

        return $messages;
    }

    /**
     * Writes an array of translation messages to the defined file.
     *
     * @param $messages array The array of messages
     */
    private function writeMessagesFile($messages)
    {
        $dumper = $this->newFileDumper();
        $messageCatalogue = new MessageCatalogue(
            $this->getLocale(),
            [$this->filename => $messages]
        );
        $dumper->dump(
            $messageCatalogue,
            [
                'path' => $this->getPath(),
                'default_locale' => self::DEFAULT_LOCALE,
                'json_encoding' => 0,
            ]
        );

        $this->output->writeln(
            sprintf(
                '<info>Exported %d translations to %s for locale %s</info>',
                count($messages),
                $this->getPath(),
                $this->getLocale()
            )
        );
    }

    /**
     * Constructs a new file dumper depending on the defined format.
     *
     * @return FileDumper The file dumper
     */
    private function newFileDumper()
    {
        if ($this->getFormat() === self::XLIFF) {
            return new XliffFileDumper();
        }

        return new JsonFileDumper();
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\FormXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\FieldMetadataValidatorInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\VarExporter\VarExporter;

class XmlFormMetadataLoader implements FormMetadataLoaderInterface, CacheWarmerInterface
{
    /**
     * @var FormXmlLoader
     */
    private $formXmlLoader;

    /**
     * @var FieldMetadataValidatorInterface
     */
    private $fieldMetadataValidator;

    /**
     * @var string[]
     */
    private $formDirectories;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(
        FormXmlLoader $formXmlLoader,
        FieldMetadataValidatorInterface $fieldMetadataValidator,
        array $formDirectories,
        string $cacheDir,
        bool $debug
    ) {
        $this->formXmlLoader = $formXmlLoader;
        $this->fieldMetadataValidator = $fieldMetadataValidator;
        $this->formDirectories = $formDirectories;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions = []): ?FormMetadata
    {
        $path = $this->getCachePath($key);

        if (\file_exists($path)) {
            $formMetadata = include $path;
            if ($formMetadata instanceof FormMetadata) {
                return $formMetadata;
            }
        }

        if (!$this->debug) {
            return null;
        }

        $this->warmUp($this->cacheDir);

        if (\file_exists($path)) {
            $formMetadata = include $path;
            if ($formMetadata instanceof FormMetadata) {
                return $formMetadata;
            }
        }

        return null;
    }

    public function warmUp($cacheDir, ?string $buildDir = null): array
    {
        $formFinder = (new Finder())->in($this->formDirectories)->name('*.xml');
        $formsMetadataCollection = [];

        foreach ($formFinder as $formFile) {
            $formMetadata = $this->formXmlLoader->load($formFile->getPathName());
            $formKey = $formMetadata->getKey();
            if (!\array_key_exists($formKey, $formsMetadataCollection)) {
                $formsMetadataCollection[$formKey] = $formMetadata;
            } else {
                $formsMetadataCollection[$formKey] = $formsMetadataCollection[$formKey]->merge($formMetadata);
            }
        }

        foreach ($formsMetadataCollection as $key => $formMetadata) {
            $this->validateItems($formMetadata->getItems(), $key);

            $path = $this->getCachePath($key);
            $this->writeCache($path, '<?php return ' . VarExporter::export($formMetadata) . ';');
        }

        return [];
    }

    /**
     * @param ItemMetadata[] $items
     */
    private function validateItems(array $items, string $formKey): void
    {
        foreach ($items as $item) {
            if ($item instanceof SectionMetadata) {
                $this->validateItems($item->getItems(), $formKey);
            }

            if ($item instanceof FieldMetadata) {
                foreach ($item->getTypes() as $type) {
                    $this->validateItems($type->getItems(), $formKey);
                }

                $this->fieldMetadataValidator->validate($item, $formKey);
            }
        }
    }

    public function isOptional(): bool
    {
        return false;
    }

    private function getCachePath(string $key): string
    {
        return \sprintf('%s%s%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, $key);
    }

    private function writeCache(string $path, string $content): void
    {
        $dir = \dirname($path);
        if (!\is_dir($dir)) {
            \mkdir($dir, 0777, true);
        }

        $tmpFile = $path . '.' . \uniqid('', true);
        \file_put_contents($tmpFile, $content);
        \rename($tmpFile, $path);

        if (\function_exists('opcache_invalidate')) {
            \opcache_invalidate($path, true);
        }
    }
}

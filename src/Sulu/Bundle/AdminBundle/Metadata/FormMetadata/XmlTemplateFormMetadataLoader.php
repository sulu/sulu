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

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\TemplateXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\FieldMetadataValidatorInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\VarExporter\VarExporter;

class XmlTemplateFormMetadataLoader implements FormMetadataLoaderInterface, CacheWarmerInterface
{
    /**
     * @param array<string, array{
     *      default_type: string|null,
     *      directories: array<string>,
     * }> $templateDirectories
     */
    public function __construct(
        private TemplateXmlLoader $templateXmlLoader,
        private FieldMetadataValidatorInterface $fieldMetadataValidator,
        private array $templateDirectories,
        private string $cacheDir,
        private bool $debug
    ) {
    }

    public function getMetadata(string $key, ?string $locale = null, array $metadataOptions = []): ?TypedFormMetadata
    {
        $path = $this->getCachePath($key);

        if (\file_exists($path)) {
            $typedFormMetadata = include $path;
            if ($typedFormMetadata instanceof TypedFormMetadata) {
                return $typedFormMetadata;
            }
        }

        if (!$this->debug) {
            return null;
        }

        $this->warmUp($this->cacheDir);

        if (\file_exists($path)) {
            $typedFormMetadata = include $path;
            if ($typedFormMetadata instanceof TypedFormMetadata) {
                return $typedFormMetadata;
            }
        }

        return null;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        foreach ($this->templateDirectories as $type => $config) {
            $defaultType = $config['default_type'] ?? null;
            $directories = \array_filter(
                $config['directories'],
                fn (string $directory) => \file_exists($directory),
            );

            $formsMetadataCollection = [];

            $typedFormMetadata = new TypedFormMetadata();
            if (null !== $defaultType) {
                $typedFormMetadata->setDefaultType($defaultType);
            }

            if (0 !== \count($directories)) {
                $formFinder = (new Finder())->in($directories)->name('*.xml');

                foreach ($formFinder as $formFile) {
                    $formMetadata = $this->templateXmlLoader->load($formFile->getPathName());
                    $formKey = $formMetadata->getKey();
                    if (!\array_key_exists($formKey, $formsMetadataCollection)) {
                        $formsMetadataCollection[$formKey] = $formMetadata;
                    } else {
                        $formsMetadataCollection[$formKey] = $formsMetadataCollection[$formKey]->merge($formMetadata);
                    }
                }
            }

            foreach ($formsMetadataCollection as $key => $formMetadata) {
                $this->validateItems($formMetadata->getItems(), $key);
                $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);
            }

            $path = $this->getCachePath($type);
            $this->writeCache($path, '<?php return ' . VarExporter::export($typedFormMetadata) . ';');
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

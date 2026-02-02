<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\TemplateXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\FieldMetadataValidatorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlTemplateFormMetadataLoader;

class XmlTemplateFormMetadataLoaderTest extends TestCase
{
    use ProphecyTrait;

    private const CACHE_DIR = __DIR__ . '/../../../../../../../../tests/Resources/cache/templates';

    private static function getCacheDir(): string
    {
        return self::CACHE_DIR;
    }

    /**
     * @var ObjectProphecy<TemplateXmlLoader>
     */
    private ObjectProphecy $templateXmlLoader;

    /**
     * @var ObjectProphecy<FieldMetadataValidatorInterface>
     */
    private ObjectProphecy $fieldMetadataValidator;

    private XmlTemplateFormMetadataLoader $loader;

    protected function setUp(): void
    {
        $this->templateXmlLoader = $this->prophesize(TemplateXmlLoader::class);
        $this->fieldMetadataValidator = $this->prophesize(FieldMetadataValidatorInterface::class);

        $cacheDir = self::getCacheDir();
        if (!\is_dir($cacheDir)) {
            \mkdir($cacheDir, 0777, true);
        }

        $this->loader = new XmlTemplateFormMetadataLoader(
            $this->templateXmlLoader->reveal(),
            $this->fieldMetadataValidator->reveal(),
            [
                'page' => [
                    'default_type' => 'default',
                    'directories' => [__DIR__ . '/dummy-templates'],
                ],
            ],
            $cacheDir,
            false
        );
    }

    protected function tearDown(): void
    {
        $cacheFile = self::getCacheDir() . '/page.php';
        if (\file_exists($cacheFile)) {
            \unlink($cacheFile);
        }
        $metaFile = $cacheFile . '.meta';
        if (\file_exists($metaFile)) {
            \unlink($metaFile);
        }
    }

    private function createFieldMetadata(string $name, string $type): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata($name);
        $fieldMetadata->setType($type);

        return $fieldMetadata;
    }

    /**
     * @param ItemMetadata[] $items
     */
    private function createFormMetadata(string $key, array $items = []): FormMetadata
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey($key);
        $formMetadata->setItems([]);

        foreach ($items as $item) {
            $formMetadata->addItem($item);
        }

        return $formMetadata;
    }

    public function testWarmUpGeneratesPhpFile(): void
    {
        $formMetadata = $this->createFormMetadata('default', [
            $this->createFieldMetadata('title', 'text_line'),
        ]);

        $this->templateXmlLoader->load(Argument::cetera())->willReturn($formMetadata);
        $this->fieldMetadataValidator->validate(Argument::cetera(), Argument::cetera())->shouldBeCalled();

        $this->loader->warmUp(self::getCacheDir());

        $cacheFile = self::getCacheDir() . '/page.php';
        $this->assertFileExists($cacheFile);

        $content = \file_get_contents($cacheFile);
        $this->assertIsString($content);
        $this->assertStringStartsWith('<?php return ', $content);
        $this->assertStringNotContainsString('unserialize', $content);
    }

    public function testGetMetadataFromCache(): void
    {
        $formMetadata = $this->createFormMetadata('default', [
            $this->createFieldMetadata('title', 'text_line'),
        ]);

        $this->templateXmlLoader->load(Argument::cetera())->willReturn($formMetadata);
        $this->fieldMetadataValidator->validate(Argument::cetera(), Argument::cetera())->shouldBeCalled();

        $this->loader->warmUp(self::getCacheDir());

        $loadedMetadata = $this->loader->getMetadata('page', 'en');

        $this->assertInstanceOf(TypedFormMetadata::class, $loadedMetadata);
        $this->assertSame('default', $loadedMetadata->getDefaultType());
        $this->assertArrayHasKey('default', $loadedMetadata->getForms());
    }

    public function testGetMetadataReturnsNullWhenCacheMissingInProdMode(): void
    {
        $result = $this->loader->getMetadata('nonexistent', 'en');

        $this->assertNull($result);
    }

    public function testGetMetadataAutoRebuildsInDebugMode(): void
    {
        $formMetadata = $this->createFormMetadata('default', [
            $this->createFieldMetadata('title', 'text_line'),
        ]);

        $this->templateXmlLoader->load(Argument::cetera())->willReturn($formMetadata);
        $this->fieldMetadataValidator->validate(Argument::cetera(), Argument::cetera())->shouldBeCalled();

        $debugLoader = new XmlTemplateFormMetadataLoader(
            $this->templateXmlLoader->reveal(),
            $this->fieldMetadataValidator->reveal(),
            [
                'page' => [
                    'default_type' => 'default',
                    'directories' => [__DIR__ . '/dummy-templates'],
                ],
            ],
            self::getCacheDir(),
            true
        );

        $loadedMetadata = $debugLoader->getMetadata('page', 'en');

        $this->assertInstanceOf(TypedFormMetadata::class, $loadedMetadata);
    }

    public function testGetMetadataNoAutoRebuildsInDebugModeUnknownKey(): void
    {
        $debugLoader = new XmlTemplateFormMetadataLoader(
            $this->templateXmlLoader->reveal(),
            $this->fieldMetadataValidator->reveal(),
            [
                'page' => [
                    'default_type' => 'default',
                    'directories' => [__DIR__ . '/dummy-templates'],
                ],
            ],
            self::getCacheDir(),
            true
        );

        $this->templateXmlLoader->load(Argument::cetera())
            ->willReturn($this->createFormMetadata('default', [
                $this->createFieldMetadata('title', 'text_line'),
            ]))
            ->shouldNotBeCalled();
        $this->fieldMetadataValidator->validate(Argument::cetera())
            ->shouldNotBeCalled();

        $loadedMetadata = $debugLoader->getMetadata('contact_details', 'en');

        $this->assertNull($loadedMetadata);
        $this->assertFileDoesNotExist(self::getCacheDir() . '/page.php');
    }
}

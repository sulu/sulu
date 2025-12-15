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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\FormXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\FieldMetadataValidatorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;

class XmlFormMetadataLoaderTest extends TestCase
{
    use ProphecyTrait;

    private const CACHE_DIR = __DIR__ . '/../../../../../../../../tests/Resources/cache';

    private static function getCacheDir(): string
    {
        return self::CACHE_DIR;
    }

    /**
     * @var ObjectProphecy<FormXmlLoader>
     */
    private $formXmlLoader;

    /**
     * @var ObjectProphecy<FieldMetadataValidatorInterface>
     */
    private $fieldMetadataValidator;

    /**
     * @var XmlFormMetadataLoader
     */
    private $xmlFormMetadataLoader;

    protected function setUp(): void
    {
        $this->formXmlLoader = $this->prophesize(FormXmlLoader::class);
        $this->fieldMetadataValidator = $this->prophesize(FieldMetadataValidatorInterface::class);

        $this->xmlFormMetadataLoader = new XmlFormMetadataLoader(
            $this->formXmlLoader->reveal(),
            $this->fieldMetadataValidator->reveal(),
            [
                __DIR__ . '/dummy-forms',
            ],
            self::getCacheDir(),
            false
        );
    }

    /**
     * @param FormMetadata[] $types
     */
    private function createFieldMetadata(
        string $name,
        string $type,
        array $types = []
    ): FieldMetadata {
        $fieldMetadata = new FieldMetadata($name);
        $fieldMetadata->setType($type);

        foreach ($types as $type) {
            $fieldMetadata->addType($type);
        }

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

    /**
     * @param ItemMetadata[] $items
     */
    private function createSectionMetadata(string $name, array $items = []): SectionMetadata
    {
        $sectionMetadata = new SectionMetadata($name);

        foreach ($items as $item) {
            $sectionMetadata->addItem($item);
        }

        return $sectionMetadata;
    }

    protected function tearDown(): void
    {
        $cacheFile = self::getCacheDir() . '/some_form_key.php';
        if (\file_exists($cacheFile)) {
            \unlink($cacheFile);
        }
        $metaFile = $cacheFile . '.meta';
        if (\file_exists($metaFile)) {
            \unlink($metaFile);
        }
    }

    public function testWarmUp(): void
    {
        $propertyMetadata = $this->createFieldMetadata('some_property', 'text_line');
        $sectionPropertyMetadata = $this->createFieldMetadata('some_section_property', 'text_line');
        $sectionMetadata = $this->createSectionMetadata('some_section', [$sectionPropertyMetadata]);
        $blockPropertyMetadata = $this->createFieldMetadata('some_block_property', 'text_line');
        $blockTypeMetadata = $this->createFormMetadata('some_block_type_key', [$blockPropertyMetadata]);
        $blockMetadata = $this->createFieldMetadata('some_block', 'block', [$blockTypeMetadata]);
        $formMetadata = $this->createFormMetadata('some_form_key', [$propertyMetadata, $sectionMetadata, $blockMetadata]);

        $this->formXmlLoader->load(Argument::cetera())
            ->willReturn($formMetadata);

        $this->fieldMetadataValidator->validate($propertyMetadata, 'some_form_key')->shouldBeCalled();
        $this->fieldMetadataValidator->validate($sectionPropertyMetadata, 'some_form_key')->shouldBeCalled();
        $this->fieldMetadataValidator->validate($blockPropertyMetadata, 'some_form_key')->shouldBeCalled();
        $this->fieldMetadataValidator->validate($blockMetadata, 'some_form_key')->shouldBeCalled();

        $this->xmlFormMetadataLoader->warmUp(self::getCacheDir());
    }

    public function testWarmUpGeneratesPhpFile(): void
    {
        $formMetadata = $this->createFormMetadata('some_form_key', [
            $this->createFieldMetadata('test_field', 'text_line'),
        ]);

        $this->formXmlLoader->load(Argument::cetera())->willReturn($formMetadata);
        $this->fieldMetadataValidator->validate(Argument::cetera(), Argument::cetera())->shouldBeCalled();

        $this->xmlFormMetadataLoader->warmUp(self::getCacheDir());

        $cacheFile = self::getCacheDir() . '/some_form_key.php';
        $this->assertFileExists($cacheFile);

        $content = \file_get_contents($cacheFile);
        $this->assertIsString($content);
        $this->assertStringStartsWith('<?php return ', $content);
        $this->assertStringNotContainsString('unserialize', $content);
    }

    public function testGetMetadataFromCache(): void
    {
        $formMetadata = $this->createFormMetadata('some_form_key', [
            $this->createFieldMetadata('test_field', 'text_line'),
        ]);

        $this->formXmlLoader->load(Argument::cetera())->willReturn($formMetadata);
        $this->fieldMetadataValidator->validate(Argument::cetera(), Argument::cetera())->shouldBeCalled();

        $this->xmlFormMetadataLoader->warmUp(self::getCacheDir());

        $loadedMetadata = $this->xmlFormMetadataLoader->getMetadata('some_form_key', 'en');

        $this->assertInstanceOf(FormMetadata::class, $loadedMetadata);
        $this->assertSame('some_form_key', $loadedMetadata->getKey());
        $this->assertCount(1, $loadedMetadata->getItems());
    }

    public function testGetMetadataReturnsNullWhenCacheMissingInProdMode(): void
    {
        $result = $this->xmlFormMetadataLoader->getMetadata('nonexistent_form', 'en');

        $this->assertNull($result);
    }

    public function testGetMetadataAutoRebuildsInDebugMode(): void
    {
        $formMetadata = $this->createFormMetadata('some_form_key', [
            $this->createFieldMetadata('test_field', 'text_line'),
        ]);

        $this->formXmlLoader->load(Argument::cetera())->willReturn($formMetadata);
        $this->fieldMetadataValidator->validate(Argument::cetera(), Argument::cetera())->shouldBeCalled();

        $debugLoader = new XmlFormMetadataLoader(
            $this->formXmlLoader->reveal(),
            $this->fieldMetadataValidator->reveal(),
            [__DIR__ . '/dummy-forms'],
            self::getCacheDir(),
            true
        );

        $loadedMetadata = $debugLoader->getMetadata('some_form_key', 'en');

        $this->assertInstanceOf(FormMetadata::class, $loadedMetadata);
        $this->assertSame('some_form_key', $loadedMetadata->getKey());
    }
}

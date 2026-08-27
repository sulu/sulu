<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Application\ContentNormalizer\Normalizer;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Application\ContentNormalizer\Normalizer\TableTemplateNormalizer;
use Sulu\Content\Application\ContentNormalizer\Normalizer\TemplateNormalizer;
use Sulu\Content\Domain\Table\TableData;
use Sulu\Content\Domain\Table\TableTemplateDataNormalizer;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class TableTemplateNormalizerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    public function testEnhanceNormalizesTableFieldsBeforeTemplateFlattening(): void
    {
        $specsField = new FieldMetadata('specs');
        $specsField->setType('table');

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('default');
        $formMetadata->addItem($specsField);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm($formMetadata);

        $metadataProvider = $this->prophesize(MetadataProviderInterface::class);
        $metadataProvider->getMetadata('example', 'en', [])->willReturn($typedFormMetadata);

        $metadataProviderRegistry = $this->prophesize(MetadataProviderRegistry::class);
        $metadataProviderRegistry->getMetadataProvider('form')->willReturn($metadataProvider->reveal());

        $dimensionContent = new ExampleDimensionContent(new \Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example());
        $dimensionContent->setLocale('en');
        $dimensionContent->setTemplateKey('default');

        $tableNormalizer = new TableTemplateNormalizer(
            $metadataProviderRegistry->reveal(),
            new TableTemplateDataNormalizer(),
        );
        $templateNormalizer = new TemplateNormalizer();

        $normalizedData = [
            'templateData' => [
                'specs' => [
                    'head' => ['A'],
                    'body' => [['1']],
                ],
            ],
            'templateKey' => 'default',
        ];

        $normalizedData = $tableNormalizer->enhance($dimensionContent, $normalizedData);
        $result = $templateNormalizer->enhance($dimensionContent, $normalizedData);

        $this->assertSame([
            'version' => TableData::VERSION,
            'head' => ['A'],
            'body' => [
                [
                    ['text' => '1', 'bold' => false, 'italic' => false, 'underline' => false],
                ],
            ],
        ], $result['specs']);
        $this->assertSame('default', $result['template']);
        $this->assertArrayNotHasKey('templateData', $result);
    }

    public function testEnhanceIgnoresNonTemplateObjects(): void
    {
        $metadataProviderRegistry = $this->prophesize(MetadataProviderRegistry::class);
        $metadataProviderRegistry->getMetadataProvider(Argument::any())->shouldNotBeCalled();

        $normalizer = new TableTemplateNormalizer(
            $metadataProviderRegistry->reveal(),
            new TableTemplateDataNormalizer(),
        );

        $data = ['templateData' => ['specs' => []]];

        $this->assertSame($data, $normalizer->enhance(new \stdClass(), $data));
    }
}

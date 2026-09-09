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

namespace Sulu\Content\Tests\Functional\Infrastructure\Sulu\Preview;

use Sulu\Bundle\PreviewBundle\Preview\PreviewContext;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Sulu\Preview\ContentObjectProvider;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ContentObjectProviderTest extends SuluTestCase
{
    /**
     * @var ContentObjectProvider<ExampleDimensionContent, Example>
     */
    private ContentObjectProvider $contentObjectProvider;

    protected function setUp(): void
    {
        /** @var ContentObjectProvider<ExampleDimensionContent, Example> $contentObjectProvider */
        $contentObjectProvider = self::getContainer()->get('example_test.example_preview_object_provider');
        $this->contentObjectProvider = $contentObjectProvider;
    }

    /**
     * Regression test for #8574: the preview must map the localized and unlocalized dimension
     * contents as separate instances and merge them back together, so that unlocalized values
     * (here the "unlocalizedValue" property of the "preview-unlocalized" template) survive
     * alongside the localized ones instead of being overwritten.
     */
    public function testUpdateValuesMergesUnlocalizedAndLocalizedValues(): void
    {
        $example = new Example();

        $unlocalizedDimensionContent = $example->createDimensionContent();
        $unlocalizedDimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);
        $example->addDimensionContent($unlocalizedDimensionContent);

        $localizedDimensionContent = $example->createDimensionContent();
        $localizedDimensionContent->setLocale('de');
        $localizedDimensionContent->setStage(DimensionContentInterface::STAGE_DRAFT);
        $localizedDimensionContent->setTemplateKey('preview-unlocalized');
        $example->addDimensionContent($localizedDimensionContent);

        $previewContext = new PreviewContext(1, 'de');
        $defaults = [
            'object' => $localizedDimensionContent,
            '_controller' => 'Sulu\Content\UserInterface\Controller\Website\ContentController::indexAction',
            'view' => 'pages/default',
        ];
        $data = [
            'template' => 'preview-unlocalized',
            'title' => 'Preview Title',
            'unlocalizedValue' => 'shared value',
        ];

        $result = $this->contentObjectProvider->updateValues($previewContext, $defaults, $data);

        $object = $result['object'];
        $this->assertInstanceOf(ExampleDimensionContent::class, $object);
        $this->assertSame($example, $object->getResource());

        $templateData = $object->getTemplateData();
        $this->assertSame('Preview Title', $templateData['title'] ?? null, 'localized value must be mapped');
        $this->assertSame(
            'shared value',
            $templateData['unlocalizedValue'] ?? null,
            'unlocalized value must survive the merge'
        );

        // the mapped source dimensions stay separate instances (this was the actual bug)
        $this->assertSame(['unlocalizedValue' => 'shared value'], $unlocalizedDimensionContent->getTemplateData());
        $this->assertSame('Preview Title', $localizedDimensionContent->getTemplateData()['title'] ?? null);
        $this->assertNotSame($unlocalizedDimensionContent, $localizedDimensionContent);
    }
}

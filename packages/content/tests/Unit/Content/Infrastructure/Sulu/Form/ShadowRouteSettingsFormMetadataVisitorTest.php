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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Form;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Content\Infrastructure\Sulu\Form\ShadowRouteSettingsFormMetadataVisitor;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShadowRouteSettingsFormMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    private ShadowRouteSettingsFormMetadataVisitor $visitor;

    protected function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->translator->trans('sulu_content.shadow_route', [], 'admin', 'de')->willReturn('URL');

        $this->visitor = new ShadowRouteSettingsFormMetadataVisitor($this->translator->reveal());
    }

    public function testAddsMirroredRouteFieldToShadowSection(): void
    {
        [$formMetadata, $shadowSection] = $this->createSettingsFormMetadata();

        $this->visitor->visitFormMetadata($formMetadata, 'de', ['templateType' => 'page']);

        $field = $shadowSection->getItems()['url'] ?? null;
        $this->assertInstanceOf(FieldMetadata::class, $field);
        $this->assertSame('shadow_route', $field->getType());
        $this->assertSame('URL', $field->getLabel('de'));
        $this->assertSame('shadowOn == true', $field->getVisibleCondition());
    }

    public function testIgnoresContentWithoutRoute(): void
    {
        [$formMetadata, $shadowSection] = $this->createSettingsFormMetadata();

        $this->visitor->visitFormMetadata($formMetadata, 'de', ['forms' => []]);

        $this->assertSame([], $shadowSection->getItems());
    }

    public function testIgnoresOtherForms(): void
    {
        [$formMetadata, $shadowSection] = $this->createSettingsFormMetadata('content_seo');

        $this->visitor->visitFormMetadata($formMetadata, 'de', ['templateType' => 'page']);

        $this->assertSame([], $shadowSection->getItems());
    }

    public function testIgnoresSettingsWithoutShadowSection(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('content_settings');

        $this->visitor->visitFormMetadata($formMetadata, 'de', ['templateType' => 'page']);

        $this->assertSame([], $formMetadata->getItems());
    }

    /**
     * @return array{FormMetadata, SectionMetadata}
     */
    private function createSettingsFormMetadata(string $key = 'content_settings'): array
    {
        $shadowSection = new SectionMetadata('shadow');
        $formMetadata = new FormMetadata();
        $formMetadata->setKey($key);
        $formMetadata->addItem($shadowSection);

        return [$formMetadata, $shadowSection];
    }
}

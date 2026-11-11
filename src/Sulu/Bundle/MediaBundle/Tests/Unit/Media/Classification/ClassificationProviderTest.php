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

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\Classification;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Media\Classification\ClassificationProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class ClassificationProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private ObjectProphecy $translator;

    protected function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
    }

    public function testGetValuesTranslatesTheTitles(): void
    {
        $this->translator->trans('app.data_sheet', [], 'admin', 'de')->willReturn('Datenblatt');
        $this->translator->trans('app.warranty_certificate', [], 'admin', 'de')->willReturn('Garantieschein');

        $provider = new ClassificationProvider(
            ['data_sheet' => 'app.data_sheet', 'warranty_certificate' => 'app.warranty_certificate'],
            $this->translator->reveal(),
        );

        $this->assertTrue($provider->hasClassifications());
        $this->assertSame(
            [
                ['name' => 'data_sheet', 'title' => 'Datenblatt'],
                ['name' => 'warranty_certificate', 'title' => 'Garantieschein'],
            ],
            $provider->getValues('de'),
        );
    }

    public function testWithoutClassifications(): void
    {
        $provider = new ClassificationProvider([], $this->translator->reveal());

        $this->assertFalse($provider->hasClassifications());
        $this->assertSame([], $provider->getValues('en'));
    }
}

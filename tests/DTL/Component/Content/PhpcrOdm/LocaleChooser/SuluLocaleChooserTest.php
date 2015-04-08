<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\PhpcrOdm\LocaleChooser;

use Prophecy\PhpUnit\ProphecyTestCase;
use DTL\Component\Content\PhpcrOdm\LocaleChooser\SuluLocaleChooser;
use Sulu\Component\Webspace\Webspace;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use DTL\Component\Content\Document\DocumentInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Localization\Localization;
use DTL\Component\Content\PhpcrOdm\DocumentNodeHelper;
use PHPCR\NodeInterface;

class SuluLocaleChooserTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->document = $this->prophesize(DocumentInterface::class);
        $this->classMetadata = $this->prophesize(ClassMetadata::class);
        $this->documentNodeHelper = $this->prophesize(DocumentNodeHelper::class);
        $this->node = $this->prophesize(NodeInterface::class);

        $this->chooser = new SuluLocaleChooser(
            $this->requestAnalyzer->reveal(),
            $this->webspaceManager->reveal(),
            $this->documentNodeHelper->reveal()
        );

    }

    public function testNoWebspace()
    {
        $this->document->getPhpcrNode()->willReturn($this->node->reveal());
        $this->requestAnalyzer->getWebspace()->willReturn(null);
        $this->documentNodeHelper->getLocales($this->node->reveal())->willReturn(array('fr', 'en', 'de'));
        $this->document->setRequestedLocale('de')->shouldBeCalled();
        $locales = $this->chooser->getFallbackLocales(
            $this->document->reveal(),
            $this->classMetadata->reveal(),
            'de'
        );

        $this->assertEquals(array('fr', 'en'), $locales);
    }

    public function testWebspace()
    {
        $forLocale = 'de';

        $this->document->setRequestedLocale('de')->shouldBeCalled();
        $this->document->getPhpcrNode()->willReturn($this->node->reveal());
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $webspaceLocalization = $this->createLocalization('de_at');
        $parentLocalization = $this->createLocalization('de', true);
        $childLocalization1 = $this->createLocalization('en', false, true);
        $childLocalization2 = $this->createLocalization('fr', false, true);
        $webspaceLocalization->getParent()->willReturn($parentLocalization);
        $webspaceLocalization->getChildren()->willReturn(array(
            $childLocalization1,
            $childLocalization2,
        ));
        $this->documentNodeHelper->getLocales($this->node->reveal())->willReturn(array('fr', 'en', 'de', 'jp'));

        $this->webspace->getLocalization($forLocale)->willReturn($webspaceLocalization->reveal());

        $locales = $this->chooser->getFallbackLocales(
            $this->document->reveal(),
            $this->classMetadata->reveal(),
            $forLocale
        );

        $this->assertEquals(array('de_at', 'en', 'fr', 'jp'), $locales);
    }

    private function createLocalization($locale, $parentNull = false, $childrenEmpty = false)
    {
        $localization = $this->prophesize(Localization::class);
        $localization->getLocalization('_')->willReturn($locale);

        $localization->getParent()->willReturn(null);
        $localization->getChildren()->willReturn(array());

        return $localization;
    }
}

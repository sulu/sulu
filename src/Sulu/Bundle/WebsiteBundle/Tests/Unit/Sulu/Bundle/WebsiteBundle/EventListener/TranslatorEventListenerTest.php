<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Bundle\PreviewBundle\Preview\Events\PreRenderEvent;
use Sulu\Bundle\WebsiteBundle\EventListener\TranslatorEventListener;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\Translation\TranslatorInterface;

class TranslatorEventListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testSetLocaleOnPreviewPreRender()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $eventListener = new TranslatorEventListener($translator->reveal());
        $eventListener->setLocaleOnPreviewPreRender(new PreRenderEvent(new RequestAttributes(['locale' => 'de'])));

        $translator->setLocale('de')->shouldBeCalled();
    }
}

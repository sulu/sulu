<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\EventListener;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\WebsiteBundle\EventListener\TranslatorListener;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Translation\Translator;

class TranslatorListenerTest extends TestCase
{
    public function testOnKernelRequest()
    {
        $translator = $this->prophesize(Translator::class);
        $responseEvent = $this->prophesize(GetResponseEvent::class);
        $request = new Request();
        $localization = new Localization('de', 'at');
        $requestAttributes = new RequestAttributes([
            'localization' => $localization,
        ]);
        $request->attributes->set('_sulu', $requestAttributes);
        $responseEvent->getRequest()->willReturn($request);

        $eventListener = new TranslatorListener($translator->reveal());

        $translator->setLocale('de_AT')->shouldBeCalled();

        $eventListener->onKernelRequest($responseEvent->reveal());
    }
}

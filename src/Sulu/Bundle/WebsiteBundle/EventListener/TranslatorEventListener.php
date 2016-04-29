<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Bundle\PreviewBundle\Preview\Events\PreRenderEvent;
use Symfony\Component\Translation\TranslatorInterface;

class TranslatorEventListener
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Set locale to translator.
     *
     * @param PreRenderEvent $event
     */
    public function setLocaleOnPreviewPreRender(PreRenderEvent $event)
    {
        $this->translator->setLocale($event->getAttribute('locale'));
    }
}

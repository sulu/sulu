<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Translator;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Wrapper for translator to lazy initialize locale with request-analyzer.
 */
class RequestLocaleTranslator implements TranslatorInterface
{
    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(TranslatorInterface $translator, RequestStack $requestStack)
    {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        $this->initialize();

        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        $this->initialize();

        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale)
    {
        // don't initialize here because of the TranslateListener (will be called on every request)

        return $this->translator->setLocale($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
        $this->initialize();

        return $this->translator->getLocale();
    }

    private function initialize()
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($this->initialized || !$request || !$request->attributes->has('_sulu')) {
            return;
        }

        /** @var RequestAttributes $requestAttributes */
        $requestAttributes = $request->attributes->get('_sulu');
        $localization = $requestAttributes->getAttribute('localization');
        if (!$localization) {
            return;
        }

        $this->translator->setLocale($localization->getLocale(Localization::LCID));
        $this->initialized = true;
    }
}

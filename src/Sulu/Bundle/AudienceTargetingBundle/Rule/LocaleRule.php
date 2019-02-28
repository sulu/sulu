<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Rule;

use Sulu\Bundle\AudienceTargetingBundle\Rule\Type\Text;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * This rule determines if the request has been sent in the desired language.
 */
class LocaleRule implements RuleInterface
{
    const LOCALE = 'locale';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate(array $options)
    {
        if (!isset($options[static::LOCALE])) {
            return false;
        }

        $languages = $this->requestStack->getCurrentRequest()->getLanguages();
        if (!$languages) {
            return false;
        }

        return substr($languages[0], 0, 2) === strtolower($options[static::LOCALE]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans('sulu_audience_targeting.rules.locale', [], 'backend');
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return new Text(static::LOCALE);
    }
}

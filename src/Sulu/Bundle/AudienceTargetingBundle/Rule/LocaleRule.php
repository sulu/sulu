<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Rule;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This rule determines if the request has been sent in the desired language.
 */
class LocaleRule implements RuleInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate(array $options)
    {
        if (!isset($options['locale'])) {
            return false;
        }

        $languages = $this->requestStack->getCurrentRequest()->getLanguages();
        if (!$languages) {
            return false;
        }

        return substr($languages[0], 0, 2) === $options['locale'];
    }
}

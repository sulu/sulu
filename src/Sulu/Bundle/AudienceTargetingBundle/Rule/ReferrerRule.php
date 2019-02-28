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

class ReferrerRule implements RuleInterface
{
    const REFERRER = 'referrer';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $referrerHeader;

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator, $referrerHeader = null)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->referrerHeader = $referrerHeader;
    }

    /**
     * Returns a string representation of the evaluation of the rule for the current context.
     *
     * @param array $options The options to evaluate against
     *
     * @return bool
     */
    public function evaluate(array $options)
    {
        $request = $this->requestStack->getCurrentRequest();
        $referrer = $request->headers->get('referer');
        if ($this->referrerHeader && $request->headers->has($this->referrerHeader)) {
            $referrer = $request->headers->get($this->referrerHeader);
        }

        return (bool) preg_match(
            '/^' . str_replace(['*', '/'], ['(.*)', '\/'], $options[static::REFERRER]) . '$/',
            $referrer
        );
    }

    /**
     * Returns the translated name for the given Rule.
     *
     * @return string
     */
    public function getName()
    {
        return $this->translator->trans('sulu_audience_targeting.rules.referrer', [], 'backend');
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return new Text(static::REFERRER);
    }
}

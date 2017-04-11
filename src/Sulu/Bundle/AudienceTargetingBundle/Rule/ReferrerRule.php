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
use Symfony\Component\Translation\TranslatorInterface;

class ReferrerRule implements RuleInterface
{
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
     * Returns a string representation of the evaluation of the rule for the current context.
     *
     * @param array $options The options to evaluate against
     *
     * @return bool
     */
    public function evaluate(array $options)
    {
        return (bool) preg_match(
            '/^' . str_replace('*', '(.*)', $options['referrer']) . '$/',
            $this->requestStack->getCurrentRequest()->headers->get('referer')
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
    public function getTemplate()
    {
        return '<div class="grid-col-12">
                <input class="form-element" type="text" data-condition-name="referrer" />
            </div>';
    }
}

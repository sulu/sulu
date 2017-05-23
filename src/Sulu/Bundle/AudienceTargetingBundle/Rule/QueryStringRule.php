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

use Sulu\Bundle\AudienceTargetingBundle\Rule\Type\KeyValue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class QueryStringRule implements RuleInterface
{
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
    private $urlHeader;

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator, $urlHeader)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->urlHeader = $urlHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate(array $options)
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($url = $request->headers->get($this->urlHeader)) {
            // the URL from the header has precedence over the real URL
            // the header is set in the target group hit request
            $request = Request::create($url);
        }

        $value = $request->get($options['parameter']);
        if (!$value) {
            return false;
        }

        return $value == $options['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans('sulu_audience_targeting.rules.query_string', [], 'backend');
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return new KeyValue(
            'parameter',
            'value',
            $this->translator->trans('sulu_audience_targeting.rules.query_string_parameter', [], 'backend'),
            $this->translator->trans('sulu_audience_targeting.rules.query_string_value', [], 'backend')
        );
    }
}

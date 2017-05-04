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

use Sulu\Bundle\AudienceTargetingBundle\Rule\Type\InternalLink;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class PageRule implements RuleInterface
{
    const PAGE = 'page';

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
    private $uuidHeader;

    /**
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param string $uuidHeader
     */
    public function __construct(RequestStack $requestStack, TranslatorInterface $translator, $uuidHeader)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->uuidHeader = $uuidHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate(array $options)
    {
        $request = $this->requestStack->getCurrentRequest();

        $uuid = $request->headers->get($this->uuidHeader);
        if (!$uuid) {
            if ($request->attributes->has('structure')) {
                $uuid = $request->attributes->get('structure')->getUuid();
            }
        }

        if (!$uuid) {
            return false;
        }

        return $options['page'] === $uuid;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans('sulu_audience_targeting.rules.page', [], 'backend');
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return new InternalLink(static::PAGE);
    }
}

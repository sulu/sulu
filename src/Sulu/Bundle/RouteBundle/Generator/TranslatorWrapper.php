<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Generator;

use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class TranslatorWrapper implements TranslatorInterface, LocaleAwareInterface
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @param mixed[] $parameters
     */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function setLocale(string $locale): void
    {
        throw new \LogicException('Not supported.');
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }
}

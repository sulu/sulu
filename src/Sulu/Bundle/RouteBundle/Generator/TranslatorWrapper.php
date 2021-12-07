<?php

namespace Sulu\Bundle\RouteBundle\Generator;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * @internal
 */
class TranslatorWrapper implements TranslatorInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }

    public function setLocale($locale)
    {
        throw new \Exception('Not supported.');
    }

    public function getLocale()
    {
        return $this->translator->getLocale();
    }
}

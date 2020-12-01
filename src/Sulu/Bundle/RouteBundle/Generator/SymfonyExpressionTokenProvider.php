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

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Enables to use the symfony expression language in route tokens.
 */
class SymfonyExpressionTokenProvider implements TokenProviderInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    public function __construct(TranslatorInterface $translator)
    {
        if (!$translator instanceof LocaleAwareInterface) {
            throw new \LogicException(\sprintf(
                'Expected "translator" in "%s" to be instance of "%s" but "%s" given.',
                __CLASS__,
                LocaleAwareInterface::class,
                \get_class($translator)
            ));
        }

        $this->translator = $translator;

        $this->expressionLanguage = new ExpressionLanguage();
        $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp('implode'));
        $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp('is_array'));
    }

    public function provide($entity, $name)
    {
        $locale = $this->translator->getLocale();

        try {
            if (\method_exists($entity, 'getLocale')) {
                $this->translator->setLocale($entity->getLocale());
            }

            $result = $this->expressionLanguage->evaluate($name, ['object' => $entity, 'translator' => $this->translator]);
            $this->translator->setLocale($locale);

            return $result;
        } catch (\Exception $e) {
            throw new CannotEvaluateTokenException($name, $entity, $e);
        } finally {
            $this->translator->setLocale($locale);
        }
    }
}

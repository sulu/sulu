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

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Translation\TranslatorInterface;

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
        $this->translator = $translator;
        $this->expressionLanguage = new ExpressionLanguage();
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

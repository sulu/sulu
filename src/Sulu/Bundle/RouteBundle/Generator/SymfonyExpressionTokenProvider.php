<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * {@inheritdoc}
     */
    public function provide($entity, $name)
    {
        try {
            return $this->expressionLanguage->evaluate($name, ['object' => $entity, 'translator' => $this->translator]);
        } catch (\Exception $e) {
            throw new CannotEvaluateTokenException($name, $entity, $e);
        }
    }
}

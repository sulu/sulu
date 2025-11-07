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
        $this->translator = $translator;

        $this->expressionLanguage = new ExpressionLanguage();
        $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp('is_array'));

        $this->expressionLanguage->register('implode', function(...$args): string {
            foreach ($args as $i => $arg) {
                if ($arg instanceof \ArrayObject) {
                    $args[$i] = $arg->getArrayCopy();
                }
            }

            return \sprintf('\%s(%s)', 'implode', \implode(', ', $args));
        }, function($arguments, ...$args): string {
            foreach ($args as $i => $arg) {
                if ($arg instanceof \ArrayObject) {
                    $args[$i] = $arg->getArrayCopy();
                }
            }

            return \implode(...$args);
        });
    }

    public function provide($entity, $name/* , $options = [] */)
    {
        $options = \func_num_args() > 2 ? \func_get_arg(2) : [];
        $locale = $this->translator->getLocale();

        try {
            $entityLocale = null;
            if (\is_object($entity) && \method_exists($entity, 'getLocale')) {
                $entityLocale = $entity->getLocale();
            } elseif (isset($options['locale'])) {
                $entityLocale = $options['locale'];
            }

            if (\is_string($entityLocale) && $entityLocale) {
                $this->setLocale($entityLocale);
            }

            $result = $this->expressionLanguage->evaluate($name, [
                'object' => $entity,
                'translator' => new TranslatorWrapper($this->translator),
                'locale' => $entityLocale,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new CannotEvaluateTokenException($name, $entity, $e);
        } finally {
            $this->setLocale($locale);
        }
    }

    private function setLocale(string $locale): void
    {
        if (\method_exists($this->translator, 'setLocale')) {
            $this->translator->setLocale($locale);
        }
    }
}

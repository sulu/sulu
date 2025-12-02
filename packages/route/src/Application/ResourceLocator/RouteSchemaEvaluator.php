<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\ResourceLocator;

use Sulu\Route\Application\ResourceLocator\Exception\InvalidRouteSchemaException;
use Sulu\Route\Application\ResourceLocator\PathCleanup\PathCleanupInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal no backwards compatibility promises are given for this class it can be removed at any time
 */
final class RouteSchemaEvaluator implements RouteSchemaEvaluatorInterface
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct(
        private TranslatorInterface $translator,
        private PathCleanupInterface $pathCleanup,
    ) {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->registerFunctions();
    }

    /**
     * @throws InvalidRouteSchemaException
     */
    public function evaluate(ResourceLocatorRequest $request): string
    {
        $schema = $request->routeSchema;
        if (null === $schema) {
            throw new InvalidRouteSchemaException('Route schema cannot be null');
        }

        $context = $this->buildContext($request);

        \preg_match_all('/{(.*?)}/', $schema, $matches);

        $replacements = [];
        foreach ($matches[1] as $index => $expression) {
            $token = $matches[0][$index];
            try {
                $value = $this->expressionLanguage->evaluate($expression, $context);
            } catch (SyntaxError $e) {
                throw new InvalidRouteSchemaException(
                    \sprintf('Invalid expression "%s" in route schema: %s', $expression, $e->getMessage()),
                    $e
                );
            }
            /** @var string|int|float $value */
            $replacements[$token] = $this->pathCleanup->cleanup((string) $value, $request->locale);
        }

        $path = \strtr($schema, $replacements);

        if (!\str_starts_with($path, '/')) {
            throw new InvalidRouteSchemaException(
                'Route schema must produce a path starting with /'
            );
        }

        return $path;
    }

    private function registerFunctions(): void
    {
        $this->expressionLanguage->register(
            'implode',
            fn (string $glue, string $pieces): string => \sprintf('implode(%s, %s)', $glue, $pieces),
            function(array $arguments, string $glue, mixed $pieces): string {
                if ($pieces instanceof ArrayAccessObject || $pieces instanceof \ArrayObject) {
                    $pieces = $pieces->getArrayCopy();
                }

                if (\is_array($pieces)) {
                    return \implode($glue, $pieces);
                }

                /** @var string|int|float $pieces */
                return (string) $pieces;
            }
        );

        $this->expressionLanguage->addFunction(
            ExpressionFunction::fromPhp('is_array')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(ResourceLocatorRequest $request): array
    {
        return [
            'object' => new ArrayAccessObject($request->parts),
            'translator' => $this->translator,
            'locale' => $request->locale,
        ];
    }
}

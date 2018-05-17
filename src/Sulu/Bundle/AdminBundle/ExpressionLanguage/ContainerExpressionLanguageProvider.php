<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ExpressionLanguage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ContainerExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var Container
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return [
            new ExpressionFunction(
                'service',
                function () {
                },
                function (array $variables, $value) {
                    return $this->container->get($value);
                }
            ),

            new ExpressionFunction(
                'parameter',
                function () {
                },
                function (array $variables, $value) {
                    return $this->container->getParameter($value);
                }
            ),
        ];
    }
}

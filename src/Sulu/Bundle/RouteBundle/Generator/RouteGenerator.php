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

use Symfony\Cmf\Api\Slugifier\SlugifierInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This generator creates routes for entities and a schema.
 */
class RouteGenerator implements RouteGeneratorInterface
{
    /**
     * @var TokenProviderInterface
     */
    private $tokenProvider;

    /**
     * @var SlugifierInterface
     */
    private $slugifier;

    /**
     * RouteGenerator constructor.
     *
     * @param TokenProviderInterface $tokenProvider
     * @param SlugifierInterface $slugifier
     */
    public function __construct(TokenProviderInterface $tokenProvider, SlugifierInterface $slugifier)
    {
        $this->tokenProvider = $tokenProvider;
        $this->slugifier = $slugifier;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($entity, array $options)
    {
        $routeSchema = $options['route_schema'];

        $tokens = [];
        preg_match_all('/{(.*?)}/', $routeSchema, $matches);
        $tokenNames = $matches[1];

        foreach ($tokenNames as $index => $name) {
            $tokenName = '{' . $name . '}';
            $tokenValue = $this->tokenProvider->provide($entity, $name);

            $tokens[$tokenName] = $this->slugifier->slugify($tokenValue);
        }

        $path = strtr($routeSchema, $tokens);
        if (0 !== strpos($path, '/')) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Generated path "%s" for object "%s" has to start with a slash',
                    $path,
                    get_class($entity)
                )
            );
        }

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsResolver(array $options)
    {
        return (new OptionsResolver())->setRequired('route_schema');
    }
}

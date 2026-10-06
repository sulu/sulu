<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use Sulu\Bundle\AdminBundle\Exception\InvalidIconProviderException;
use Sulu\Bundle\AdminBundle\Icon\IconProviderInterface;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @experimental This is an experimental feature and may change in future releases.
 */
class IconController
{
    /**
     * @param array<string, string> $iconSets
     * @param iterable<IconProviderInterface> $iconProviders
     */
    public function __construct(
        private array $iconSets,
        private iterable $iconProviders,
    ) {
    }

    public function cgetAction(Request $request): Response
    {
        $iconSetName = $request->query->getString('icon_set', 'sulu');

        if (!\array_key_exists($iconSetName, $this->iconSets)) {
            throw new NotFoundHttpException(\sprintf(
                'Unkown icon set "%s". Known icon sets are: %s',
                $iconSetName,
                \implode(', ', \array_keys($this->iconSets)),
            ));
        }

        $iconSet = \explode('://', $this->iconSets[$iconSetName]);
        $provider = $iconSet[0];
        $path = $iconSet[1] ?? '';

        $iconProviders = \iterator_to_array($this->iconProviders);

        if (\array_key_exists($provider, $iconProviders)) {
            /** @var IconProviderInterface $iconProvider */
            $iconProvider = $iconProviders[$provider];
            $icons = $iconProvider->getIcons($path);
        } else {
            throw new InvalidIconProviderException($provider, \array_keys($iconProviders));
        }

        // Implement a simple search functionality.
        $search = $request->query->get('search');
        if ($search) {
            $filteredIcons = [];

            foreach ($icons as $icon) {
                if (\str_contains($icon['id'], $search)) {
                    $filteredIcons[] = $icon;
                }
            }

            $icons = $filteredIcons;
        }

        // Sort by ID.
        \usort($icons, fn ($a, $b) => $a['id'] <=> $b['id']);

        $data = new CollectionRepresentation($icons, 'icons');

        return new JsonResponse($data->toArray());
    }
}

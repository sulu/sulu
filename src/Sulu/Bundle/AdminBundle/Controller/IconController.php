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

use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\AdminBundle\Exception\InvalidIconProviderException;
use Sulu\Bundle\AdminBundle\Icon\IconProviderInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental This is an experimental feature and may change in future releases.
 */
class IconController extends AbstractRestController
{
    /**
     * @var array<string, string>
     */
    private array $iconSets;

    /**
     * @var iterable<IconProviderInterface>
     */
    private iterable $iconProviders;

    /**
     * @param array<string, string> $iconSets
     * @param iterable<IconProviderInterface> $iconProviders
     */
    public function __construct(
        ViewHandlerInterface $viewHandler,
        array $iconSets,
        iterable $iconProviders,
    ) {
        parent::__construct($viewHandler);
        $this->iconSets = $iconSets;
        $this->iconProviders = $iconProviders;
    }

    /**
     * Returns icons.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        /** @var string $iconSetName */
        $iconSetName = $request->get('icon_set');

        if (!$iconSetName) {
            throw new MissingParameterException(\get_class($this), 'icon_set');
        }

        $iconSet = \explode('://', $this->iconSets[$iconSetName]);
        $search = $request->query->get('search');
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

        return $this->handleView(
            $this->view(
                new CollectionRepresentation(
                    $icons,
                    'icons'
                )
            )
        );
    }
}

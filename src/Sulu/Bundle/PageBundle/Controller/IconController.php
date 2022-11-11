<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Controller;

use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IconController extends AbstractRestController implements ClassResourceInterface
{
    /**
     * @var mixed[]
     */
    private array $iconSets;

    /**
     * @param array<mixed> $iconSets
     */
    public function __construct(
        ViewHandlerInterface $viewHandler,
        array $iconSets,
    ) {
        parent::__construct($viewHandler);
        $this->iconSets = $iconSets;
    }

    /**
     * Returns icons.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $iconSet = $this->iconSets[$request->query->get('icon_set')];
        $search = $request->query->get('search');
        $provider = $iconSet['provider'];
        $icons = [];

        // Get icons depending on the provider type.
        switch ($provider) {
            case 'svg':
                $icons = $this->getIconsAsSVGs($iconSet);
                break;
            case 'icomoon':
                $icons = $this->getIconsAsIcomoon($iconSet);
                break;
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

    /**
     * Return a set of SVG icons from a specific directory.
     *
     * @param array<array{path: string}> $iconSet
     *
     * @return array<array{id: string, content: string}>
     */
    public function getIconsAsSVGs(array $iconSet)
    {
        $icons = [];
        $path = $iconSet['options']['path'];

        // TODO maybe do this in a compilerpass to avoid file system access in the runtime
        $finder = new Finder();
        $finder->in((string) $path);

        foreach ($finder as $file) {
            $icons[] = [
                'id' => $file->getBasename('.svg'),
                'content' => $file->getContents(),
            ];
        }

        return $icons;
    }

    /**
     * Return a set of icons from the icomoon selection.json.
     *
     * @param array<array{selection_json: string}> $iconSet
     *
     * @return array<array{id: string, content: string}>
     */
    public function getIconsAsIcomoon(array $iconSet)
    {
        $icons = [];
        $selectionJsonPath = $iconSet['options']['selection_json'];

        if (!$selectionJsonPath) {
            return $icons;
        }

        $selection = \json_decode(\file_get_contents((string) $selectionJsonPath));

        foreach ($selection->icons as $icon) {
            $paths = [];

            foreach ($icon->icon->paths as $index => $path) {
                $paths[] = "<path d=\"{$path}\" key=\"{$index}\" fill=\"#262626\"></path>";
            }

            $content = '<svg viewBox="0 0 1000 1000" width="50" height="50">' . \join('', $paths) . '</svg>';

            $icons[] = [
                'id' => $icon->properties->name,
                'content' => $content,
            ];
        }

        return $icons;
    }
}

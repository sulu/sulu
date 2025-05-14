<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Icon\providers;

use Sulu\Bundle\AdminBundle\Icon\IconProviderInterface;

/**
 * @experimental This is an experimental feature and may change in future releases.
 */
class IcomoonProvider implements IconProviderInterface
{
    /**
     * @return array<array{id: string, content: string}>
     */
    public function getIcons(string $path): array
    {
        $icons = [];
        $selectionContent = \file_get_contents($path);

        if (!$selectionContent) {
            return $icons;
        }

        $selection = (array) \json_decode($selectionContent);
        $iconsArray = (array) $selection['icons'];

        /**
         * @var \stdClass $icon
         */
        foreach ($iconsArray as $icon) {
            $paths = [];

            \assert(isset($icon->icon) && \is_object($icon->icon));
            \assert(isset($icon->icon->paths) && \is_array($icon->icon->paths));
            \assert(isset($icon->icon->attrs) && \is_array($icon->icon->attrs));
            \assert(isset($icon->properties) && \is_object($icon->properties));
            \assert(isset($icon->properties->name) && \is_string($icon->properties->name));

            /**
             * @var string $path
             */
            foreach ($icon->icon->paths as $index => $path) {
                /** @var \stdClass $attrs */
                $attrs = $icon->icon->attrs[$index];
                /** @var string $fill */
                $fill = $attrs->fill ?? '#262626';
                $paths[] = "<path d=\"{$path}\" key=\"{$index}\" fill=\"{$fill}\"></path>";
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

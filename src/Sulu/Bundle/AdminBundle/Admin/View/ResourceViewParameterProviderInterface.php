<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\View;

/**
 * Provides the view parameters of a resource which the caller of the ResourceViewUrlGeneratorInterface cannot know,
 * e.g. the template group in a view name like `sulu_snippet.snippet.edit_tabs_{group}` or an additional path
 * parameter of a custom view. Parameters passed by the caller take precedence over the provided ones. Several
 * providers can serve the same resource, on a conflicting parameter the one with the higher tag priority wins.
 */
interface ResourceViewParameterProviderInterface
{
    public static function getResourceKey(): string;

    /**
     * @param array<string, int|string> $viewParameters
     *
     * @return array<string, int|string>
     */
    public function getViewParameters(string $resourceView, array $viewParameters): array;
}

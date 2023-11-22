<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\DependencyInjection\Compiler;

use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class WebspacesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasExtension('sulu_search')) {
            return;
        }

        $indexes = $container->getParameter('sulu_search.indexes');
        $webspaceConfig = $container->getParameter('sulu_website.webspace.configuration');

        foreach ($webspaceConfig as $key => $value) {
            $webspaceKey = $value['key'];
            $webspaceName = $value['name'];
            $indexes['page_' . $webspaceKey] = [
                'name' => $webspaceName,
                'icon' => 'su-document',
                'view' => [
                    'name' => PageAdmin::EDIT_FORM_VIEW,
                    'result_to_view' => ['id' => 'id', 'locale' => 'locale', 'properties/webspace_key' => 'webspace'],
                ],
                'security_context' => 'sulu.webspaces.' . $webspaceKey,
            ];
            $indexes['page_' . $webspaceKey . '_published'] = [
                'name' => $webspaceName,
                'icon' => 'su-document',
                'view' => [
                    'name' => PageAdmin::EDIT_FORM_VIEW,
                    'result_to_view' => ['id' => 'id', 'locale' => 'locale', 'properties/webspace_key' => 'webspace'],
                ],
                'security_context' => 'sulu.webspaces.' . $webspaceKey,
                'contexts' => ['website'],
            ];
        }

        $container->setParameter('sulu_search.indexes', $indexes);
    }
}

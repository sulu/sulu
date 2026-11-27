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

namespace Sulu\Bundle\AdminBundle\Controller;

use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewRedirectController
{
    public function __construct(
        private ResourceViewUrlGeneratorInterface $resourceViewUrlGenerator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $resourceKey = $request->query->getString('resourceKey');
        $view = $request->query->getString('view');
        $viewParams = [
            'id' => $request->query->getString('id'),
            'locale' => $request->query->getString('locale'),
            'webspace' => $request->query->getString('webspace'),
        ];

        $url = $this->resourceViewUrlGenerator->generate(
            $resourceKey,
            $view,
            $viewParams,
        );

        return new RedirectResponse($url);
    }
}

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
use Sulu\Bundle\AdminBundle\Teaser\TeaserManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TeaserController extends AbstractRestController
{
    public function __construct(
        ViewHandlerInterface $viewHandler,
        private TeaserManagerInterface $teaserManager
    ) {
        parent::__construct($viewHandler);
    }

    /**
     * Returns teaser by ids (get-parameter).
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $ids = \array_map(
            function($item) {
                $parts = \explode(';', $item);

                return ['type' => $parts[0], 'id' => $parts[1]];
            },
            \array_filter(\explode(',', $request->get('ids', '')))
        );

        return $this->handleView(
            $this->view(
                new CollectionRepresentation(
                    $this->teaserManager->find(
                        $ids,
                        $this->getLocale($request)
                    ),
                    'teasers'
                )
            )
        );
    }
}

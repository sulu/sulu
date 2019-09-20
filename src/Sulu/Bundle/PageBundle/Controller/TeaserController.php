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

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\PageBundle\Teaser\TeaserManagerInterface;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TeaserController extends RestController implements ClassResourceInterface
{
    /**
     * @var TeaserManagerInterface
     */
    private $teaserManager;

    public function __construct(TeaserManagerInterface $teaserManager)
    {
        $this->teaserManager = $teaserManager;
    }

    /**
     * Returns teaser by ids (get-parameter).
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $ids = array_map(
            function($item) {
                $parts = explode(';', $item);

                return ['type' => $parts[0], 'id' => $parts[1]];
            },
            array_filter(explode(',', $request->get('ids', '')))
        );

        return $this->handleView(
            $this->view(
                new CollectionRepresentation(
                    $this->teaserManager->find($ids, $this->getLocale($request)),
                    'teasers'
                )
            )
        );
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides api for requesting teasers.
 */
class TeaserController extends RestController implements ClassResourceInterface
{
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
            function ($item) {
                $parts = explode(';', $item);

                return ['type' => $parts[0], 'id' => $parts[1]];
            },
            array_filter(explode(',', $request->get('ids', '')))
        );

        return $this->handleView(
            $this->view(
                new CollectionRepresentation(
                    $this->get('sulu_content.teaser.manager')->find($ids, $this->getLocale($request)),
                    'teasers'
                )
            )
        );
    }
}

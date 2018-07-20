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

use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controller handles as an compatibility layer between the NodeController and the PageController.
 * This is necessary because the NodeController behaves quite different in a few situations from other controllers, and
 * we don't want to implement two different behaviours in the frontend.
 */
class PageController extends NodeController
{
    protected static $relationName = 'pages';

    public function cgetAction(Request $request)
    {
        if ('true' === $request->query->get('flat')
            && !$request->query->has('fields')
            && !$request->query->has('ids')
        ) {
            // For some reason the NodeController checks if some fields are set instead of the flat flag.
            // Therefore we set the fields to a default value if flat is passed as true, because the other APIs are also
            // checking the flat flag, and the behavior is consistent this way.
            // This should not happen if ids are passed, because the NodeController behaves different then as well
            $request->query->set('fields', 'title,published');
        }

        return $this->transformResponse(parent::cgetAction($request));
    }

    protected function cgetContent(Request $request)
    {
        if (!$request->query->has('parent')) {
            // If the content is loaded using the content mapper for a flat list (indicated by the flat flag in the
            // response) and there is no parent given, then webspaces have to be returned.
            if ($request->query->has('webspace')) {
                // If a webspace is given, then only this single webspace should be returned.
                // This behavior can be easily explained if the webspace parameter is seen as a filter.
                $request->query->set('webspace-nodes', 'single');
            } else {
                // If no webspace is given, then all webspaces should be returned.
                $request->query->set('webspace-nodes', 'all');
            }
        }

        return parent::cgetContent($request);
    }

    private function transformResponse(Response $response)
    {
        $responseContent = json_decode($response->getContent(), true);

        if (array_key_exists('nodes', $responseContent['_embedded'])) {
            // sometime the NodeController does not listen the relation name set in this controller,
            // so we replace it on our own.
            $responseContent['_embedded']['pages'] = $responseContent['_embedded']['nodes'];
            unset($responseContent['_embedded']['nodes']);
        }

        // sometimes the NodeController has an uuid field instead of id, so we replace it
        array_walk($responseContent['_embedded']['pages'], function(&$node) {
            if (array_key_exists('uuid', $node)) {
                $node['id'] = $node['uuid'];
                unset($node['uuid']);
            }
        });

        $response->setContent(json_encode($responseContent));

        return $response;
    }
}

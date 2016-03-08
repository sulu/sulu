<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rest-API-Endpoint to get countries.
 */
class CountryController extends RestController implements ClassResourceInterface
{
    /**
     * Returns country identified by code.
     *
     * @param int $id
     *
     * @return Response
     */
    public function getAction($id)
    {
        return $this->handleView(
            $this->view(
                $this->get('sulu_contact.country_repository')->find($id)
            )
        );
    }

    /**
     * Returns list of all available countries in the system.
     *
     * @return Response
     */
    public function cgetAction()
    {
        return $this->handleView(
            $this->view(
                new CollectionRepresentation($this->get('sulu_contact.country_repository')->findAll(), 'countries')
            )
        );
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ContactMediaController.
 *
 * @RouteResource("Medias")
 */
class ContactMediaController extends AbstractMediaController implements ClassResourceInterface
{
    protected static $mediaEntityKey = 'contact_media';

    public function deleteAction(int $contactId, int $id)
    {
        return $this->removeMediaFromEntity($this->getContactEntityName(), $contactId, $id);
    }

    public function postAction(int $contactId, Request $request)
    {
        return $this->addMediaToEntity($this->getContactEntityName(), $contactId, $request->get('mediaId', ''));
    }

    public function cgetAction(int $contactId, Request $request)
    {
        return $this->getMultipleView(
            $this->getContactEntityName(),
            'get_contact_medias',
            $this->get('sulu_contact.contact_manager'),
            $contactId,
            $request
        );
    }

    public function fieldsAction()
    {
        return $this->getFieldsView($this->getContactEntityName());
    }

    private function getContactEntityName()
    {
        return $this->container->getParameter('sulu.model.contact.class');
    }
}

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
 * Class AccountMediaController.
 *
 * @RouteResource("Medias")
 */
class AccountMediaController extends AbstractMediaController implements ClassResourceInterface
{
    protected static $mediaEntityKey = 'account_media';

    public function deleteAction(int $contactId, int $id)
    {
        return $this->removeMediaFromEntity($this->getAccountEntityName(), $contactId, $id);
    }

    public function postAction(int $contactId, Request $request)
    {
        return $this->addMediaToEntity($this->getAccountEntityName(), $contactId, $request->get('mediaId', ''));
    }

    public function cgetAction(int $contactId, Request $request)
    {
        return $this->getMultipleView(
            $this->getAccountEntityName(),
            'get_account_medias',
            $this->get('sulu_contact.account_manager'),
            $contactId,
            $request
        );
    }

    private function getAccountEntityName()
    {
        return $this->container->getParameter('sulu.model.account.class');
    }
}

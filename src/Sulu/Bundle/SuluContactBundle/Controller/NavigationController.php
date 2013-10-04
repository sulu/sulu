<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use Sulu\Bundle\ContactBundle\Admin\ContentPool;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class NavigationController extends Controller
{

    const SERVICE_NAME = 'sulu_contact.content_pool';

    /**
     * Lists all the contacts or filters the contacts by parameters
     * Special function for lists
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contentAction()
    {

        // TODO: get type of content (contact / account ...)

        /** @var ContentPool $pool*/
        if ($this->has(self::SERVICE_NAME)) {
            $pool = $this->get(self::SERVICE_NAME);
        }

        return new Response(json_encode($pool->toArray()));
    }


}

<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * handles snippets
 */
class SnippetController extends RestController implements ClassResourceInterface
{
    /**
     * extract parameter methods
     */
    use RequestParametersTrait;

    public function cgetAction(Request $request)
    {
        return new JsonResponse(array(
            "_embedded" => array(
                "snippets" => array(
                    array(
                        "id" => "123-123-123",
                        "title" => "asdfasdf",
                        "changed" => "2014-01-01 01:01",
                        "created" => "2014-01-01 01:01"
                    )
                )
            ),

            "_links" => array(

            )
        ));
    }

    /**
     * TODO refactor
     * @return JsonResponse
     */
    public function getFieldsAction()
    {
        return new JsonResponse(
            array(
                array(
                    "name" => "title",
                    "translation" => "public.title",
                    "disabled" => false,
                    "default" => true,
                    "sortable" => true,
                    "type" => "",
                    "width" => "",
                    "minWidth" => "100px",
                    "editable" => false
                ),
                array(
                    "name" => "id",
                    "translation" => "public.id",
                    "disabled" => true,
                    "default" => false,
                    "sortable" => true,
                    "type" => "",
                    "width" => "50px",
                    "minWidth" => "",
                    "editable" => false
                ),
                array(
                    "name" => "created",
                    "translation" => "public.created",
                    "disabled" => true,
                    "default" => false,
                    "sortable" => true,
                    "type" => "date",
                    "width" => "",
                    "minWidth" => "",
                    "editable" => false
                ),
                array(
                    "name" => "changed",
                    "translation" => "public.changed",
                    "disabled" => true,
                    "default" => false,
                    "sortable" => true,
                    "type" => "date",
                    "width" => "",
                    "minWidth" => "",
                    "editable" => false
                ),
            )
        );

    }
}

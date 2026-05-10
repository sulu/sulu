<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait ControllerTrait
{
    /**
     * The type of the entity, which is handled by the concrete controller.
     *
     * @var string
     */
    protected static $entityName;

    /**
     * The key of the entity which will be used in the embedded part of the REST Response.
     *
     * @var string
     */
    protected static $entityKey;

    /**
     * contains all attributes that are not sortable.
     *
     * @var array
     */
    protected $unsortable = [];

    /**
     * contains all attributes that are sortable
     * if defined unsortable gets ignored.
     *
     * @var array
     */
    protected $sortable = [];

    /**
     * standard bundle prefix.
     *
     * @var string
     */
    protected $bundlePrefix = '';

    /**
     * Returns the language.
     *
     * @return string|null
     */
    public function getLocale(Request $request)
    {
        return $request->query->get('locale', null);
    }

    protected function responseGetById(int $id, callable $callable): mixed
    {
        $entity = $callable($id);

        if (!$entity) {
            $exception = new EntityNotFoundException(self::$entityName, $id);
            return new JsonResponse($exception->toArray(), status: Response::HTTP_NOT_FOUND);
        }

        return $entity;
    }
}

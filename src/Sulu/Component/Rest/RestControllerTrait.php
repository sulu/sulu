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

use FOS\RestBundle\View\View;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Symfony\Component\HttpFoundation\Request;

trait RestControllerTrait
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
     */
    public function getLocale(Request $request)
    {
        return $request->query->get('locale', null);
    }

    /**
     * function replaces a url parameter.
     *
     * @param string $url String the complete url
     * @param string $key String parameter name (e.g. page=)
     * @param string $value replace value
     * @param bool $add defines if value should be added
     *
     * @return mixed|string
     *
     * @deprecated
     */
    public function replaceOrAddUrlString($url, $key, $value, $add = true)
    {
        if ($value) {
            if ($pos = \strpos($url, $key)) {
                return \preg_replace('/(.*' . $key . ')([\,|\w]*)(\&*.*)/', '${1}' . $value . '${3}', $url);
            } else {
                if ($add) {
                    $and = (false === \strpos($url, '?')) ? '?' : '&';

                    return $url . $and . $key . $value;
                }
            }
        } else {
            // remove if key exists
            if ($pos = \strpos($url, $key)) {
                $result = \preg_replace('/(.*)([\\?|\&]{1}' . $key . ')([\,|\w]*)(\&*.*)/', '${1}${4}', $url);

                // if was first variable, redo questionmark
                if (\strpos($url, '?' . $key)) {
                    $result = \preg_replace('/&/', '?', $result, 1);
                }

                return $result;
            }
        }

        return $url;
    }

    /**
     * Returns the response with the entity with the given id, or a response with a status of 404, in case the entity
     * is not found. The find method is injected by a callback.
     *
     * @param string|int $id
     * @param callable $findCallback
     *
     * @return View
     */
    protected function responseGetById($id, $findCallback)
    {
        $entity = $findCallback($id);

        if (!$entity) {
            $exception = new EntityNotFoundException(self::$entityName, $id);
            // Return a 404 together with an error message, given by the exception, if the entity is not found
            $view = $this->view(
                $exception->toArray(),
                404
            );
        } else {
            $view = $this->view($entity, 200);
        }

        return $view;
    }

    /**
     * Deletes the entity with the given id using the deleteCallback and return a successful response, or an error
     * message with a 4xx status code.
     *
     * @param string|int $id
     * @param callable $deleteCallback
     *
     * @return View
     */
    public function responseDelete($id, $deleteCallback)
    {
        try {
            $deleteCallback($id);
            $view = $this->view(null, 204);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $view;
    }

    /**
     * This method processes a put request (delete non-existing entities, update existing entities, add new
     * entries), and let the single actions be modified by callbacks.
     *
     * @param ApiEntity[] $entities
     * @param mixed[] $requestEntities
     * @param callable $deleteCallback
     * @param callable $updateCallback
     * @param callable $addCallback
     * @param callable $entityIdCallback defines how to get the entity's id which will be compared with requestEntities' id
     *
     * @return bool
     *
     * @deprecated
     */
    protected function processPut($entities, $requestEntities, $deleteCallback, $updateCallback, $addCallback, $entityIdCallback = null)
    {
        $success = true;
        // default for entityIdCallback
        if (null === $entityIdCallback) {
            $entityIdCallback = function($entity) {
                return $entity->getId();
            };
        }

        if (!empty($entities)) {
            foreach ($entities as $entity) {
                $this->findMatch($requestEntities, $entityIdCallback($entity), $matchedEntry, $matchedKey);

                if (null == $matchedEntry) {
                    // delete entity if it is not listed anymore
                    $deleteCallback($entity);
                } else {
                    // update entity if it is matched
                    $success = $updateCallback($entity, $matchedEntry);
                    if (!$success) {
                        break;
                    }
                }

                // Remove done element from array
                if (null !== $matchedKey) {
                    unset($requestEntities[$matchedKey]);
                }
            }
        }

        // The entity which have not been delete or updated have to be added
        if (!empty($requestEntities)) {
            foreach ($requestEntities as $entity) {
                if (!$success) {
                    break;
                }
                $success = $addCallback($entity);
            }
        }

        // FIXME: this is just a hack to avoid relations that start with index != 0
        // FIXME: otherwise deserialization process will parse relations as object instead of an array
        // reindex entities
        if (\count($entities) > 0 && \is_object($entities) && \method_exists($entities, 'getValues')) {
            $newEntities = $entities->getValues();
            $entities->clear();
            foreach ($newEntities as $value) {
                $entities->add($value);
            }
        }

        return $success;
    }

    /**
     * Tries to find a given id in a given set of entities. Returns the entity itself and its key with the
     * $matchedEntry and $matchKey parameters.
     *
     * @param array $requestEntities The set of entities to search in
     * @param int $id The id to search
     * @param array $matchedEntry
     * @param string $matchedKey
     */
    protected function findMatch($requestEntities, $id, &$matchedEntry, &$matchedKey)
    {
        $matchedEntry = null;
        $matchedKey = null;
        if (!empty($requestEntities)) {
            foreach ($requestEntities as $key => $entity) {
                if (isset($entity['id']) && $entity['id'] == $id) {
                    $matchedEntry = $entity;
                    $matchedKey = $key;
                    break;
                }
            }
        }
    }
}

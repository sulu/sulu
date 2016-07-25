<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\Listing\ListRestHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Abstract Controller for extracting some required rest functionality.
 */
abstract class RestController extends FOSRestController
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
     * contains all fields that should be excluded from api.
     *
     * @var array
     *
     * @deprecated
     */
    protected $fieldsExcluded = [];

    /**
     * contains all fields that should be hidden by default from api.
     *
     * @var array
     */
    protected $fieldsHidden = [];

    /**
     * contains the matching between fields and their types.
     *
     * @var array
     */
    protected $fieldTypes = [
        'created' => 'date',
        'changed' => 'date',
        'birthday' => 'date',
        'title' => 'title',
        'size' => 'bytes',
        'thumbnails' => 'thumbnails',
    ];

    /**
     * contains all field relations.
     *
     * @var array
     *
     * @deprecated
     */
    protected $fieldsRelations = [];

    /**
     * contains sort order of elements: array(order => fieldName).
     *
     * @var array
     */
    protected $fieldsSortOrder = [];

    /**
     * contains custom translation keys like array(fieldName => translationKey).
     *
     * @var array
     *
     * @deprecated
     */
    protected $fieldsTranslationKeys = [];

    /**
     * contains fields that cannot be hidden and are visible by default.
     *
     * @var array
     */
    protected $fieldsDefault = [];

    /**
     * contains fields that are editable.
     *
     * @var array
     */
    protected $fieldsEditable = [];

    /**
     * contains arrays of validation key-value data.
     *
     * @var array
     */
    protected $fieldsValidation = [];

    /**
     * @var array contains the widths of the fields
     *
     * @deprecated
     */
    protected $fieldsWidth = [];

    /**
     * @var array contains the widths of the fields
     */
    protected $fieldsMinWidth = [];

    /**
     * @var array contains the default widths of some common fields
     */
    private $fieldsDefaultWidth = [];

    /**
     * contains default translations for some fields.
     *
     * @var array
     */
    private $fieldsDefaultTranslationKeys = [];

    /**
     * standard bundle prefix.
     *
     * @var string
     */
    protected $bundlePrefix = '';

    /**
     * Returns the language.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function getLocale(Request $request)
    {
        return $request->get('locale', null);
    }

    /**
     * Creates a response which contains all fields of the current entity.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @deprecated
     */
    public function responseFields()
    {
        try {
            $this->fieldsDefaultTranslationKeys = $this->container->getParameter('sulu.fields_defaults.translations');
            $this->fieldsDefaultWidth = $this->container->getParameter('sulu.fields_defaults.widths');

            /** @var ListRestHelper $listHelper */
            $listHelper = $this->get('sulu_core.list_rest_helper');

            $fields = $listHelper->getAllFields(self::$entityName);

            // excluded fields
            $fields = array_diff($fields, $this->fieldsExcluded);
            // relations
            $fields = array_merge($fields, $this->fieldsRelations);
            // hide
            $fields = array_diff($fields, $this->fieldsHidden);
            // put at last position
            $fields = array_merge($fields, $this->fieldsHidden);
            // apply sort order
            $fields = array_diff($fields, $this->fieldsSortOrder);

            foreach ($this->fieldsSortOrder as $key => $value) {
                array_splice($fields, $key, 0, $value);
            }

            // parsing final array and sets to Default
            $fieldsArray = $this->addFieldAttributes($fields, $this->fieldsHidden);

            $view = $this->view($fieldsArray, 200);
        } catch (\Exception $e) {
            $view = $this->view($e->getMessage(), 400);
        }

        return $this->handleview($view);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function responsePersistSettings()
    {
        try {
            $key = $this->getRequest()->get('key');
            $data = $this->getRequest()->get('data');

            $userDataServiceId = $this->container->getParameter('sulu_admin.user_data_service');
            if ($this->has($userDataServiceId)) {
                /** @var UserManagerInterface $userManager */
                $userManager = $this->get($userDataServiceId);
                /** @var CurrentUserDataInterface $userData */
                $userData = $userManager->getCurrentUserData();

                $userData->setUserSetting($key, $data);
            }
            $view = $this->view('', 200);
        } catch (\Exception $e) {
            $view = $this->view($e->getMessage(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * creates the translation keys array and sets the default attribute, if set
     * also adds the type property for.
     *
     * @param $fields
     * @param $fieldsHidden
     *
     * @return array
     */
    protected function addFieldAttributes($fields, $fieldsHidden = [])
    {
        // add translations
        $fieldsArray = [];

        foreach ($fields as $field) {
            if (isset($this->fieldsTranslationKeys[$field])) {
                $translationKey = $this->fieldsTranslationKeys[$field];
            } else {
                if (isset($this->fieldsDefaultTranslationKeys[$field])) {
                    $translationKey = $this->fieldsDefaultTranslationKeys[$field];
                } else {
                    // check translations
                    $translationKey = $this->bundlePrefix . $field;
                }
            }
            $fieldWidth = null;
            if (isset($this->fieldsWidth[$field])) {
                $fieldWidth = $this->fieldsWidth[$field];
            } else {
                if (isset($this->fieldsDefaultWidth[$field])) {
                    $fieldWidth = $this->fieldsDefaultWidth[$field];
                }
            }

            $type = '';
            if (isset($this->fieldTypes[$field])) {
                $type = $this->fieldTypes[$field];
            }

            $fieldsArray[] = [
                'id' => $field,
                'translation' => $translationKey,
                'disabled' => in_array($field, $fieldsHidden) ? true : false,
                'default' => in_array($field, $this->fieldsDefault) ? true : null,
                'editable' => in_array($field, $this->fieldsEditable) ? true : null,
                'width' => ($fieldWidth != null) ? $fieldWidth : null,
                'minWidth' => array_key_exists($field, $this->fieldsMinWidth) ? $this->fieldsMinWidth[$field] : null,
                'type' => $type,
                'validation' => array_key_exists($field, $this->fieldsValidation) ?
                        $this->fieldsValidation[$field] : null,
            ];
        }

        return $fieldsArray;
    }

    /**
     * Lists all the entities or filters the entities by parameters
     * Special function for lists
     * route /contacts/list.
     *
     * @param array    $where
     * @param string   $entityName
     * @param Function $entityFilter   function for filtering entities
     * @param array    $joinConditions to specify join conditions
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @deprecated
     */
    protected function responseList($where = [], $entityName = null, $entityFilter = null, $joinConditions = [])
    {
        /** @var ListRestHelper $listHelper */
        $listHelper = $this->get('sulu_core.list_rest_helper');

        if (is_null($entityName)) {
            $entityName = self::$entityName;
        }

        $entities = $listHelper->find($entityName, $where, $joinConditions);
        $numberOfAll = $listHelper->getTotalNumberOfElements($entityName, $where, $joinConditions);
        $pages = $listHelper->getTotalPages($numberOfAll);

        // filter entities
        if (!is_null($entityFilter)) {
            $entities = array_map($entityFilter, $entities);
        }

        $response = [
            '_links' => $this->getHalLinks($entities, $pages, true),
            '_embedded' => $entities,
            'total' => count($entities),
            'page' => $listHelper->getPage(),
            'pages' => $pages,
            'limit' => $listHelper->getLimit(),
            'numberOfAll' => $numberOfAll,
        ];

        return $this->view($response, 200);
    }

    /**
     * creates HAL conform response-array out of an entity collection.
     *
     * @param array $entities
     * @param bool  $returnListLinks
     *
     * @return array
     *
     * @deprecated
     */
    protected function createHalResponse(array $entities, $returnListLinks = false)
    {
        return [
            '_links' => $this->getHalLinks($entities, 1, $returnListLinks),
            '_embedded' => $entities,
            'total' => count($entities),
        ];
    }

    /**
     * returns HAL-conform _links array.
     *
     * @param array $entities
     * @param int   $pages
     * @param bool  $returnListLinks
     *
     * @return array
     *
     * @deprecated
     */
    protected function getHalLinks(array $entities, $pages = 1, $returnListLinks = false)
    {
        /** @var ListRestHelper $listHelper */
        $listHelper = $this->get('sulu_core.list_rest_helper');

        $path = $this->getRequest()->getRequestUri();
        $path = $this->replaceOrAddUrlString(
            $path,
            'limit=',
            $listHelper->getLimit(),
            false
        );

        // remove parameters without value
        foreach ($this->getRequest()->query->all() as $key => $value) {
            if ($value == '') {
                // remove from path
                $path = $this->replaceOrAddUrlString(
                    $path,
                    $key . '=',
                    null,
                    false
                );
            }
        }

        $page = $listHelper->getPage();

        // create sort links
        $sortable = [];
        if ($returnListLinks && count($entities) > 0) {
            $keys = [];
            if (count($this->sortable) > 0) {
                $keys = $this->sortable;
            } elseif (is_array($entities[0])) {
                $keys = array_keys($entities[0]);
            }
            // remove page
            $sortUrl = $this->replaceOrAddUrlString(
                $path,
                'page=',
                null
            );
            foreach ($keys as $key) {
                if (!in_array($key, $this->unsortable)) {
                    $sortPath = $this->replaceOrAddUrlString(
                        $sortUrl,
                        'sortBy=',
                        $key
                    );
                    $sortable[$key] = $this->replaceOrAddUrlString(
                        $sortPath,
                        'sortOrder=',
                        '{sortOrder}'
                    );
                }
            }
        }

        // create search link
        $searchLink = $this->replaceOrAddUrlString(
            $path,
            'search=',
            '{searchString}'
        );

        $searchLink = $this->replaceOrAddUrlString(
            $searchLink,
            'searchFields=',
            '{searchFields}'
        );

        $searchLink = $this->replaceOrAddUrlString(
            $searchLink,
            'page=',
            '1'
        );

        // create all link
        $allLink = $this->replaceOrAddUrlString(
            $path,
            'limit=',
            null
        );

        $allLink = $this->replaceOrAddUrlString(
            $allLink,
            'page=',
            null
        );

        // create filter link
        $filterLink = $this->replaceOrAddUrlString(
            $path,
            'fields=',
            '{fieldsList}'
        );

        // create pagination link
        $paginationLink = $this->replaceOrAddUrlString($path, 'limit=', '{limit}');

        return [
            'self' => $path,
            'first' => ($pages > 1) ?
                    $this->replaceOrAddUrlString($path, 'page=', 1) : null,
            'last' => ($pages > 1) ?
                    $this->replaceOrAddUrlString($path, 'page=', $pages) : null,
            'next' => ($page < $pages) ?
                    $this->replaceOrAddUrlString($path, 'page=', $page + 1) : null,
            'prev' => ($page > 1 && $pages > 1) ?
                    $this->replaceOrAddUrlString($path, 'page=', $page - 1) : null,
            'pagination' => $this->replaceOrAddUrlString($paginationLink, 'page=', '{page}'),
            'find' => $returnListLinks ? $searchLink : null,
            'filter' => $returnListLinks ? $filterLink : null,
            'sortable' => $returnListLinks ? $sortable : null,
            'all' => $returnListLinks ? $allLink : null,
        ];
    }

    /**
     * function replaces a url parameter.
     *
     * @param string $url   String the complete url
     * @param string $key   String parameter name (e.g. page=)
     * @param string $value replace value
     * @param bool   $add   defines if value should be added
     *
     * @return mixed|string
     *
     * @deprecated
     */
    public function replaceOrAddUrlString($url, $key, $value, $add = true)
    {
        if ($value) {
            if ($pos = strpos($url, $key)) {
                return preg_replace('/(.*' . $key . ')([\,|\w]*)(\&*.*)/', '${1}' . $value . '${3}', $url);
            } else {
                if ($add) {
                    $and = (strpos($url, '?') === false) ? '?' : '&';

                    return $url . $and . $key . $value;
                }
            }
        } else {
            // remove if key exists
            if ($pos = strpos($url, $key)) {
                $result = preg_replace('/(.*)([\\?|\&]{1}' . $key . ')([\,|\w]*)(\&*.*)/', '${1}${4}', $url);

                // if was first variable, redo questionmark
                if (strpos($url, '?' . $key)) {
                    $result = preg_replace('/&/', '?', $result, 1);
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
     * @param $id
     * @param callback $findCallback
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
     * @param $id
     * @param $deleteCallback
     *
     * @return \FOS\RestBundle\View\View
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
     * @param $requestEntities
     * @param callback $deleteCallback
     * @param callback $updateCallback
     * @param callback $addCallback
     * @param callback $entityIdCallback defines how to get the entity's id which will be compared with requestEntities' id
     *
     * @return bool
     *
     * @deprecated
     */
    protected function processPut($entities, $requestEntities, $deleteCallback, $updateCallback, $addCallback, $entityIdCallback = null)
    {
        $success = true;
        // default for entityIdCallback
        if ($entityIdCallback === null) {
            $entityIdCallback = function ($entity) {
                return $entity->getId();
            };
        }

        if (!empty($entities)) {
            foreach ($entities as $entity) {
                $this->findMatch($requestEntities, $entityIdCallback($entity), $matchedEntry, $matchedKey);

                if ($matchedEntry == null) {
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
                if ($matchedKey !== null) {
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
        if (count($entities) > 0 && method_exists($entities, 'getValues')) {
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
     * @param array  $requestEntities The set of entities to search in
     * @param int    $id              The id to search
     * @param array  $matchedEntry
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

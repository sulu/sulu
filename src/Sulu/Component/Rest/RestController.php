<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use FOS\RestBundle\Controller\FOSRestController;
use Sulu\Bundle\AdminBundle\UserManager\CurrentUserDataInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\Listing\ListRestHelper;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Abstract Controller for extracting some required rest functionality
 * @package Sulu\Bundle\CoreBundle\Controller
 */
abstract class RestController extends FOSRestController
{
    /**
     * The type of the entity, which is handled by the concrete controller
     * @var string
     */
    protected $entityName;

    /**
     * contains all attributes that are not sortable
     * @var array
     */
    protected $unsortable = array();


    /**
     * contains all fields that should be excluded from api
     * @var array
     */
    protected $fieldsExcluded = array();

    /**
     * contains all fields that should be hidden by default from api
     * @var array
     */
    protected $fieldsHidden = array();

    /**
     * contains all field relations
     * @var array
     */
    protected $fieldsRelations = array();

    /**
     * contains sort order of elements: array(order => fieldName)
     * @var array
     */
    protected $fieldsSortOrder = array();

    /**
     * contains custom translation keys like array(fieldName => translationKey)
     * @var array
     */
    protected $fieldsTranslationKeys = array();

    /**
     * contains default translations for some keys
     * @var array
     */
    protected $fieldsDefaultTranslationKeys = array('id'=>'public.id', 'name'=>'public.name');

    /**
     * standard bundle prefix
     * @var string
     */
    protected $bundlePrefix = '';


    /**
     * Creates a response which contains all fields of the current entity
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function responseFields() {
        try {
            /** @var ListRestHelper $listHelper */
            $listHelper = $this->get('sulu_core.list_rest_helper');

            $fields = $listHelper->getAllFields($this->entityName);

            // excluded fields
            $fields = array_diff($fields, $this->fieldsExcluded);
            // relations
            $fields = array_merge($fields, $this->fieldsRelations);
            // hide
            $fields = array_diff($fields, $this->fieldsHidden);
            // apply sort order
            $fields = array_diff($fields, $this->fieldsSortOrder);
            foreach ($this->fieldsSortOrder as $key => $value) {
                 array_splice($fields, $key, 0, $value);
            }

            // parsing final array
            $fieldsArray = $this->addTranslationKeys($fields);
            $fieldsHiddenArray = $this->addTranslationKeys($this->fieldsHidden, true);
            $fieldsArray = array_merge($fieldsArray, $fieldsHiddenArray);


            $view = $this->view($fieldsArray, 200);
        } catch (\Exception $e) {
            $view = $this->view($e->getMessage(), 400);
        }

        return $this->handleview($view);
    }

    public function responsePersistSettings() {
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
            $view = $this->view('',200);
        } catch (\Exception $e) {
            $view = $this->view($e->getMessage(),400);
        }

        return $this->handleView($view);
    }


    /**
     * creates the translation keys array
     * @param $fields
     * @param bool $hidden defines if keys are hidden
     * @return array
     */
    private function addTranslationKeys($fields, $hidden = false) {
        // add translations
        $fieldsArray = array();
        foreach($fields as $field) {
            if (isset($this->fieldsTranslationKeys[$field])) {
                $translationkey = $this->fieldsTranslationKeys[$field];
            }
            else if (isset($this->fieldsDefaultTranslationKeys[$field])) {
                $translationkey = $this->fieldsDefaultTranslationKeys[$field];
            }
            else {
                // check translations
                $translationkey = $this->bundlePrefix.$field;
            }

            $fieldsArray[] = array(
                'id' => $field,
                'translation' => $translationkey,
                'disabled' => $hidden
            );
        }
        return $fieldsArray;
    }

    /**
     * Lists all the entities or filters the entities by parameters
     * Special function for lists
     * route /contacts/list
     * @param array $where
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function responseList($where = array())
    {
        /** @var ListRestHelper $listHelper */
        $listHelper = $this->get('sulu_core.list_rest_helper');

        $entities = $listHelper->find($this->entityName, $where);
        $pages = $listHelper->getTotalPages($this->entityName, $where);

        $response = array(
            '_links' => $this->getHalLinks($entities, $pages, true),
            '_' => $this->getHalLinks($entities, $pages, true),
            '_embedded' => $entities,
            'total' => sizeof($entities),
            'page' => $listHelper->getPage(),
            'pages' => $pages,
            'pageSize' => $listHelper->getLimit(),
        );

        return $this->view($response, 200);
    }

    /**
     * creates HAL conform response-array out of an entitycollection
     * @param array $entities
     * @return array
     */
    protected function createHalResponse(array $entities)
    {
        return array(
            '_links' => $this->getHalLinks($entities),
            '_embedded' => $entities,
            'total' => count($entities),
        );
    }


    /**
     * returns HAL-conform _links array
     * @param array $entities
     * @param int $pages
     * @param bool $returnListLinks
     * @return array
     */
    private function getHalLinks(array $entities, $pages = 1, $returnListLinks = false)
    {
        /** @var ListRestHelper $listHelper */
        $listHelper = $this->get('sulu_core.list_rest_helper');

        $path = $this->getRequest()->getRequestUri();
//        $pathInfo = $this->getRequest()->getPathInfo();
        $path = $this->replaceOrAddUrlString(
            $path,
            $listHelper->getParameterName('pageSize') . '=',
            $listHelper->getLimit(),
            false
        );

        $page = $listHelper->getPage();

        // create sort links
        $sortable = array();
        if ($returnListLinks && count($entities) > 0) {
            $keys = array_keys($entities[0]);
            // remove page
            $sortUrl = $this->replaceOrAddUrlString(
                $path,
                $listHelper->getParameterName('page') . '=',
                null
            );
            foreach ($keys as $key) {
                if (!in_array($key, $this->unsortable)) {
                    $sortPath = $this->replaceOrAddUrlString(
                        $sortUrl,
                        $listHelper->getParameterName('sortBy') . '=',
                        $key
                    );
                    $sortable[$key] = $this->replaceOrAddUrlString(
                        $sortPath,
                        $listHelper->getParameterName('sortOrder') . '=',
                        '{sortOrder}'
                    );
                }
            }
        }

        // create search link
        $searchLink = $this->replaceOrAddUrlString(
            $path,
            $listHelper->getParameterName('search') . '=',
            '{searchString}'
        );
        $searchLink = $this->replaceOrAddUrlString(
            $searchLink,
            $listHelper->getParameterName('page') . '=',
            '1'
        );


        return array(
            'self' => $path,
            'first' => ($pages > 1) ? $this->replaceOrAddUrlString(
                $path,
                $listHelper->getParameterName('page') . '=',
                1
            ) : null,
            'last' => ($pages > 1) ? $this->replaceOrAddUrlString(
                $path,
                $listHelper->getParameterName('page') . '=',
                $pages
            ) : null,
            'next' => ($page < $pages) ? $this->replaceOrAddUrlString(
                $path,
                $listHelper->getParameterName('page') . '=',
                $page + 1
            ) : null,
            'prev' => ($page > 1 && $pages > 1) ? $this->replaceOrAddUrlString(
                $path,
                $listHelper->getParameterName('page') . '=',
                $page - 1
            ) : null,
            'pagination' => ($pages > 1) ? $this->replaceOrAddUrlString(
                $path,
                $listHelper->getParameterName('page') . '=',
                '{page}'
            ) : null,
            'find' => $returnListLinks ? $searchLink : null,
            'sortable' => $returnListLinks ? $sortable : null,
        );
    }

    /**
     * function replaces a url parameter
     * @param $url - the complete url
     * @param $key - parametername (e.g. page=)
     * @param $value - replace value
     * @param bool $add - defines if value should be added
     * @return mixed|string
     */
    public function replaceOrAddUrlString($url, $key, $value, $add = true)
    {
        if ($value) {
            if ($pos = strpos($url, $key)) {
                return preg_replace('/(.*' . $key . ')(\w+)(\&*.*)/', '${1}' . $value . '${3}', $url);
            } else {
                if ($add) {
                    $and = (strpos($url, '?') === false) ? '?' : '&';
                    return $url . $and . $key . $value;
                }
            }
        } else {
            // remove if key exists
            if ($pos = strpos($url, $key)) {
                $result = preg_replace('/(.*)([\\?|\&]{1}'.$key.')(\w+)(\&*.*)/', '${1}${4}', $url);

                // if was first variable, redo questionmark
                if(strpos($url, '?'.$key)) {
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
     * @param $id
     * @param callback $findCallback
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function responseGetById($id, $findCallback)
    {
        $entity = $findCallback($id);

        if (!$entity) {
            $exception = new EntityNotFoundException($this->entityName, $id);
            // Return a 404 together with an error message, given by the excpetion, if the entity is not found
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
     * message with a 4xx-statuscode.
     * @param $id
     * @param $deleteCallback
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
     * entries), and let the single actions be modified by callbacks
     * @param $entities
     * @param $requestEntities
     * @param callback $deleteCallback
     * @param callback $updateCallback
     * @param callback $addCallback
     * @return bool
     */
    protected function processPut($entities, $requestEntities, $deleteCallback, $updateCallback, $addCallback)
    {
        $success = true;

        if (!empty($entities)) {
            foreach ($entities as $entity) {
                $this->findMatch($requestEntities, $entity->getId(), $matchedEntry, $matchedKey);

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
                if (!is_null($matchedKey)) {
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

        return $success;
    }

    /**
     * Tries to find an given id in a given set of entities. Returns the entity itself and its key with the
     * $matchedEntry and $matchKey parameters.
     * @param array $requestEntities The set of entities to search in
     * @param integer $id The id to search
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
                }
            }
        }
    }
}

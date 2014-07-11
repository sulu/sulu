<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use Hateoas\Representation\CollectionRepresentation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;

use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\RestObject\Media;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes media available through a REST API
 * @package Sulu\Bundle\MediaBundle\Controller
 */
class MediaController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluMediaBundle:Media';

    /**
     * {@inheritdoc}
     */
    protected $fieldsHidden = array('id', 'created', 'changed');

    /**
     * {@inheritdoc}
     */
    protected $fieldsSortOrder = array(0 => 'id');

    /**
     * {@inheritdoc}
     */
    protected $fieldsTranslationKeys = array('id' => 'public.id');

    /**
     * {@inheritdoc}
     */
    protected $fieldsEditable = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsValidation = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsWidth = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsRelations = array('title', 'name', 'description', 'thumbnails', 'size'); // TODO change thumbnails to format when husky updated

    /**
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'media.media.';

    /**
     * returns all fields that can be used by list
     * @Get("media/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        $fieldDescriptors = $this->getMediaManager()->getFieldDescriptors();
        return $this->handleView($this->view($fieldDescriptors, 200));
    }

    /**
     * persists a setting
     * @Put("media/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
    }

    /**
     * Shows a single media with the given id
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        $locale = $this->getLocale($request->get('locale'));
        $mM = $this->getMediaManager();
        $view = $this->responseGetById(
            $id,
            function ($id) use ($locale, $mM) {
                /**
                 * @var MediaEntity $mediaEntity
                 */
                $mediaEntity = $mM->findById($id);
                return $mM->getApiObject($mediaEntity, $locale);
            }
        );

        return $this->handleView($view);
    }

    /**
     * lists all media
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $collection = $request->get('collection');
        $limit = $request->get('limit');
        $ids = $request->get('ids');
        if ($ids !== null) {
            $ids = explode(',', $ids);
        }

        $mM = $this->getMediaManager();
        $media = $mM->find($collection, $ids, $limit);
        $wrappers = $mM->getApiObjects($media, $this->getLocale($request->get('locale')));
        $mediaCollection = new CollectionRepresentation($wrappers, 'media');
        $view = $this->view($mediaCollection, 200);
        return $this->handleView($view);
    }

    /**
     * Creates a new media
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException
     */
    public function postAction(Request $request)
    {
        return $this->saveEntity(null, $request);
    }

    /**
     * Edits the existing media with the given id
     * @param integer $id The id of the media to update
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        return $this->saveEntity($id, $request);
    }

    /**
     * Delete a media with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $this->getMediaManager()->delete($id);
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function saveEntity($id, Request $request)
    {
        try {
            $mM = $this->getMediaManager();
            $data = $this->getData($request);
            $data['id'] = $id;
            $uploadedFile = $this->getUploadedFile($request, 'fileVersion');
            $categoryEntity = $mM->save($uploadedFile, $data, $this->getUser()->getId());
            $categoryWrapper = $mM->getApiObject($categoryEntity, $this->getLocale($request->get('locale')));

            $view = $this->view($categoryWrapper, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     * @param $name
     * @return UploadedFile
     */
    protected function getUploadedFile(Request $request, $name)
    {
        return $request->files->get($name);
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getData(Request $request)
    {
        return array(
            'id' => $request->get('id'),
            'locale' => $request->get('locale', $this->getLocale($request->get('locale'))),
            'type' => $request->get('type'),
            'collection' => $request->get('collection'),
            'versions' => $request->get('versions'),
            'version' => $request->get('version'),
            'size' => $request->get('size'),
            'contentLanguages' => $request->get('contentLanguages', array()),
            'publishLanguages' => $request->get('publishLanguages', array()),
            'tags' => $request->get('tags', array()),
            'formats' => $request->get('formats', array()),
            'url' => $request->get('url'),
            'name' => $request->get('name'),
            'title' => $request->get('title', $this->getTitleFromUpload($request, 'fileVersion')),
            'description' => $request->get('description'),
            'changer' => $request->get('changer'),
            'creator' => $request->get('creator'),
            'changed' => $request->get('changed'),
            'created' => $request->get('created'),
        );
    }

    /**
     * @param Request $request
     * @return null
     */
    protected function getTitleFromUpload($request)
    {
        $title = null;

        $uploadedFile = $this->getUploadedFile($request, 'fileVersion');

        if ($uploadedFile) {
            $title = $part = implode('.', explode('.', $uploadedFile->getClientOriginalName(), -1));
        }

        return $title;
    }

    /**
     * getMediaManager
     * @return MediaManagerInterface
     */
    protected function getMediaManager()
    {
        return $this->get('sulu_media.media_manager');
    }

    /**
     * @param $requestLocale
     * @return mixed
     */
    protected function getLocale($requestLocale)
    {
        if ($requestLocale) {
            return $requestLocale;
        }

        return $this->getUser()->getLocale();
    }
}

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

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use Sulu\Bundle\ContactBundle\Entity\AccountCategory;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use \DateTime;

/**
 * Makes account categories available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class AccountCategoryController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluContactBundle:AccountCategory';


    /**
     * lists all accounts
     * optional parameter 'flat' calls listAction
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        $contacts = $this->getDoctrine()->getRepository($this->entityName)->findAll();
        $view = $this->view($this->createHalResponse($contacts), 200);

        return $this->handleView($view);
    }

    /**
     * Creates a new account category
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        $name = $this->getRequest()->get('category');

        try {
            if ($name == null) {
                throw new RestException('There is no category-name for the account-category given');
            }

            $em = $this->getDoctrine()->getManager();
            $category = new AccountCategory();
            $category->setCategory($name);

            $em->persist($category);
            $em->flush();

            $view = $this->view($category, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Edits the existing contact with the given id
     * @param integer $id The id of the contact to update
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id)
    {

        try {
            /** @var AccountCategory $category */
            $category = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->findAccountById($id);

            if (!$category) {
                throw new EntityNotFoundException($this->entityName, $id);
            } else {

                $name = $this->getRequest()->get('category');

                if ($name == null || $name == '') {
                    throw new RestException('There is no category-name for the account-category given');
                } else {

                    $em = $this->getDoctrine()->getManager();
                    $category->setCategory($name);

                    $em->flush();
                    $view = $this->view($category, 200);
                }
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }


    /**
     * Delete a account category with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {

            /* @var AccountCategory $category */
            $category = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->find($id);

            if (!$category) {
                throw new EntityNotFoundException($this->$entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($category);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);
        return $this->handleView($view);
    }

    /**
     * Add or update a bunch of account categories
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction()
    {

        /** @var Request $request */
        $request = $this->getRequest();
        $i = 0;
        while ($item = $request->get($i)) {
            $this->addAndUpdateCategories($item);
            $i++;
        }

        $this->getDoctrine()->getManager()->flush();
        $view = $this->view(null, 204);

        return $this->handleView($view);
    }

    /**
     * Helper function for patch action
     * @param $item
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function addAndUpdateCategories($item){
        if(isset($item['id'])) {
            /* @var AccountCategory $category */
            $category = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->find($item['id']);

            if($category == null){
                throw new EntityNotFoundException($this->$entityName, $item['id']);
            } else {
                $category->setCategory($item['category']);
            }

        } else {
            $category = new AccountCategory();
            $category->setCategory($item['category']);
        }
    }

}

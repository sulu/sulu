<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Component\HttpFoundation\Request;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\SecurityBundle\Security\Exception\EmailNotUniqueException;
use Sulu\Bundle\SecurityBundle\Security\Exception\MissingPasswordException;
use Sulu\Bundle\SecurityBundle\Security\Exception\UsernameNotUniqueException;
use Sulu\Component\Rest\Exception\InvalidArgumentException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Sulu\Bundle\SecurityBundle\Entity\UserSetting;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;

/**
 * Makes the users accessible through a rest api
 * @package Sulu\Bundle\SecurityBundle\Controller
 */
class UserController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    const ENTITY_NAME_USER_SETTING = 'SuluSecurityBundle:UserSetting';

    protected static $entityName = 'SuluSecurityBundle:User';

    protected static $entityKey = 'users';

    /**
     * Contains the field descriptors used by the list response
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors;

    // TODO: move field descriptors to a manager
    public function __construct()
    {
        $this->fieldDescriptors = array();
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor('id', 'id', static::$entityName);
        $this->fieldDescriptors['username'] = new DoctrineFieldDescriptor('username', 'username', static::$entityName);
        $this->fieldDescriptors['email'] = new DoctrineFieldDescriptor('email', 'email', static::$entityName);
        $this->fieldDescriptors['password'] = new DoctrineFieldDescriptor('password', 'password', static::$entityName);
        $this->fieldDescriptors['locale'] = new DoctrineFieldDescriptor('locale', 'locale', static::$entityName);
        $this->fieldDescriptors['salt'] = new DoctrineFieldDescriptor('salt', 'salt', static::$entityName);
        $this->fieldDescriptors['apiKey'] = new DoctrineFieldDescriptor('apiKey', 'apiKey', static::$entityName);
    }

    /**
     * Returns the user with the given id
     *
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $find = function ($id) {
            return $this->getUserManager()->getUserById($id);
        };

        return $this->handleView(
            $this->responseGetById($id, $find)
        );
    }

    /**
     * Creates a new user in the system
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        try {
            $this->checkArguments($request);
            $locale = $this->getLocale($request);
            $data = $request->request->all();
            $user = $this->getUserManager()->save($data, $locale);
            $view = $this->view($user, 200);
        } catch (UsernameNotUniqueException $exc) {
            $view = $this->view($exc->toArray(), 409);
        } catch (MissingPasswordException $exc) {
            $view = $this->view($exc->toArray(), 400);
        } catch (EmailNotUniqueException $exc) {
            $view = $this->view($exc->toArray(), 409);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @Post("/users/{id}")
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postEnableUserAction($id, Request $request)
    {
        $action = $request->get('action');
        try {
            switch ($action) {
                case 'enable':
                    // call repository method
                    $user = $this->getUserManager()->enableUser($id);
                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            // prepare view
            $view = $this->view($user, 200);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Updates the given user with the given data
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $id)
    {
        try {
            $this->checkArguments($request);
            $locale = $this->getLocale($request);
            $user = $this->getUserManager()->save($request->request->all(), $locale, $id);
            $view = $this->view($user, 200);
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (UsernameNotUniqueException $exc) {
            $view = $this->view($exc->toArray(), 409);
        } catch (EmailNotUniqueException $exc) {
            $view = $this->view($exc->toArray(), 409);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Partly updates a user entity for a given id
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction(Request $request, $id)
    {
        try {
            $locale = $this->getLocale($request);
            $user = $this->getUserManager()->save($request->request->all(), $locale, $id, true);
            $view = $this->view($user, 200);
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (UsernameNotUniqueException $exc) {
            $view = $this->view($exc->toArray(), 409);
        } catch (EmailNotUniqueException $exc) {
            $view = $this->view($exc->toArray(), 409);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Takes a key, value pair and stores it as settings for the user
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param Number $id the id of the user
     * @param String $key the settings key
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putSettingsAction(Request $request, $id, $key)
    {
        $value = $request->get('value');

        try {
            if ($key === null || $value === null) {
                throw new InvalidArgumentException(static::$entityName, 'key and value');
            }

            $em = $this->getDoctrine()->getManager();
            $user = $this->getUser();

            if ($user->getId() != $id) {
                throw new InvalidArgumentException(static::$entityName, 'id');
            }

            // encode before persist
            $data = json_encode($value);

            // get setting
            /** @var UserSetting $setting */
            $setting = $this->getDoctrine()
                ->getRepository(static::ENTITY_NAME_USER_SETTING)
                ->findOneBy(array('user' => $user, 'key' => $key));

            // or create new one
            if (!$setting) {
                $setting = new UserSetting();
                $setting->setKey($key);
                $setting->setUser($user);
                $em->persist($setting);
            }
            // persist setting
            $setting->setValue($data);
            $em->flush($setting);

            //create view
            $view = $this->view($setting, 200);
        } catch (InvalidArgumentException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Returns the settings for a key for the current user
     *
     * @param Number $id The id of the user
     * @param String $key The settings key
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getSettingsAction($id, $key)
    {
        try {
            $user = $this->getUser();

            if ($user->getId() != $id) {
                throw new InvalidArgumentException(static::$entityName, 'id');
            }

            $setting = $this->getDoctrine()
                ->getRepository(static::ENTITY_NAME_USER_SETTING)
                ->findOneBy(array('user' => $user, 'key' => $key));

            $view = $this->view($setting, 200);
        } catch (InvalidArgumentException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Deletes the user with the given id
     *
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = $this->getUserManager()->delete($id);
        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Checks if all the arguments are given, and throws an exception if one is missing
     * @throws \Sulu\Component\Rest\Exception\MissingArgumentException
     */

    // TODO: Use schema validation see:
    // https://github.com/sulu-io/sulu/issues/1136
    private function checkArguments(Request $request)
    {
        if ($request->get('username') == null) {
            throw new MissingArgumentException(static::$entityName, 'username');
        }
        if ($request->get('password') === null) {
            throw new MissingArgumentException(static::$entityName, 'password');
        }
        if ($request->get('locale') == null) {
            throw new MissingArgumentException(static::$entityName, 'locale');
        }
        if ($request->get('contact') == null) {
            throw new MissingArgumentException(static::$entityName, 'contact');
        }
    }

    /**
     * Returns a user with a specific contact id or all users
     * optional parameter 'flat' calls listAction
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $view = null;
        if ($request->get('flat') == 'true') {

            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->get('sulu_core.doctrine_rest_helper');

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create(static::$entityName);

            $restHelper->initializeListBuilder($listBuilder, $this->fieldDescriptors);

            $list = new ListRepresentation(
                $listBuilder->execute(),
                static::$entityKey,
                'get_users',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $contactId = $request->get('contactId');

            if ($contactId != null) {
                $entities = array();
                $entities[] = $this->getDoctrine()->getRepository(static::$entityName)->findUserByContact($contactId);
                if (!$entities[0]) {
                    $view = $this->view(null, 204);
                }
            } else {
                $entities = $this->getUserManager()->findAll();
            }

            $list = new CollectionRepresentation($entities, static::$entityKey);
        }

        if (!$view) {
            $view = $this->view($list, 200);
        }

        return $this->handleView($view);
    }

    /**
     * {@inheritDoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.security.users';
    }

    /**
     * Returns the UserManager
     *
     * @return UserManager
     */
    private function getUserManager()
    {
        return $this->get('sulu_security.user_manager');
    }
}

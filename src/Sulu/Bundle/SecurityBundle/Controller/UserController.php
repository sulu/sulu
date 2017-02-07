<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use JMS\Serializer\SerializationContext;
use Sulu\Bundle\SecurityBundle\Security\Exception\EmailNotUniqueException;
use Sulu\Bundle\SecurityBundle\Security\Exception\MissingPasswordException;
use Sulu\Bundle\SecurityBundle\Security\Exception\UsernameNotUniqueException;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes the users accessible through a rest api.
 */
class UserController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    use RequestParametersTrait;

    protected static $entityKey = 'users';

    /**
     * Contains the field descriptors used by the list response.
     *
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors;

    // TODO: move field descriptors to a manager

    protected function getFieldDescriptors()
    {
        if (empty($this->fieldDescriptors)) {
            $this->initFieldDescriptors();
        }

        return $this->fieldDescriptors;
    }

    private function initFieldDescriptors()
    {
        $this->fieldDescriptors = [];
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            $this->container->getParameter('sulu.model.user.class')
        );
        $this->fieldDescriptors['username'] = new DoctrineFieldDescriptor(
            'username',
            'username',
            $this->container->getParameter('sulu.model.user.class')
        );
        $this->fieldDescriptors['email'] = new DoctrineFieldDescriptor(
            'email',
            'email',
            $this->container->getParameter('sulu.model.user.class')
        );
        $this->fieldDescriptors['password'] = new DoctrineFieldDescriptor(
            'password',
            'password',
            $this->container->getParameter('sulu.model.user.class')
        );
        $this->fieldDescriptors['locale'] = new DoctrineFieldDescriptor(
            'locale',
            'locale',
            $this->container->getParameter('sulu.model.user.class')
        );
        $this->fieldDescriptors['salt'] = new DoctrineFieldDescriptor(
            'salt',
            'salt',
            $this->container->getParameter('sulu.model.user.class')
        );
        $this->fieldDescriptors['apiKey'] = new DoctrineFieldDescriptor(
            'apiKey',
            'apiKey',
            $this->container->getParameter('sulu.model.user.class')
        );
    }

    /**
     * Returns the user with the given id.
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

        $view = $this->responseGetById($id, $find);

        $this->addSerializationGroups($view);

        return $this->handleView($view);
    }

    /**
     * Creates a new user in the system.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        try {
            $this->checkArguments($request);
            $locale = $this->getRequestParameter($request, 'locale', true);
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

        $this->addSerializationGroups($view);

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

        $this->addSerializationGroups($view);

        return $this->handleView($view);
    }

    /**
     * Updates the given user with the given data.
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
            $locale = $this->getRequestParameter($request, 'locale', true);
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

        $this->addSerializationGroups($view);

        return $this->handleView($view);
    }

    /**
     * Partly updates a user entity for a given id.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction(Request $request, $id)
    {
        try {
            $locale = $this->getRequestParameter($request, 'locale');
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

        $this->addSerializationGroups($view);

        return $this->handleView($view);
    }

    /**
     * Deletes the user with the given id.
     *
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = $this->getUserManager()->delete();
        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Checks if all the arguments are given, and throws an exception if one is missing.
     *
     * @throws \Sulu\Component\Rest\Exception\MissingArgumentException
     */

    // TODO: Use schema validation see:
    // https://github.com/sulu-io/sulu/issues/1136

    private function checkArguments(Request $request)
    {
        if ($request->get('username') == null) {
            throw new MissingArgumentException($this->container->getParameter('sulu.model.user.class'), 'username');
        }
        if ($request->get('password') === null) {
            throw new MissingArgumentException($this->container->getParameter('sulu.model.user.class'), 'password');
        }
        if ($request->get('locale') == null) {
            throw new MissingArgumentException($this->container->getParameter('sulu.model.user.class'), 'locale');
        }
        if ($request->get('contact') == null) {
            throw new MissingArgumentException($this->container->getParameter('sulu.model.user.class'), 'contact');
        }
    }

    /**
     * Returns a user with a specific contact id or all users
     * optional parameter 'flat' calls listAction.
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

            $listBuilder = $factory->create($this->container->getParameter('sulu.model.user.class'));

            $restHelper->initializeListBuilder($listBuilder, $this->getFieldDescriptors());

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
                $entities = [];
                $entities[] = $this->getDoctrine()->getRepository(
                    $this->container->getParameter('sulu.model.user.class')
                )->findUserByContact($contactId);
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

        $this->addSerializationGroups($view);

        return $this->handleView($view);
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.security.users';
    }

    /**
     * Adds the necessary serialization groups to the given view.
     */
    private function addSerializationGroups($view)
    {
        // set serialization groups
        $view->setSerializationContext(
            SerializationContext::create()->setGroups(
                ['Default', 'partialContact', 'fullUser']
            )
        );
    }

    /**
     * Returns the UserManager.
     *
     * @return UserManager
     */
    private function getUserManager()
    {
        return $this->get('sulu_security.user_manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(Request $request)
    {
        return;
    }
}

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

use Doctrine\ORM\NoResultException;
use Sulu\Bundle\SecurityBundle\Security\Exception\InvalidTokenException;
use Sulu\Bundle\SecurityBundle\Security\Exception\MissingPasswordException;
use Sulu\Bundle\SecurityBundle\Security\Exception\TokenAlreadyRequestedException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class ResettingController
 * @package Sulu\Bundle\SecurityBundle\Controller
 */
class ResettingController extends Controller
{

    const ENTITY_NAME_USER = 'SuluSecurityBundle:User';

    /**
     * Generates a token for a user and sends an email with
     * a link to the resetting route
     * @param Request $request
     * @return JsonResponse
     */
    public function sendEmailAction(Request $request) {
        try {
            /** @var User $user */
            $user = $this->findUser($request->get('user'));
            $this->generateTokenForUser($user);
            $email = $this->getEmail($user);
            // TODO: send message to email
            $response = new JsonResponse(array(
                'success' => true,
                'email' => $email
            ));
        } catch(EntityNotFoundException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        } catch(TokenAlreadyRequestedException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        }

        return $response;
    }

    /**
     * Resets a users password
     * @param Request $request
     * @param $token - the token to identify the user
     * @return JsonResponse
     */
    public function resetAction(Request $request, $token) {
        try {
            /** @var User $user */
            $user = $this->findUserByToken($token);
            $this->changePassword($user, $request->get('password', ''));
            $this->deleteToken($user);
            $this->loginUser($user, $request);
            $response = new JsonResponse(array(
                'success' => true,
                'url' => $this->get('router')->generate('sulu_admin')
            ));
        } catch(InvalidTokenException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        } catch(MissingPasswordException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        }

        return $response;
    }

    /**
     * Returns the users email or as a fallback the installation-email-adress
     * @param User $user
     * @return string
     */
    private function getEmail($user) {
        if ($user->getEmail() !== '') {
            return $user->getEmail();
        }
        // TODO: load from config
        return '';
    }

    /**
     * Finds a user with an identifier (username or email)
     * @param $identifier
     * @return \Symfony\Component\Security\Core\User\UserInterface
     * @throws EntityNotFoundException
     */
    private function findUser($identifier) {
        try {
            return $this->getDoctrine()->getRepository(static::ENTITY_NAME_USER)->findUserByIdentifier($identifier);
        } catch (NoResultException $exc) {
            throw new EntityNotFoundException(static::ENTITY_NAME_USER, $identifier);
        }
    }

    /**
     * Returns a user for a given token
     * @param $token
     * @return \Symfony\Component\Security\Core\User\UserInterface
     * @throws InvalidTokenException
     */
    private function findUserByToken($token) {
        try {
            return $this->getDoctrine()->getRepository(static::ENTITY_NAME_USER)->findUserByToken($token);
        } catch (NoResultException $exc) {
            throw new InvalidTokenException($token);
        }
    }

    /**
     * @return \Sulu\Bundle\SecurityBundle\Util\TokenGeneratorInterface
     */
    private function getTokenGenerator() {
        return $this->get('sulu_security.token_generator');
    }

    /**
     * Gives a user a token, so she's logged in
     * @param User $user
     * @param $request
     */
    private function loginUser($user, $request) {
        $token = new UsernamePasswordToken($user, null, 'admin', $user->getRoles());
        $this->get('security.context')->setToken($token); //now the user is logged in

        //now dispatch the login event
        $event = new InteractiveLoginEvent($request, $token);
        $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
    }

    /**
     * Deletes the user's reset-password-token
     * @param User $user
     */
    private function deleteToken($user) {
        $em = $this->getDoctrine()->getManager();
        $user->setPasswordResetToken(null);
        $user->setTokenExpiresAt(null);
        $em->persist($user);
        $em->flush();
    }

    /**
     * Changes the password of a user
     * @param User $user
     * @param string $password
     * @throws MissingPasswordException
     */
    private function changePassword($user, $password) {
        if ($password) {
            throw new MissingPasswordException();
        }
        $em = $this->getDoctrine()->getManager();
        $user->setPassword($this->encodePassword($user, $password, $user->getSalt()));
        $em->persist($user);
        $em->flush();
    }

    /**
     * Generates a new token for a new user
     * @param User $user
     * @throws TokenAlreadyRequestedException
     */
    private function generateTokenForUser($user) {
        if ($user->getPasswordResetToken() !== '' &&
            (new \DateTime())->add($this->getResetInterval()) < $user->getTokenExpiresAt()->add($this->getRequestInterval())
        ) { // if a token was already requested within the request interval time frame
            throw new TokenAlreadyRequestedException($this->getRequestInterval());
        }
        $em = $this->getDoctrine()->getManager();

        $user->setPasswordResetToken($this->getToken());
        $expireDateTime = (new \DateTime())->add($this->getResetInterval());
        $user->setTokenExpiresAt($expireDateTime);

        $em->persist($user);
        $em->flush();
    }

    /**
     * Returns a unique token
     * @return string - token
     */
    private function getToken() {
        return $this->getUniqueToken($this->getTokenGenerator()->generateToken());
    }

    /**
     * If the passed token is unique returns it back otherwise returns a unique token
     * @param $startToken - the token to start width
     * @return string a unique token
     */
    private function getUniqueToken($startToken) {
        try {
            $this->getDoctrine()->getRepository(static::ENTITY_NAME_USER)->findUserByToken($startToken);
        } catch(NoResultException $ex) {
            return $startToken;
        }
        return $this->getUniqueToken($this->getTokenGenerator()->generateToken());
    }

    /**
     * Returns an encoded password gor a given one
     * @param $user
     * @param $password
     * @param $salt
     * @return mixed
     */
    private function encodePassword($user, $password, $salt)
    {
        $encoder = $this->get('security.encoder_factory')->getEncoder($user);

        return $encoder->encodePassword($password, $salt);
    }

    /**
     * The interval in which the token is valid
     * @return \DateInterval
     */
    private function getResetInterval() {
        return new \DateInterval('PT24H');
    }

    /**
     * The interval in which only one token can be generated
     * @return \DateInterval
     */
    private function getRequestInterval() {
        return new \DateInterval('PT10M');
    }
}

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

use Doctrine\ORM\NoResultException;
use Sulu\Bundle\SecurityBundle\Security\Exception\InvalidTokenException;
use Sulu\Bundle\SecurityBundle\Security\Exception\MissingPasswordException;
use Sulu\Bundle\SecurityBundle\Security\Exception\NoTokenFoundException;
use Sulu\Bundle\SecurityBundle\Security\Exception\TokenAlreadyRequestedException;
use Sulu\Bundle\SecurityBundle\Security\Exception\TokenEmailsLimitReachedException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Translation\Translator;

/**
 * Class ResettingController.
 */
class ResettingController extends Controller
{
    protected static $emailSubjectKey = 'security.reset.mail-subject';
    protected static $emailMessageKey = 'security.reset.mail-message';
    protected static $translationDomain = 'backend';
    protected static $resetRouteId = 'sulu_admin.reset';
    const MAX_NUMBER_EMAILS = 3;

    /**
     * The interval in which the token is valid.
     *
     * @return \DateInterval
     */
    private static function getResetInterval()
    {
        return new \DateInterval('PT24H');
    }

    /**
     * The interval in which only one token can be generated.
     *
     * @return \DateInterval
     */
    private static function getRequestInterval()
    {
        return new \DateInterval('PT10M');
    }

    /**
     * Generates a token for a user and sends an email with
     * a link to the resetting route.
     *
     * @param Request $request
     * @param bool    $generateNewKey If true a new token will be generated before sending the mail
     *
     * @return JsonResponse
     */
    public function sendEmailAction(Request $request, $generateNewKey = true)
    {
        try {
            /** @var UserInterface $user */
            $user = $this->findUser($request->get('user'));
            if ($generateNewKey === true) {
                $this->generateTokenForUser($user);
            }
            $email = $this->getEmail($user);
            $this->sendTokenEmail($user, $this->getSenderAddress($request), $email);
            $response = new JsonResponse(['email' => $email]);
        } catch (EntityNotFoundException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        } catch (TokenAlreadyRequestedException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        } catch (NoTokenFoundException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        } catch (TokenEmailsLimitReachedException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        }

        return $response;
    }

    /**
     * Resets a users password.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function resetAction(Request $request)
    {
        try {
            $token = $request->get('token');
            /** @var UserInterface $user */
            $user = $this->findUserByValidToken($token);
            $this->changePassword($user, $request->get('password', ''));
            $this->deleteToken($user);
            $this->loginUser($user, $request);
            $response = new JsonResponse(['url' => $this->get('router')->generate('sulu_admin')]);
        } catch (InvalidTokenException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        } catch (MissingPasswordException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        }

        return $response;
    }

    /**
     * Returns the sender's email address.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function getSenderAddress(Request $request)
    {
        return 'no-reply@' . $request->getHost();
    }

    /**
     * @return Translator
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }

    /**
     * @return string
     */
    protected function getSubject()
    {
        return $this->getTranslator()->trans(
            static::$emailSubjectKey,
            [],
            static::$translationDomain
        );
    }

    /**
     * @param UserInterface $user
     *
     * @return string
     */
    protected function getMessage($user)
    {
        $message = $this->getTranslator()->trans(
            static::$emailMessageKey,
            [],
            static::$translationDomain
        );

        $message .= PHP_EOL;

        $message .= $this->generateUrl(
            static::$resetRouteId,
            ['token' => $user->getPasswordResetToken()],
            true
        );

        return $message;
    }

    /**
     * Returns the users email or as a fallback the installation-email-adress.
     *
     * @param UserInterface $user
     *
     * @return string
     */
    private function getEmail(UserInterface $user)
    {
        if ($user->getEmail() !== null) {
            return $user->getEmail();
        }

        return $this->container->getParameter('sulu_admin.email');
    }

    /**
     * Finds a user with an identifier (username or email).
     *
     * @param string $identifier
     *
     * @return UserInterface
     *
     * @throws EntityNotFoundException
     */
    private function findUser($identifier)
    {
        try {
            return $this->getUserRepository()->findUserByIdentifier($identifier);
        } catch (NoResultException $exc) {
            throw new EntityNotFoundException($this->getUserRepository()->getClassName(), $identifier);
        }
    }

    /**
     * Returns a user for a given token and checks if the token is still valid.
     *
     * @param string $token
     *
     * @return \Symfony\Component\Security\Core\User\UserInterface
     *
     * @throws InvalidTokenException
     */
    private function findUserByValidToken($token)
    {
        try {
            /** @var UserInterface $user */
            $user = $this->getUserRepository()->findUserByToken($token);
            if (new \DateTime() > $user->getPasswordResetTokenExpiresAt()) {
                throw new InvalidTokenException($token);
            }

            return $user;
        } catch (NoResultException $exc) {
            throw new InvalidTokenException($token);
        }
    }

    /**
     * @return \Sulu\Bundle\SecurityBundle\Util\TokenGeneratorInterface
     */
    private function getTokenGenerator()
    {
        return $this->get('sulu_security.token_generator');
    }

    /**
     * Gives a user a token, so she's logged in.
     *
     * @param UserInterface $user
     * @param $request
     */
    private function loginUser(UserInterface $user, $request)
    {
        $token = new UsernamePasswordToken($user, null, 'admin', $user->getRoles());
        $this->get('security.token_storage')->setToken($token); //now the user is logged in

        //now dispatch the login event
        $event = new InteractiveLoginEvent($request, $token);
        $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
    }

    /**
     * Deletes the user's reset-password-token.
     *
     * @param UserInterface $user
     */
    private function deleteToken(UserInterface $user)
    {
        $em = $this->getDoctrine()->getManager();
        $user->setPasswordResetToken(null);
        $user->setPasswordResetTokenExpiresAt(null);
        $user->setPasswordResetTokenEmailsSent(null);
        $em->persist($user);
        $em->flush();
    }

    /**
     * Sends the password-reset-token of a user to an email-adress.
     *
     * @param UserInterface $user
     * @param string        $from From-Email-Address
     * @param string        $to   To-Email-Address
     *
     * @throws NoTokenFoundException
     * @throws TokenEmailsLimitReachedException
     */
    private function sendTokenEmail(UserInterface $user, $from, $to)
    {
        if ($user->getPasswordResetToken() === null) {
            throw new NoTokenFoundException($user);
        }
        if ($user->getPasswordResetTokenEmailsSent() === self::MAX_NUMBER_EMAILS) {
            throw new TokenEmailsLimitReachedException(self::MAX_NUMBER_EMAILS, $user);
        }
        $mailer = $this->get('mailer');
        $em = $this->getDoctrine()->getManager();
        $message = $mailer->createMessage()
            ->setSubject(
                $this->getSubject()
            )
            ->setFrom($from)
            ->setTo($to)
            ->setBody(
                $this->getMessage($user)
            );
        $mailer->send($message);
        $user->setPasswordResetTokenEmailsSent($user->getPasswordResetTokenEmailsSent() + 1);
        $em->persist($user);
        $em->flush();
    }

    /**
     * Changes the password of a user.
     *
     * @param UserInterface $user
     * @param string        $password
     *
     * @throws MissingPasswordException
     */
    private function changePassword(UserInterface $user, $password)
    {
        if ($password === '') {
            throw new MissingPasswordException();
        }
        $em = $this->getDoctrine()->getManager();
        $user->setPassword($this->encodePassword($user, $password, $user->getSalt()));
        $em->persist($user);
        $em->flush();
    }

    /**
     * Generates a new token for a new user.
     *
     * @param UserInterface $user
     *
     * @throws TokenAlreadyRequestedException
     */
    private function generateTokenForUser(UserInterface $user)
    {
        // if a token was already requested within the request interval time frame
        if ($user->getPasswordResetToken() !== null &&
            $this->dateIsInRequestFrame($user->getPasswordResetTokenExpiresAt())
        ) {
            throw new TokenAlreadyRequestedException(self::getRequestInterval());
        }
        $em = $this->getDoctrine()->getManager();

        $user->setPasswordResetToken($this->getToken());
        $expireDateTime = (new \DateTime())->add(self::getResetInterval());
        $user->setPasswordResetTokenExpiresAt($expireDateTime);
        $user->setPasswordResetTokenEmailsSent(0);

        $em->persist($user);
        $em->flush();
    }

    /**
     * Takes a date-time of a reset-token and returns true iff the token associated with the date-time
     * was requested less then the request-interval before. (So there is not really a need to generate a new token).
     *
     * @param \DateTime $date
     *
     * @return bool
     */
    private function dateIsInRequestFrame(\DateTime $date)
    {
        if ($date === null) {
            return false;
        }

        return (new \DateTime())->add(self::getResetInterval()) < $date->add(self::getRequestInterval());
    }

    /**
     * Returns a unique token.
     *
     * @return string the unique token
     */
    private function getToken()
    {
        return $this->getUniqueToken($this->getTokenGenerator()->generateToken());
    }

    /**
     * If the passed token is unique returns it back otherwise returns a unique token.
     *
     * @param string $startToken The token to start width
     *
     * @return string a unique token
     */
    private function getUniqueToken($startToken)
    {
        try {
            $this->getUserRepository()->findUserByToken($startToken);
        } catch (NoResultException $ex) {
            return $startToken;
        }

        return $this->getUniqueToken($this->getTokenGenerator()->generateToken());
    }

    /**
     * Returns an encoded password gor a given one.
     *
     * @param UserInterface $user
     * @param string        $password
     * @param string        $salt
     *
     * @return mixed
     */
    private function encodePassword(UserInterface $user, $password, $salt)
    {
        $encoder = $this->get('security.encoder_factory')->getEncoder($user);

        return $encoder->encodePassword($password, $salt);
    }

    /**
     * @return UserRepositoryInterface
     */
    private function getUserRepository()
    {
        return $this->get('sulu.repository.user');
    }
}

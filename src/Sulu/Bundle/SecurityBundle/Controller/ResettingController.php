<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\SecurityBundle\Domain\Event\UserPasswordResettedEvent;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Exception\UserNotInSystemException;
use Sulu\Bundle\SecurityBundle\Security\Exception\EmailTemplateException;
use Sulu\Bundle\SecurityBundle\Security\Exception\InvalidTokenException;
use Sulu\Bundle\SecurityBundle\Security\Exception\MissingPasswordException;
use Sulu\Bundle\SecurityBundle\Security\Exception\NoTokenFoundException;
use Sulu\Bundle\SecurityBundle\Security\Exception\TokenEmailsLimitReachedException;
use Sulu\Bundle\SecurityBundle\Util\TokenGeneratorInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Security\Authentication\UserInterface as SuluUserInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Class ResettingController.
 */
class ResettingController
{
    protected static $resetRouteId = 'sulu_admin';

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var TokenGeneratorInterface
     */
    protected $tokenGenerator;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var Mailer|\Swift_Mailer
     */
    protected $mailer;

    /**
     * @var PasswordHasherFactoryInterface|EncoderFactoryInterface
     */
    protected $passwordHasherFactory;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    /**
     * @var string
     */
    protected $suluSecuritySystem;

    /**
     * @var string
     */
    protected $sender;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $translationDomain;

    /**
     * @var string
     */
    protected $mailTemplate;

    /**
     * @var string
     */
    protected $adminMail;

    /**
     * @var string
     */
    protected $tokenSendLimit;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @param PasswordHasherFactoryInterface|EncoderFactoryInterface $passwordHasherFactory
     */
    public function __construct(
        ValidatorInterface $validator,
        TranslatorInterface $translator,
        TokenGeneratorInterface $tokenGenerator,
        Environment $templating,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $dispatcher,
        $mailer,
        $passwordHasherFactory,
        UserRepositoryInterface $userRepository,
        UrlGeneratorInterface $router,
        EntityManagerInterface $entityManager,
        DomainEventCollectorInterface $domainEventCollector,
        string $suluSecuritySystem,
        string $sender,
        string $subject,
        string $translationDomain,
        string $mailTemplate,
        string $tokenSendLimit,
        string $adminMail,
        string $secret
    ) {
        $this->validator = $validator;
        $this->translator = $translator;
        $this->tokenGenerator = $tokenGenerator;
        $this->twig = $templating;
        $this->tokenStorage = $tokenStorage;
        $this->dispatcher = $dispatcher;
        $this->mailer = $mailer;
        $this->passwordHasherFactory = $passwordHasherFactory;
        $this->userRepository = $userRepository;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->domainEventCollector = $domainEventCollector;

        $this->suluSecuritySystem = $suluSecuritySystem;
        $this->sender = $sender;
        $this->subject = $subject;
        $this->translationDomain = $translationDomain;
        $this->mailTemplate = $mailTemplate;
        $this->tokenSendLimit = $tokenSendLimit;
        $this->adminMail = $adminMail;
        $this->secret = $secret;
    }

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
     * @return JsonResponse
     */
    public function sendEmailAction(Request $request)
    {
        try {
            /** @var UserInterface $user */
            $user = $this->findUser($request->get('user'));
            $token = $this->generateTokenForUser($user);
            $email = $this->getEmail($user);
            $this->sendTokenEmail($user, $this->getSenderAddress($request), $email, $token);
        } catch (\Exception $ex) {
            // do nothing
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Resets a users password.
     *
     * @return JsonResponse
     */
    public function resetAction(Request $request)
    {
        try {
            $token = $request->get('token');

            if (null == $token) {
                throw new NoTokenFoundException();
            }

            /** @var User $user */
            $user = $this->findUserByValidToken($this->generateTokenHash($token));
            $this->changePassword($user, $request->get('password', ''));
            $this->deleteToken($user);
            $this->loginUser($user, $request);
            $response = new JsonResponse(['user' => $user->getUsername()]);
        } catch (InvalidTokenException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        } catch (MissingPasswordException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        } catch (NoTokenFoundException $ex) {
            $response = new JsonResponse($ex->toArray(), 400);
        }

        return $response;
    }

    /**
     * Returns the sender's email address.
     *
     * @return string
     */
    protected function getSenderAddress(Request $request)
    {
        $sender = $this->sender;

        if (!$sender || !$this->isEmailValid($sender)) {
            $sender = 'no-reply@' . $request->getHost();
        }

        return $sender;
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    protected function isEmailValid($email)
    {
        $constraint = new EmailConstraint();
        $result = $this->validator->validate($email, $constraint);

        return 0 === \count($result);
    }

    /**
     * @return string
     */
    protected function getSubject()
    {
        return $this->translator->trans(
            $this->subject,
            [],
            $this->translationDomain
        );
    }

    /**
     * @param UserInterface $user
     *
     * @return string
     *
     * @throws EmailTemplateException
     */
    protected function getMessage($user, string $token)
    {
        $resetUrl = $this->router->generate(static::$resetRouteId, [], UrlGeneratorInterface::ABSOLUTE_URL);
        $template = $this->mailTemplate;
        $translationDomain = $this->translationDomain;

        if (!$this->twig->getLoader()->exists($template)) {
            throw new EmailTemplateException($template);
        }

        return \trim(
            $this->twig->render(
                $template,
                [
                    'user' => $user,
                    'reset_url' => $resetUrl . '#/?forgotPasswordToken=' . $token,
                    'translation_domain' => $translationDomain,
                ]
            )
        );
    }

    /**
     * Returns the users email or as a fallback the installation-email-adress.
     *
     * @return string
     */
    private function getEmail(UserInterface $user)
    {
        if (null !== $user->getEmail()) {
            return $user->getEmail();
        }

        return $this->adminMail;
    }

    /**
     * Finds a user with an identifier (username or email).
     *
     * @param string $identifier
     *
     * @return UserInterface
     *
     * @throws EntityNotFoundException
     * @throws UserNotInSystemException
     */
    private function findUser($identifier)
    {
        try {
            $user = $this->userRepository->findUserByIdentifier($identifier);
        } catch (NoResultException $exc) {
            throw new EntityNotFoundException($this->userRepository->getClassName(), $identifier, $exc);
        }

        if (!$this->hasSystem($user)) {
            throw new UserNotInSystemException($this->getSystem(), $identifier);
        }

        return $user;
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
            $user = $this->userRepository->findUserByToken($token);
            if (new \DateTime() > $user->getPasswordResetTokenExpiresAt()) {
                throw new InvalidTokenException($token);
            }

            return $user;
        } catch (NoResultException $exc) {
            throw new InvalidTokenException($token, $exc);
        }
    }

    /**
     * Gives a user a token, so she's logged in.
     */
    private function loginUser(UserInterface $user, $request)
    {
        $token = new UsernamePasswordToken($user, 'admin', $user->getRoles());
        $this->tokenStorage->setToken($token); //now the user is logged in

        //now dispatch the login event
        $event = new InteractiveLoginEvent($request, $token);
        $this->dispatcher->dispatch($event, 'security.interactive_login');
    }

    /**
     * Deletes the user's reset-password-token.
     */
    private function deleteToken(UserInterface $user)
    {
        $user->setPasswordResetToken(null);
        $user->setPasswordResetTokenExpiresAt(null);
        $user->setPasswordResetTokenEmailsSent(null);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Sends the password-reset-token of a user to an email-adress.
     *
     * @param string $from From-Email-Address
     * @param string $to To-Email-Address
     *
     * @throws TokenEmailsLimitReachedException
     */
    private function sendTokenEmail(UserInterface $user, string $from, string $to, string $token)
    {
        $maxNumberEmails = $this->tokenSendLimit;

        if (new \DateTime() < $user->getPasswordResetTokenExpiresAt() && $user->getPasswordResetTokenEmailsSent() === \intval($maxNumberEmails)) {
            throw new TokenEmailsLimitReachedException($maxNumberEmails, $user);
        }

        if ($this->mailer instanceof \Swift_Mailer) {
            $message = $this->mailer->createMessage()
                ->setSubject($this->getSubject())
                ->setFrom($from)
                ->setTo($to)
                ->setBody($this->getMessage($user, $token), 'text/html');

            $this->mailer->send($message);
        } else {
            $message = (new Email())
                ->subject($this->getSubject())
                ->from($from)
                ->to($to)
                ->html($this->getMessage($user, $token));

            $this->mailer->send($message);
        }

        $user->setPasswordResetTokenEmailsSent($user->getPasswordResetTokenEmailsSent() + 1);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Changes the password of a user.
     *
     * @param string $password
     *
     * @throws MissingPasswordException
     */
    private function changePassword(User $user, $password)
    {
        if ('' === $password) {
            throw new MissingPasswordException();
        }
        $user->setPassword($this->encodePassword($user, $password, $user->getSalt()));
        $this->entityManager->persist($user);

        $this->domainEventCollector->collect(new UserPasswordResettedEvent($user));
        $this->entityManager->flush();
    }

    /**
     * Generates a new token for a new user.*.
     */
    private function generateTokenForUser(UserInterface $user)
    {
        $token = $this->getToken();
        $user->setPasswordResetToken($this->generateTokenHash($token));
        $expireDateTime = (new \DateTime())->add(self::getResetInterval());
        $user->setPasswordResetTokenExpiresAt($expireDateTime);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $token;
    }

    /**
     * Generates a hash for the specified token.
     */
    private function generateTokenHash(string $token): string
    {
        return \hash('sha1', $this->secret . $token);
    }

    /**
     * Returns a unique token.
     *
     * @return string the unique token
     */
    private function getToken()
    {
        return $this->getUniqueToken($this->tokenGenerator->generateToken());
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
            $this->userRepository->findUserByToken($startToken);
        } catch (NoResultException $ex) {
            return $startToken;
        }

        return $this->getUniqueToken($this->tokenGenerator->generateToken());
    }

    /**
     * Returns an encoded password gor a given one.
     *
     * @param string $password
     * @param string $salt
     */
    private function encodePassword(UserInterface $user, $password, $salt)
    {
        if ($this->passwordHasherFactory instanceof PasswordHasherFactoryInterface) {
            $hasher = $this->passwordHasherFactory->getPasswordHasher($user);
            $password = $hasher->hash($password);
        } else {
            $encoder = $this->passwordHasherFactory->getEncoder($user);
            $password = $encoder->encodePassword($password, $salt);
        }

        return $password;
    }

    /**
     * Check if given user has sulu-system.
     *
     * @return bool
     */
    private function hasSystem(SuluUserInterface $user)
    {
        $system = $this->getSystem();
        foreach ($user->getRoleObjects() as $role) {
            if ($role->getSystem() === $system) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns system name.
     *
     * @return string
     */
    private function getSystem()
    {
        return $this->suluSecuritySystem;
    }
}

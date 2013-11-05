<?php


namespace Sulu\Bundle\SecurityBundle\Services;
use Sulu\Bundle\AdminBundle\UserData\UserDataInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\SecurityContextInterface;


class UserDataHandler implements UserDataInterface
{

    protected $security;
    protected $router;

    /**
     * @param SecurityContextInterface $security
     * @param RouterInterface $router
     */
    public function __construct(SecurityContextInterface $security, RouterInterface $router)
    {
        $this->security = $security;
        $this->router = $router;
    }

    /**
     * @return Boolean - returns if user is admin user
     */
    public function isAdminUser()
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    /**
     * @return Boolean - returns if a user is logged in
     */
    public function isLoggedIn()
    {
        if( $this->security->isGranted('IS_AUTHENTICATED_REMEMBERED') ){
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return String - returns username
     */
    public function getUserName()
    {
        $user = $this->getUser()->getContact();
        if (!isset($user)) {
            return $this->getUser()->getUsername();
        }
        return $user->getFirstName().' '.$user->getLastName();
    }

    /**
     * @return String - returns UserIcon URL
     */
    public function getUserIcon()
    {
        // TODO: Implement getUserIcon() method.
    }

    /**
     * @return String - returns Logout URL
     */
    public function getLogoutLink()
    {
        return "http://".$this->router->getContext()->getHost().'/admin/logout';
    }


    /**
     * Get a user from the Security Context
     *
     * @return mixed
     *
     * @throws \LogicException If SecurityBundle is not available
     *
     * @see Symfony\Component\Security\Core\Authentication\Token\TokenInterface::getUser()
     */
    protected function getUser()
    {
        if (!$this->security) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->security->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }
}

<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * appTestUrlMatcher
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class appTestUrlMatcher extends Symfony\Bundle\FrameworkBundle\Routing\RedirectableUrlMatcher
{
    /**
     * Constructor.
     */
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function match($pathinfo)
    {
        $allow = array();
        $pathinfo = rawurldecode($pathinfo);
        $context = $this->context;
        $request = $this->request;

        if (0 === strpos($pathinfo, '/security')) {
            // sulu_security.roles_navigation
            if ($pathinfo === '/security/navigation/roles') {
                return array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\NavigationController::rolesAction',  '_route' => 'sulu_security.roles_navigation',);
            }

            if (0 === strpos($pathinfo, '/security/template')) {
                // sulu_security.template.role.form
                if ($pathinfo === '/security/template/role/form.html') {
                    return array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\TemplateController::roleformAction',  '_route' => 'sulu_security.template.role.form',);
                }

                // sulu_security.template.permission.form
                if ($pathinfo === '/security/template/permission/form.html') {
                    return array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\TemplateController::permissionformAction',  '_route' => 'sulu_security.template.permission.form',);
                }

                // sulu_security.template.role.list
                if ($pathinfo === '/security/template/role/list.html') {
                    return array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\TemplateController::roleListAction',  '_route' => 'sulu_security.template.role.list',);
                }

            }

        }

        if (0 === strpos($pathinfo, '/api')) {
            if (0 === strpos($pathinfo, '/api/roles')) {
                if (0 === strpos($pathinfo, '/api/roles/fields')) {
                    // get_role_fields
                    if (preg_match('#^/api/roles/fields(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_get_role_fields;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_role_fields')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\RoleController::getFieldsAction',  '_format' => 'json',));
                    }
                    not_get_role_fields:

                    // put_role_fields
                    if (preg_match('#^/api/roles/fields(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_put_role_fields;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'put_role_fields')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\RoleController::putFieldsAction',  '_format' => 'json',));
                    }
                    not_put_role_fields:

                }

                // get_roles
                if (preg_match('#^/api/roles(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_get_roles;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_roles')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\RoleController::cgetAction',  '_format' => 'json',));
                }
                not_get_roles:

                // get_role
                if (preg_match('#^/api/roles/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_get_role;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_role')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\RoleController::getAction',  '_format' => 'json',));
                }
                not_get_role:

                // post_role
                if (preg_match('#^/api/roles(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_post_role;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'post_role')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\RoleController::postAction',  '_format' => 'json',));
                }
                not_post_role:

                // put_role
                if (preg_match('#^/api/roles/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_put_role;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'put_role')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\RoleController::putAction',  '_format' => 'json',));
                }
                not_put_role:

                // delete_role
                if (preg_match('#^/api/roles/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_delete_role;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'delete_role')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\RoleController::deleteAction',  '_format' => 'json',));
                }
                not_delete_role:

            }

            if (0 === strpos($pathinfo, '/api/groups')) {
                // get_groups
                if (preg_match('#^/api/groups(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_get_groups;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_groups')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\GroupController::cgetAction',  '_format' => 'json',));
                }
                not_get_groups:

                // get_group
                if (preg_match('#^/api/groups/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_get_group;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_group')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\GroupController::getAction',  '_format' => 'json',));
                }
                not_get_group:

                // post_group
                if (preg_match('#^/api/groups(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_post_group;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'post_group')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\GroupController::postAction',  '_format' => 'json',));
                }
                not_post_group:

                // put_group
                if (preg_match('#^/api/groups/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_put_group;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'put_group')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\GroupController::putAction',  '_format' => 'json',));
                }
                not_put_group:

                // delete_group
                if (preg_match('#^/api/groups/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_delete_group;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'delete_group')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\GroupController::deleteAction',  '_format' => 'json',));
                }
                not_delete_group:

            }

            if (0 === strpos($pathinfo, '/api/users')) {
                // get_user
                if (preg_match('#^/api/users/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_get_user;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_user')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\UserController::getAction',  '_format' => 'json',));
                }
                not_get_user:

                // post_user
                if (preg_match('#^/api/users(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_post_user;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'post_user')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\UserController::postAction',  '_format' => 'json',));
                }
                not_post_user:

                // put_user
                if (preg_match('#^/api/users/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_put_user;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'put_user')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\UserController::putAction',  '_format' => 'json',));
                }
                not_put_user:

                // patch_user
                if (preg_match('#^/api/users/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PATCH') {
                        $allow[] = 'PATCH';
                        goto not_patch_user;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'patch_user')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\UserController::patchAction',  '_format' => 'json',));
                }
                not_patch_user:

                // put_user_settings
                if (preg_match('#^/api/users/(?P<id>[^/]++)/settings/(?P<key>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_put_user_settings;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'put_user_settings')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\UserController::putSettingsAction',  '_format' => 'json',));
                }
                not_put_user_settings:

                // get_user_settings
                if (preg_match('#^/api/users/(?P<id>[^/]++)/settings/(?P<key>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_get_user_settings;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_user_settings')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\UserController::getSettingsAction',  '_format' => 'json',));
                }
                not_get_user_settings:

                // delete_user
                if (preg_match('#^/api/users/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_delete_user;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'delete_user')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\UserController::deleteAction',  '_format' => 'json',));
                }
                not_delete_user:

                // get_users
                if (preg_match('#^/api/users(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_get_users;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_users')), array (  '_controller' => 'Sulu\\Bundle\\SecurityBundle\\Controller\\UserController::cgetAction',  '_format' => 'json',));
                }
                not_get_users:

            }

        }

        throw 0 < count($allow) ? new MethodNotAllowedException(array_unique($allow)) : new ResourceNotFoundException();
    }
}

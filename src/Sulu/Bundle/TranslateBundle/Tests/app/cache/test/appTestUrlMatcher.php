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

        if (0 === strpos($pathinfo, '/translate')) {
            // sulu_translate.content_navigation
            if ($pathinfo === '/translate/navigation/content') {
                return array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\NavigationController::contentAction',  '_route' => 'sulu_translate.content_navigation',);
            }

            if (0 === strpos($pathinfo, '/translate/template')) {
                // sulu_translate.template.catalogue.form
                if ($pathinfo === '/translate/template/package/form.html') {
                    return array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\TemplateController::packageFormAction',  '_route' => 'sulu_translate.template.catalogue.form',);
                }

                // sulu_translate.template.code.form
                if ($pathinfo === '/translate/template/translation/form.html') {
                    return array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\TemplateController::translationFormAction',  '_route' => 'sulu_translate.template.code.form',);
                }

                // sulu_translate.template.package.list
                if ($pathinfo === '/translate/template/package/list.html') {
                    return array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\TemplateController::packageListAction',  '_route' => 'sulu_translate.template.package.list',);
                }

            }

        }

        if (0 === strpos($pathinfo, '/api')) {
            if (0 === strpos($pathinfo, '/api/packages')) {
                if (0 === strpos($pathinfo, '/api/packages/fields')) {
                    // get_package_fields
                    if (preg_match('#^/api/packages/fields(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_get_package_fields;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_package_fields')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\PackageController::getFieldsAction',  '_format' => 'json',));
                    }
                    not_get_package_fields:

                    // put_package_fields
                    if (preg_match('#^/api/packages/fields(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_put_package_fields;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'put_package_fields')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\PackageController::putFieldsAction',  '_format' => 'json',));
                    }
                    not_put_package_fields:

                }

                // get_packages
                if (preg_match('#^/api/packages(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_get_packages;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_packages')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\PackageController::cgetAction',  '_format' => 'json',));
                }
                not_get_packages:

                // get_package
                if (preg_match('#^/api/packages/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_get_package;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_package')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\PackageController::getAction',  '_format' => 'json',));
                }
                not_get_package:

                // post_package
                if (preg_match('#^/api/packages(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_post_package;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'post_package')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\PackageController::postAction',  '_format' => 'json',));
                }
                not_post_package:

                // put_package
                if (preg_match('#^/api/packages/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_put_package;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'put_package')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\PackageController::putAction',  '_format' => 'json',));
                }
                not_put_package:

                // delete_package
                if (preg_match('#^/api/packages/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_delete_package;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'delete_package')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\PackageController::deleteAction',  '_format' => 'json',));
                }
                not_delete_package:

            }

            if (0 === strpos($pathinfo, '/api/c')) {
                if (0 === strpos($pathinfo, '/api/catalogues')) {
                    // get_fields
                    if (0 === strpos($pathinfo, '/api/catalogues/fields') && preg_match('#^/api/catalogues/fields(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_get_fields;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_fields')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\CatalogueController::getFieldsAction',  '_format' => 'json',));
                    }
                    not_get_fields:

                    // get_catalogue
                    if (preg_match('#^/api/catalogues/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_get_catalogue;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_catalogue')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\CatalogueController::getCatalogueAction',  '_format' => 'json',));
                    }
                    not_get_catalogue:

                    // get_catalogues
                    if (preg_match('#^/api/catalogues(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_get_catalogues;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_catalogues')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\CatalogueController::cgetCataloguesAction',  '_format' => 'json',));
                    }
                    not_get_catalogues:

                    // delete_catalogues
                    if (preg_match('#^/api/catalogues/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_delete_catalogues;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'delete_catalogues')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\CatalogueController::deleteCataloguesAction',  '_format' => 'json',));
                    }
                    not_delete_catalogues:

                }

                if (0 === strpos($pathinfo, '/api/codes')) {
                    // get_code_fields
                    if (0 === strpos($pathinfo, '/api/codes/fields') && preg_match('#^/api/codes/fields(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_get_code_fields;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_code_fields')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\CodeController::getFieldsAction',  '_format' => 'json',));
                    }
                    not_get_code_fields:

                    // get_codes
                    if (preg_match('#^/api/codes(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_get_codes;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_codes')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\CodeController::cgetAction',  '_format' => 'json',));
                    }
                    not_get_codes:

                    // get_code
                    if (preg_match('#^/api/codes/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_get_code;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_code')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\CodeController::getAction',  '_format' => 'json',));
                    }
                    not_get_code:

                    // post_code
                    if (preg_match('#^/api/codes(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'POST') {
                            $allow[] = 'POST';
                            goto not_post_code;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'post_code')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\CodeController::postAction',  '_format' => 'json',));
                    }
                    not_post_code:

                    // put_code
                    if (preg_match('#^/api/codes/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_put_code;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'put_code')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\CodeController::putAction',  '_format' => 'json',));
                    }
                    not_put_code:

                    // delete_code
                    if (preg_match('#^/api/codes/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_delete_code;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'delete_code')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\CodeController::deleteAction',  '_format' => 'json',));
                    }
                    not_delete_code:

                }

                // cget_catalogues_translations
                if (0 === strpos($pathinfo, '/api/catalogues') && preg_match('#^/api/catalogues/(?P<slug>[^/]++)/translations(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_cget_catalogues_translations;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'cget_catalogues_translations')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\TranslationsController::cgetAction',  '_format' => 'json',));
                }
                not_cget_catalogues_translations:

            }

            // get_catalogues_translations_fields
            if (0 === strpos($pathinfo, '/api/translations/fields') && preg_match('#^/api/translations/fields(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_get_catalogues_translations_fields;
                }

                return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_catalogues_translations_fields')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\TranslationsController::getFieldsAction',  '_format' => 'json',));
            }
            not_get_catalogues_translations_fields:

            if (0 === strpos($pathinfo, '/api/catalogues')) {
                // get_catalogues_translations
                if (preg_match('#^/api/catalogues/(?P<slug>[^/]++)/translations(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_get_catalogues_translations;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_catalogues_translations')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\TranslationsController::cgetAction',  '_format' => 'json',));
                }
                not_get_catalogues_translations:

                // patch_catalogues_translations
                if (preg_match('#^/api/catalogues/(?P<slug>[^/]++)/translations(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PATCH') {
                        $allow[] = 'PATCH';
                        goto not_patch_catalogues_translations;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'patch_catalogues_translations')), array (  '_controller' => 'Sulu\\Bundle\\TranslateBundle\\Controller\\TranslationsController::patchAction',  '_format' => 'json',));
                }
                not_patch_catalogues_translations:

            }

        }

        throw 0 < count($allow) ? new MethodNotAllowedException(array_unique($allow)) : new ResourceNotFoundException();
    }
}

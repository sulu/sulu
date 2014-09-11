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

        if (0 === strpos($pathinfo, '/api')) {
            if (0 === strpos($pathinfo, '/api/collection')) {
                if (0 === strpos($pathinfo, '/api/collection/fields')) {
                    // get_collection_fields
                    if (preg_match('#^/api/collection/fields(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_get_collection_fields;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_collection_fields')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\CollectionController::getFieldsAction',  '_format' => 'json',));
                    }
                    not_get_collection_fields:

                    // put_collection_fields
                    if (preg_match('#^/api/collection/fields(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_put_collection_fields;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'put_collection_fields')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\CollectionController::putFieldsAction',  '_format' => 'json',));
                    }
                    not_put_collection_fields:

                }

                if (0 === strpos($pathinfo, '/api/collections')) {
                    // get_collection
                    if (preg_match('#^/api/collections/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_get_collection;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_collection')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\CollectionController::getAction',  '_format' => 'json',));
                    }
                    not_get_collection:

                    // get_collections
                    if (preg_match('#^/api/collections(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                            $allow = array_merge($allow, array('GET', 'HEAD'));
                            goto not_get_collections;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_collections')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\CollectionController::cgetAction',  '_format' => 'json',));
                    }
                    not_get_collections:

                    // post_collection
                    if (preg_match('#^/api/collections(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'POST') {
                            $allow[] = 'POST';
                            goto not_post_collection;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'post_collection')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\CollectionController::postAction',  '_format' => 'json',));
                    }
                    not_post_collection:

                    // put_collection
                    if (preg_match('#^/api/collections/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'PUT') {
                            $allow[] = 'PUT';
                            goto not_put_collection;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'put_collection')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\CollectionController::putAction',  '_format' => 'json',));
                    }
                    not_put_collection:

                    // delete_collection
                    if (preg_match('#^/api/collections/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                        if ($this->context->getMethod() != 'DELETE') {
                            $allow[] = 'DELETE';
                            goto not_delete_collection;
                        }

                        return $this->mergeDefaults(array_replace($matches, array('_route' => 'delete_collection')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\CollectionController::deleteAction',  '_format' => 'json',));
                    }
                    not_delete_collection:

                }

            }

            if (0 === strpos($pathinfo, '/api/media')) {
                // cget_media
                if (preg_match('#^/api/media(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_cget_media;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'cget_media')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\MediaController::cgetAction',  '_format' => 'json',));
                }
                not_cget_media:

                // file_media_version_update
                if (preg_match('#^/api/media/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_file_media_version_update;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'file_media_version_update')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\MediaController::fileVersionUpdateAction',  '_format' => 'json',));
                }
                not_file_media_version_update:

                // get_media_fields
                if (0 === strpos($pathinfo, '/api/media/fields') && preg_match('#^/api/media/fields(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_get_media_fields;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_media_fields')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\MediaController::getFieldsAction',  '_format' => 'json',));
                }
                not_get_media_fields:

                // get_media
                if (preg_match('#^/api/media/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                        $allow = array_merge($allow, array('GET', 'HEAD'));
                        goto not_get_media;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'get_media')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\MediaController::getAction',  '_format' => 'json',));
                }
                not_get_media:

                // post_media
                if (preg_match('#^/api/media(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'POST') {
                        $allow[] = 'POST';
                        goto not_post_media;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'post_media')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\MediaController::postAction',  '_format' => 'json',));
                }
                not_post_media:

                // put_media
                if (preg_match('#^/api/media/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'PUT') {
                        $allow[] = 'PUT';
                        goto not_put_media;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'put_media')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\MediaController::putAction',  '_format' => 'json',));
                }
                not_put_media:

                // delete_media
                if (preg_match('#^/api/media/(?P<id>[^/\\.]++)(?:\\.(?P<_format>json|xml|html))?$#s', $pathinfo, $matches)) {
                    if ($this->context->getMethod() != 'DELETE') {
                        $allow[] = 'DELETE';
                        goto not_delete_media;
                    }

                    return $this->mergeDefaults(array_replace($matches, array('_route' => 'delete_media')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\MediaController::deleteAction',  '_format' => 'json',));
                }
                not_delete_media:

            }

        }

        // sulu_media.website.image.proxy
        if (0 === strpos($pathinfo, '/uploads/media') && preg_match('#^/uploads/media/(?P<slug>.*)$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'sulu_media.website.image.proxy')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\MediaStreamController::getImageAction',));
        }

        // sulu_media.website.media.download
        if (0 === strpos($pathinfo, '/media') && preg_match('#^/media/(?P<id>[^/]++)/download/(?P<slug>.*)$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'sulu_media.website.media.download')), array (  '_controller' => 'Sulu\\Bundle\\MediaBundle\\Controller\\MediaStreamController::downloadAction',));
        }

        throw 0 < count($allow) ? new MethodNotAllowedException(array_unique($allow)) : new ResourceNotFoundException();
    }
}

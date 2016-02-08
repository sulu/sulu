<?php

namespace Sulu\Component\Routing\Auto;

use Symfony\Cmf\Component\RoutingAuto\AdapterInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\HttpCache\ProxyClient\Symfony;
use Symfony\Cmf\Component\RoutingAuto\UriContext;
use Symfony\Cmf\Component\RoutingAuto\Model\AutoRouteInterface;
use Sulu\Component\DocumentManager\ClassNameInflector;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;

class SuluAdapter implements AdapterInterface
{
    private $inspector;
    private $manager;
    private $pathBuilder;

    public function __construct(
        DocumentInspector $inspector, 
        DocumentManager $manager,
        PathBuilder $pathBuilder
    )
    {
        $this->inspector = $inspector;
        $this->manager = $manager;
        $this->pathBuilder = $pathBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocales($object)
    {
        if (!$object instanceof StructureBehavior) {
            return array();
        }

        return $this->inspector->getLocales($object);
    }

    /**
     * {@inheritdoc}
     */
    public function translateObject($object, $locale)
    {
        $uuid = $this->inspector->getUuid($object);
        $this->manager->find($uuid, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function createAutoRoute(UriContext $uriContext, $document, $tag)
    {
        $locale = $uriContext->getLocale();

        if (!$locale) {
            throw new \InvalidArgumentException(sprintf(
                'Document with title "%s" has no locale, cannot create auto route',
                $document->getTitle()
            ));
        }

        $webspace = $this->inspector->getWebspace($document);
        $uri = $uriContext->getUri();
        $path = $this->getRoutePath($webspace, $locale, $uri);

        $document = new RouteDocument();
        $document->setTargetDocument($document);
        $document->setType(AutoRouteInterface::TYPE_PRIMARY);
        $document->setAutoRouteTag($tag);

        $this->manager->persist(
            $document,
            $locale,
            array(
                'path' => $path,
                'auto_create' => true,
            )
        );

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function getRealClassName($className)
    {
        return ClassNameInflector::getUserClassName($className);
    }

    /**
     * {@inheritdoc}
     */
    public function compareAutoRouteContent(AutoRouteInterface $autoRoute, $contentObject)
    {
        if ($autoRoute->getContent() === $contentObject) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function findRouteForUri($uri, UriContext $uriContext)
    {
        $subject = $uriContext->getSubjectObject();
        $webspace = $this->inspector->getWebspace($subject);
        $locale = $uriContext->getLocale();
        $path = $this->getRoutePath($webspace, $locale, $uri);

        try {
            $route = $this->manager->find($path, $locale);
        } catch (DocumentNotFoundException $e) {
            return null;
        }

        return $route;
    }

    /**
     * {@inheritdoc}
     */
    public function generateAutoRouteTag(UriContext $uriContext)
    {
        return $uriContext->getLocale() ?: '_NO_LOCALE_';
    }

    /**
     * {@inheritdoc}
     */
    public function migrateAutoRouteChildren(AutoRouteInterface $srcAutoRoute, AutoRouteInterface $destAutoRoute)
    {
        $srcChildren = $this->inspector->getChildren($srcAutoRoute);

        foreach ($srcChildren as $srcChild) {
            $this->manager->move($srcChild, $this->inspector->getUuid($destAutoRoute));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeAutoRoute(AutoRouteInterface $autoRoute)
    {
        $this->manager->remove($autoRoute);
    }

    /**
     * {@inheritdoc}
     */
    public function getReferringAutoRoutes($contentDocument)
    {
        $referrers = $this->inspector->getReferrers($contentDocument);
        $routes = array();

        foreach ($referrers as $referrer) {
            if (!$referrer instanceof AutoRouteInterface) {
                continue;
            }

            $routes[] = $referrer;
        }

        return $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function createRedirectRoute(AutoRouteInterface $referringAutoRoute, AutoRouteInterface $newRoute)
    {
        $referringAutoRoute->setType(AutoRouteInterface::TYPE_REDIRECT);
    }

    private function getRoutePath($webspaceKey, $locale, $uri)
    {
        if (substr($uri, 0, 1) == '/') {
            $uri = substr($uri, 1);
        }
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%route%', $locale, $uri]);
    }
}

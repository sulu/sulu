<?php

namespace PoolAlpin\Bundle\BaseBundle\Listener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Sulu\Component\Validation\JsonSchema\Validate;
use Sulu\Component\Validation\JsonSchema\Exceptions\SchemaValidationException;

class ControllerListener
{
    private $annotationReader;

    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * onKernelController
     *
     * Checks for annotations of type @Validate
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        list($object, $method) = $controller;

        // the controller could be a proxy, e.g. when using the JMSSecuriyExtraBundle or JMSDiExtraBundle
        $className = ClassUtils::getClass($object);
        $reflectionClass = new \ReflectionClass($className);
        $reflectionMethod = $reflectionClass->getMethod($method);
        $allAnnotations = $this->annotationReader->getMethodAnnotations($reflectionMethod);

        // filter all instances of JsonSchema\Validation
        $validateAnnotations = array_filter($allAnnotations, function ($annotation) {
            return $annotation instanceof Validate;
        });

        $urlParams = $event->getRequest()->query->all();
        $skippedAnnotations = [];
        foreach ($validateAnnotations as $validateAnnotation) {
            // check if an url parameter is defined and validate
            if (isset($urlParams[$validateAnnotation->parameter])) {
                if (json_decode($urlParams[$validateAnnotation->parameter]) == true) {
                    $this->validate($validateAnnotation, $reflectionClass, $event);
                    return;
                }
            } 
            // otherwise if no parameter is set, add to skipped annotations
            elseif (!$validateAnnotation->parameter) {
                array_push($skippedAnnotations, $validateAnnotation);
            }
        }
        // validate all annotations without a parameter
        foreach ($skippedAnnotations as $validateAnnotation) {
            $this->validate($validateAnnotation, $reflectionClass, $event);
        }
        unset($skippedAnnotations);
    }

    /**
     * validate
     *
     * Get the schema from within the appropriate bundle and checks it against
     * the json content provided by the request.
     *
     * @param Annotation $annotation
     * @param $reflectionClass
     * @param FilterControllerEvent $event
     * @throws SchemaValidationException
     */
    public function validate($annotation, \ReflectionClass $reflectionClass, FilterControllerEvent $event)
    {
        $rootDir = dirname($reflectionClass->getFileName());
        $schemaUrl = $rootDir . '../Resources/schema/' . $annotation->value . '.json';
        $schema = json_decode(file_get_contents($schemaUrl));
        $data = json_decode(json_encode($event->getRequest()->request->all()));

        // Validate
        $validator = new \JsonSchema\Validator();
        $validator->check($data, $schema);

        if (!$validator->isValid()) {
            throw new SchemaValidationException($validator->getErrors());
        }
    }
}

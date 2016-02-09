<?php

namespace Sulu\Component\Routing\Auto\Provider;

use Symfony\Cmf\Component\RoutingAuto\TokenProviderInterface;
use Symfony\Cmf\Component\RoutingAuto\UriContext;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Subscriber\ResourceSegmentSubscriber;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentHelper;
use Sulu\Component\Routing\UriUtils;

/**
 * Provides the Sulu resource locator for the subject object, or
 * the subject objects parent.
 */
class ResourceLocatorProvider implements TokenProviderInterface
{
    /**
     * @var DocumentManager
     */
    private $manager;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @param DocumentManager $manager
     */
    public function __construct(
        DocumentManager $manager,
        DocumentInspector $inspector
    )
    {
        $this->manager = $manager;
        $this->inspector = $inspector;
    }

    /**
     * {@inheritDoc}
     */
    public function provideValue(UriContext $uriContext, $options)
    {
        $subject = $uriContext->getSubjectObject();
        $document = $subject;

        if (!$document instanceof ResourceSegmentBehavior) {
            throw new \InvalidArgumentException(
                'Document must be instance of ResourceSegmentBehavior'
            );
        }

        if ($options['parent']) {
            $parentDocument = $this->inspector->getParent($document);

            if (!$parentDocument) {
                throw new \RuntimeException(sprintf(
                    'Document "%s" has no parent when trying to provide parent resource locator path',
                    DocumentHelper::getDebugTitle($document)
                ));
            }

            $document = $parentDocument;
        }

        $resourceSegments = array();

        do {
            if (!$document instanceof ResourceSegmentBehavior) {
                break;
            }

            $this->manager->find($this->inspector->getUuid($document), $uriContext->getLocale());

            $resourceSegment = $document->getResourceSegment();
            $resourceSegment = UriUtils::relatizive($resourceSegment);

            if ($resourceSegment) {
                $resourceSegments[] = $resourceSegment;
            }

        } while ($document = $this->inspector->getParent($document));

        $resourceSegments = array_reverse($resourceSegments);

        $value = implode('/', $resourceSegments);


        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults(array(
            'parent' => false,
        ));
    }
}

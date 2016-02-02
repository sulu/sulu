<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Form\DataTransformer;

use PHPCR\Util\UUIDHelper;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DocumentToUuidTransformer implements DataTransformerInterface
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function transform($document)
    {
        if (null === $document) {
            return;
        }

        // TODO: Use the document inspector instead of the UUID behavior
        if (!$document instanceof UuidBehavior) {
            throw new \RuntimeException(sprintf(
                'Document must implement UuuidBehavior to be used in a form. Got "%s"',
                is_object($document) ? get_class($document) : gettype($document)
            ));
        }

        return $document->getUuid();
    }

    public function reverseTransform($uuid)
    {
        if (!$uuid) {
            return;
        }

        if (!UUIDHelper::isUuid($uuid)) {
            throw new TransformationFailedException(sprintf(
                'Given UUID is not a UUID, given: "%s"',
                $uuid
            ));
        }

        $document = $this->documentManager->find($uuid);

        if (null === $document) {
            throw new TransformationFailedException(sprintf(
                'Could not find document with UUID "%s"', $uuid
            ));
        }

        return $document;
    }
}

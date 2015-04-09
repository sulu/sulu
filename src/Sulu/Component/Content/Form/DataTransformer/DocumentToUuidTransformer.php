<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Bundle\ContentBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\ODM\PHPCR\DocumentManager;
use DTL\Component\Content\Document\DocumentInterface;
use PHPCR\Util\UUIDHelper;
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

        $node = $this->documentManager->getNodeForDocument($document);

        return $node->getIdentifier();
    }

    public function reverseTransform($uuid)
    {
        if (!$uuid) {
            return null;
        }

        if (!UUIDHelper::isUuid($uuid)) {
            throw new TransformationFailedException(sprintf(
                'Given UUID is not a UUID, given: "%s"',
                $uuid
            ));
        }

        $document = $this->documentManager->find(null, $uuid);

        if (null === $document) {
            throw new TransformationFailedException(sprintf(
                'Could not find document with UUID "%s"', $uuid
            ));
        }

        return $document;
    }
}

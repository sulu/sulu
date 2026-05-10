<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Controller;

use Sulu\Bundle\MarkupBundle\Tag\TagNotFoundException;
use Sulu\Bundle\TagBundle\Admin\TagAdmin;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\AbstractController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Serializer\SuluSerializerInterface;
use Symfony\Component\HttpFoundation\Response;

final class TagGetAction extends AbstractController implements SecuredControllerInterface
{
    protected static $entityName = \Sulu\Bundle\TagBundle\Entity\Tag::class;

    protected $unsortable = [];

    protected $bundlePrefix = 'tags.';

    public function __construct(
        private SuluSerializerInterface $suluSerializer,
        private readonly TagManagerInterface $tagManager,
    ) {
        parent::__construct($suluSerializer);
    }

    public function __invoke(int $id): Response
    {
        try {
            $tag = $this->tagManager->findById($id);

            if (!$tag) {
                throw new EntityNotFoundException(self::$entityName, $id);
            }

            return $this->suluSerializer->handleView($tag, ['partialTag']);
        } catch (TagNotFoundException | EntityNotFoundException $exc) {
            $exception = new EntityNotFoundException(self::$entityName, $id);

            return $this->suluSerializer->handleView($exception->toArray(), [], 404);
        }
    }

    /**
     * @return string
     */
    public function getSecurityContext(): string
    {
        return TagAdmin::SECURITY_CONTEXT;
    }
}

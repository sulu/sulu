<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Application\MessageHandler;

use Sulu\Article\Application\Mapper\ArticleMapperInterface;
use Sulu\Article\Application\Message\CreateArticleMessage;
use Sulu\Article\Domain\Event\ArticleCreatedEvent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create a ArticleMapper to extend this Handler.
 */
final class CreateArticleMessageHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        /** @var iterable<ArticleMapperInterface> */
        private iterable $articleMappers,
        private DomainEventCollectorInterface $domainEventCollector,
        private ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    public function __invoke(CreateArticleMessage $message): ArticleInterface
    {
        $data = $message->getData();

        if (!\array_key_exists('author', $data)) {
            $token = $this->tokenStorage?->getToken();
            $user = $token?->getUser();
            if (null !== $token && $user instanceof User) {
                $contact = $user->getContact();
                $data['author'] = $contact->getId();
            }
        }

        if (!\array_key_exists('authored', $data)) {
            $data['authored'] = (new \DateTimeImmutable())->format('c');
        }

        $article = $this->articleRepository->createNew($message->getUuid());

        foreach ($this->articleMappers as $articleMapper) {
            $articleMapper->mapArticleData($article, $data);
        }

        $this->articleRepository->add($article);

        /** @var string $locale */
        $locale = $data['locale'];

        $this->domainEventCollector->collect(new ArticleCreatedEvent($article, $locale, $data));

        return $article;
    }
}

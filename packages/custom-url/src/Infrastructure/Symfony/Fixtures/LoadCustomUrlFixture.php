<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Infrastructure\Symfony\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Sulu\CustomUrl\Domain\Model\CustomUrl;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepositoryInterface;

/**
 * @codeCoverageIgnore
 */
final class LoadCustomUrlFixture extends AbstractFixture implements FixtureInterface
{
    public function __construct(
        private readonly CustomUrlRepositoryInterface $customUrlRepository
    ) {
    }

    private function generateRandomString(): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = \strlen($characters);
        $randomString = '';

        for ($i = 0; $i < 10; ++$i) {
            $randomString .= $characters[\random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10_000; ++$i) {
            // Create custom url entity and persist it
            $customUrl = new CustomUrl();
            $customUrl->setTitle($this->generateRandomString());
            $customUrl->setWebspace('sulu-blog');
            $customUrl->setPublished(0 === $i % 2);
            $customUrl->setBaseDomain('localhost/*');
            $customUrl->setDomainParts(['test' . $i]);
            $customUrl->setTargetDocument('1234');
            $customUrl->setTargetLocale('en');
            $customUrl->setCanonical(true);
            $customUrl->setRedirect(false);
            $customUrl->setNoFollow(true);
            $customUrl->setNoIndex(true);

            $this->customUrlRepository->add($customUrl);

            if (0 === $i % 1_000) {
                echo 'Flushing ' . $i . \PHP_EOL;
                $manager->flush();
                $manager->clear();
            }
        }
        $manager->flush();
    }
}

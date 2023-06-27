<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Tests\Functional\Infrastructure\Doctrine\Repository;

use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ReferenceBundle\Domain\Exception\ReferenceNotFoundException;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ReferenceRepositoryTest extends SuluTestCase
{
    use SetGetPrivatePropertyTrait;

    private ReferenceRepositoryInterface $referenceRepository;

    public function setUp(): void
    {
        $this->referenceRepository = self::getContainer()->get(ReferenceRepositoryInterface::class);
    }

    public function testCreateNew(): void
    {
        $reference = $this->referenceRepository->create(
            'media',
            (string) 1,
            'pages',
            '434fb6f3-b64e-45e8-8287-fc9667bd3d50',
            'en',
            'Page Title',
            'headerImage',
            ['locale' => 'en']
        );

        static::assertSame('media', $reference->getResourceKey());
        static::assertSame('1', $reference->getResourceId());
        static::assertSame('pages', $reference->getReferenceResourceKey());
        static::assertSame('434fb6f3-b64e-45e8-8287-fc9667bd3d50', $reference->getReferenceResourceId());
        static::assertSame('en', $reference->getReferenceLocale());
        static::assertSame('Page Title', $reference->getReferenceTitle());
        static::assertSame('headerImage', $reference->getReferenceProperty());
        static::assertSame(['locale' => 'en'], $reference->getReferenceViewAttributes());
    }

    public function testFindOneByNotExist(): void
    {
        $this->assertNull($this->referenceRepository->findOneBy(['id' => 2147483647]));
    }

    public function testFindOneByExist(): void
    {
        $reference = $this->createReference();

        $this->referenceRepository->add($reference);
        $this->referenceRepository->flush();

        $referenceId = $reference->getId();

        $reference = $this->referenceRepository->getOneBy(['id' => $referenceId]);

        $this->assertSame($referenceId, $reference->getId());
    }

    public function testGetOneByNotExist(): void
    {
        $this->expectException(ReferenceNotFoundException::class);

        $this->referenceRepository->getOneBy(['id' => 2147483647]);
    }

    public function testGetOneByExist(): void
    {
        $reference = $this->createReference();

        $this->referenceRepository->add($reference);
        $this->referenceRepository->flush();

        $referenceId = $reference->getId();

        $reference = $this->referenceRepository->getOneBy(['id' => $referenceId]);

        $this->assertSame($referenceId, $reference->getId());
    }

    public function testAddAndRemove(): void
    {
        $reference = $this->createReference();

        $this->referenceRepository->add($reference);
        $this->referenceRepository->flush();

        $referenceId = $reference->getId();

        $this->assertNotNull($this->referenceRepository->findOneBy(['id' => $referenceId]));

        $this->referenceRepository->remove($reference);
        $this->referenceRepository->flush();

        $this->assertNull($this->referenceRepository->findOneBy(['id' => $referenceId]));
    }

    public function testRemoveBy(): void
    {
        static::purgeDatabase();

        $referenceResourceKey = 'pages';
        $referenceResourceId = Uuid::uuid4()->toString();
        $referenceLocale = 'en';

        $reference1 = $this->createReference();
        $reference2 = $this->createReference();
        $reference3 = $this->createReference();
        $reference4 = $this->createReference();
        static::setPrivateProperty($reference2, 'referenceResourceKey', $referenceResourceKey);
        static::setPrivateProperty($reference3, 'referenceResourceId', $referenceResourceId);
        static::setPrivateProperty($reference3, 'referenceLocale', $referenceLocale);
        static::setPrivateProperty($reference4, 'referenceResourceKey', $referenceResourceKey);
        static::setPrivateProperty($reference4, 'referenceResourceId', $referenceResourceId);
        static::setPrivateProperty($reference4, 'referenceLocale', $referenceLocale);

        $this->referenceRepository->add($reference1);
        $this->referenceRepository->add($reference2);
        $this->referenceRepository->add($reference3);
        $this->referenceRepository->add($reference4);

        $this->referenceRepository->flush();
        $reference1Id = $reference1->getId();
        $reference2Id = $reference2->getId();
        $reference3Id = $reference3->getId();
        $reference4Id = $reference4->getId();

        $this->referenceRepository->removeBy([
            'referenceResourceKey' => $referenceResourceKey,
            'referenceResourceId' => $referenceResourceId,
            'referenceLocale' => $referenceLocale,
        ]);

        $this->referenceRepository->flush();

        $this->assertNotNull($this->referenceRepository->findOneBy(['id' => $reference1Id]));
        $this->assertNotNull($this->referenceRepository->findOneBy(['id' => $reference2Id]));
        $this->assertNull($this->referenceRepository->findOneBy(['id' => $reference3Id]));
        $this->assertNull($this->referenceRepository->findOneBy(['id' => $reference4Id]));
    }

    private function createReference(): ReferenceInterface
    {
        $reference = $this->referenceRepository->create(
            'media',
            '1',
            'pages',
            Uuid::uuid4()->toString(),
            'en',
            'Page Title',
            'headerImage',
            ['locale' => 'en']
        );

        return $reference;
    }
}

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

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class FileVersionMediaLanguageTest extends SuluTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();
        $this->entityManager = self::getEntityManager();
    }

    public function testPersistAndReloadKeepsTheLanguages(): void
    {
        $id = $this->persistFileVersion(['de', 'en']);

        $this->entityManager->clear();
        $fileVersion = $this->entityManager->find(FileVersion::class, $id);
        self::assertInstanceOf(FileVersion::class, $fileVersion);
        self::assertSame(['de', 'en'], $fileVersion->getMediaLanguages());
    }

    public function testUpdatingLanguagesRemovesTheOldOnes(): void
    {
        $id = $this->persistFileVersion(['de', 'en']);

        $this->entityManager->clear();
        $fileVersion = $this->entityManager->find(FileVersion::class, $id);
        self::assertInstanceOf(FileVersion::class, $fileVersion);
        $fileVersion->setMediaLanguages(['fr']);
        $this->entityManager->flush();

        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(FileVersion::class, $id);
        self::assertInstanceOf(FileVersion::class, $reloaded);
        self::assertSame(['fr'], $reloaded->getMediaLanguages());

        // orphanRemoval must have deleted the two previous rows, leaving exactly one
        $count = $this->entityManager->getConnection()
            ->fetchOne('SELECT COUNT(*) FROM me_file_version_media_languages');
        self::assertEquals(1, $count);
    }

    public function testClearingLanguagesLeavesAnEmptyArray(): void
    {
        $id = $this->persistFileVersion(['de']);

        $this->entityManager->clear();
        $fileVersion = $this->entityManager->find(FileVersion::class, $id);
        self::assertInstanceOf(FileVersion::class, $fileVersion);
        $fileVersion->setMediaLanguages([]);
        $this->entityManager->flush();

        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(FileVersion::class, $id);
        self::assertInstanceOf(FileVersion::class, $reloaded);
        self::assertSame([], $reloaded->getMediaLanguages());
    }

    public function testCloneCarriesTheLanguagesForward(): void
    {
        $id = $this->persistFileVersion(['de', 'en']);

        $this->entityManager->clear();
        $fileVersion = $this->entityManager->find(FileVersion::class, $id);
        self::assertInstanceOf(FileVersion::class, $fileVersion);

        $clone = clone $fileVersion;
        $clone->setName('clone.pdf');
        $clone->setVersion(2);
        $this->entityManager->persist($clone);
        $this->entityManager->flush();

        $this->entityManager->clear();
        $reloadedClone = $this->entityManager->find(FileVersion::class, $clone->getId());
        self::assertInstanceOf(FileVersion::class, $reloadedClone);
        self::assertSame(['de', 'en'], $reloadedClone->getMediaLanguages());

        // the clone owns its own rows: two originals plus two clones
        $count = $this->entityManager->getConnection()
            ->fetchOne('SELECT COUNT(*) FROM me_file_version_media_languages');
        self::assertEquals(4, $count);
    }

    public function testResavingAnOverlappingLanguageSetKeepsUnchangedRows(): void
    {
        $id = $this->persistFileVersion(['en']);
        $enRowId = $this->fetchMediaLanguageRowId($id, 'en');

        $this->entityManager->clear();
        $fileVersion = $this->entityManager->find(FileVersion::class, $id);
        self::assertInstanceOf(FileVersion::class, $fileVersion);
        // 'en' stays and 'fr' is added: the kept row must not be deleted and re-inserted
        $fileVersion->setMediaLanguages(['en', 'fr']);
        $this->entityManager->flush();

        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(FileVersion::class, $id);
        self::assertInstanceOf(FileVersion::class, $reloaded);
        self::assertSame(['en', 'fr'], $reloaded->getMediaLanguages());
        self::assertSame($enRowId, $this->fetchMediaLanguageRowId($id, 'en'));
        $frRowId = $this->fetchMediaLanguageRowId($id, 'fr');

        // re-saving the identical set is a no-op
        $reloaded->setMediaLanguages(['en', 'fr']);
        $this->entityManager->flush();

        self::assertSame($enRowId, $this->fetchMediaLanguageRowId($id, 'en'));
        self::assertSame($frRowId, $this->fetchMediaLanguageRowId($id, 'fr'));
    }

    private function fetchMediaLanguageRowId(int $fileVersionId, string $language): int
    {
        $rowId = $this->entityManager->getConnection()->fetchOne(
            'SELECT id FROM me_file_version_media_languages WHERE idFileVersions = ? AND language = ?',
            [$fileVersionId, $language],
        );
        self::assertIsNumeric($rowId);

        return (int) $rowId;
    }

    /**
     * @param string[] $languages
     */
    private function persistFileVersion(array $languages): int
    {
        $fileVersion = new FileVersion();
        $fileVersion->setName('document.pdf');
        $fileVersion->setVersion(1);
        $fileVersion->setSize(1024);
        $fileVersion->setMimeType('application/pdf');
        $fileVersion->setCreated(new \DateTimeImmutable('2026-09-23'));
        $fileVersion->setChanged(new \DateTimeImmutable('2026-09-23'));
        $fileVersion->setMediaLanguages($languages);

        $this->entityManager->persist($fileVersion);
        $this->entityManager->flush();

        return $fileVersion->getId();
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Tests\Functional\Translate;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Code;
use Sulu\Bundle\TranslateBundle\Entity\Location;
use Sulu\Bundle\TranslateBundle\Entity\Package;
use Sulu\Bundle\TranslateBundle\Entity\Translation;
use Sulu\Bundle\TranslateBundle\Translate\Export;

class ExportTest extends SuluTestCase
{
    private static $fixturePath;

    /**
     * @var Export
     */
    protected $export;

    /**
     * @var array
     */
    protected static $entities;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$fixturePath = __DIR__ . '/../../DataFixtures/Files';
    }

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();

        $this->export = new Export($this->em);

        //
        // Package - id 1
        // -------------------------------

        //Insert some data in the database
        $package = new Package();
        $package->setName('Export');
        $this->package1 = $package;
        $this->em->persist($package);

        $catalogue = new Catalogue();
        $catalogue->setPackage($package);
        $catalogue->setIsDefault(false);
        $catalogue->setLocale('en');
        $this->em->persist($catalogue);

        $location1 = new Location();
        $location1->setName('Newsletter');
        $location1->setPackage($package);
        $this->em->persist($location1);

        $location2 = new Location();
        $location2->setName('Portals');
        $location2->setPackage($package);
        $this->em->persist($location2);

        $code1 = new Code();
        $code1->setPackage($package);
        $code1->setCode('export.easy');
        $code1->setBackend(true);
        $code1->setFrontend(true);
        $code1->setLocation($location1);
        $this->em->persist($code1);

        $code2 = new Code();
        $code2->setPackage($package);
        $code2->setCode('export.great');
        $code2->setBackend(true);
        $code2->setFrontend(false);
        $code2->setLocation($location1);
        $this->em->persist($code2);

        $code3 = new Code();
        $code3->setPackage($package);
        $code3->setCode('export.configurable');
        $code3->setBackend(false);
        $code3->setFrontend(true);
        $code3->setLocation($location2);
        $this->em->persist($code3);

        $this->em->flush();

        $translation1 = new Translation();
        $translation1->setCatalogue($catalogue);
        $translation1->setCode($code1);
        $translation1->setValue('Exports made easy');
        $this->em->persist($translation1);

        $translation2 = new Translation();
        $translation2->setCatalogue($catalogue);
        $translation2->setCode($code2);
        $translation2->setValue('Exports are great');
        $this->em->persist($translation2);

        $translation3 = new Translation();
        $translation3->setCatalogue($catalogue);
        $translation3->setCode($code3);
        $translation3->setValue('Exports are configurable');
        $this->em->persist($translation3);

        //
        // Package - id 2
        // -------------------------------

        //Insert some data in the database
        $package2 = new Package();
        $package2->setName('Export2');
        $this->em->persist($package2);

        $catalogue2 = new Catalogue();
        $catalogue2->setPackage($package2);
        $catalogue2->setIsDefault(false);
        $catalogue2->setLocale('en');
        $this->em->persist($catalogue2);

        $location21 = new Location();
        $location21->setName('Newsletter');
        $location21->setPackage($package2);
        $this->em->persist($location21);

        $location22 = new Location();
        $location22->setName('Portals');
        $location22->setPackage($package2);
        $this->em->persist($location22);

        $code21 = new Code();
        $code21->setPackage($package2);
        $code21->setCode('export.easy2');
        $code21->setBackend(true);
        $code21->setFrontend(true);
        $code21->setLocation($location21);
        $this->em->persist($code21);

        $code22 = new Code();
        $code22->setPackage($package2);
        $code22->setCode('export.great2');
        $code22->setBackend(true);
        $code22->setFrontend(false);
        $code22->setLocation($location21);
        $this->em->persist($code22);

        $code23 = new Code();
        $code23->setPackage($package2);
        $code23->setCode('export.configurable2');
        $code23->setBackend(false);
        $code23->setFrontend(true);
        $code23->setLocation($location22);
        $this->em->persist($code23);

        $this->em->flush();

        $translation21 = new Translation();
        $translation21->setCatalogue($catalogue2);
        $translation21->setCode($code21);
        $translation21->setValue('Exports made super easy');
        $this->em->persist($translation21);

        $translation22 = new Translation();
        $translation22->setCatalogue($catalogue2);
        $translation22->setCode($code22);
        $translation22->setValue('Exports are super great');
        $this->em->persist($translation22);

        $translation23 = new Translation();
        $translation23->setCatalogue($catalogue2);
        $translation23->setCode($code23);
        $translation23->setValue('Exports are super configurable');
        $this->em->persist($translation23);

        $this->em->flush();
    }

    public function tearDown()
    {
        parent::tearDown();
        if (file_exists(self::$fixturePath . '/Export.en.xlf')) {
            unlink(self::$fixturePath . '/Export.en.xlf');
        }
        if (file_exists(self::$fixturePath . '/Export.en.json')) {
            unlink(self::$fixturePath . '/Export.en.json');
        }
    }

    public function testXliffExport()
    {
        $this->export->setPackageId($this->package1->getId());
        $this->export->setLocale('en');
        $this->export->setFilename('sulu');
        $this->export->setFormat(Export::XLIFF);
        $this->export->setPath(self::$fixturePath . '/');
        $this->export->execute();

        $expectedHash = file_get_contents(self::$fixturePath . '/samples/export.xlf');
        $actualHash = file_get_contents(self::$fixturePath . '/sulu.en.xlf');

        $this->assertSame($expectedHash, $actualHash);
    }

    public function testXliffExportLocation()
    {
        $this->export->setPackageId($this->package1->getId());
        $this->export->setLocale('en');
        $this->export->setFilename('sulu');
        $this->export->setFormat(Export::XLIFF);
        $this->export->setLocation('Newsletter');
        $this->export->setPath(self::$fixturePath . '/');
        $this->export->execute();

        $expectedHash = file_get_contents(self::$fixturePath . '/samples/export.newsletter.xlf');
        $actualHash = file_get_contents(self::$fixturePath . '/sulu.en.xlf');

        $this->assertEquals($expectedHash, $actualHash);

        $this->export->setPackageId($this->package1->getId());
        $this->export->setLocale('en');
        $this->export->setFormat(Export::XLIFF);
        $this->export->setLocation('Portals');
        $this->export->setFilename('sulu');
        $this->export->setPath(self::$fixturePath . '/');
        $this->export->execute();

        $expectedHash = file_get_contents(self::$fixturePath . '/samples/export.portals.xlf');
        $actualHash = file_get_contents(self::$fixturePath . '/sulu.en.xlf');

        $this->assertEquals($expectedHash, $actualHash);
    }

    public function testXliffExportBackendFrontend()
    {
        $this->export->setPackageId($this->package1->getId());
        $this->export->setLocale('en');
        $this->export->setFormat(Export::XLIFF);
        $this->export->setFrontend(null);
        $this->export->setBackend(true);
        $this->export->setFilename('sulu');
        $this->export->setPath(self::$fixturePath . '/');
        $this->export->execute();

        $expectedHash = file_get_contents(self::$fixturePath . '/samples/export.backend.xlf');
        $actualHash = file_get_contents(self::$fixturePath . '/sulu.en.xlf');

        $this->assertEquals($expectedHash, $actualHash);

        $this->export->setPackageId($this->package1->getId());
        $this->export->setLocale('en');
        $this->export->setFormat(Export::XLIFF);
        $this->export->setFrontend(true);
        $this->export->setBackend(null);
        $this->export->setPath(self::$fixturePath . '/');
        $this->export->setFilename('sulu');
        $this->export->execute();

        $expectedHash = file_get_contents(self::$fixturePath . '/samples/export.frontend.xlf');
        $actualHash = file_get_contents(self::$fixturePath . '/sulu.en.xlf');

        $this->assertEquals($expectedHash, $actualHash);
    }

    public function testJsonExport()
    {
        $this->export->setPackageId($this->package1->getId());
        $this->export->setLocale('en');
        $this->export->setFormat(Export::JSON);
        $this->export->setPath(self::$fixturePath . '/');
        $this->export->setFilename('sulu');
        $this->export->execute();

        $expectedHash = file_get_contents(self::$fixturePath . '/samples/export.json');
        $actualHash = file_get_contents(self::$fixturePath . '/sulu.en.json');

        $this->assertEquals($expectedHash, $actualHash);
    }

    public function testJsonExportWithoutPackageId()
    {
        $this->export->setLocale('en');
        $this->export->setFormat(Export::JSON);
        $this->export->setPath(self::$fixturePath . '/');
        $this->export->setFilename('sulu');
        $this->export->execute();

        $expectedHash = file_get_contents(self::$fixturePath . '/samples/export.all.json');
        $actualHash = file_get_contents(self::$fixturePath . '/sulu.en.json');

        $this->assertEquals($expectedHash, $actualHash);
    }

    public function testXlfExportWithoutPackageId()
    {
        $this->export->setLocale('en');
        $this->export->setFormat(Export::XLIFF);
        $this->export->setPath(self::$fixturePath . '/');
        $this->export->setFilename('sulu');
        $this->export->execute();

        $expectedHash = file_get_contents(self::$fixturePath . '/samples/export.all.xlf');
        $actualHash = file_get_contents(self::$fixturePath . '/sulu.en.xlf');

        $this->assertEquals($expectedHash, $actualHash);
    }
}

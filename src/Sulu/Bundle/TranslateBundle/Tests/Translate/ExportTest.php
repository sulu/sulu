<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Tests\Translate;

use Sulu\Bundle\CoreBundle\Tests\DatabaseTestCase;
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Code;
use Sulu\Bundle\TranslateBundle\Entity\Location;
use Sulu\Bundle\TranslateBundle\Entity\Package;
use Sulu\Bundle\TranslateBundle\Entity\Translation;
use Sulu\Bundle\TranslateBundle\Translate\Export;

class ExportTest extends DatabaseTestCase
{
    /**
     * @var Export
     */
    protected $export;

    /**
     * @var array
     */
    protected static $entities;

    public function setUp()
    {
        $this->setUpSchema();

        $this->export = new Export(self::$em);

        //Insert some data in the database
        $package = new Package();
        $package->setName('Export');
        self::$em->persist($package);

        $catalogue = new Catalogue();
        $catalogue->setPackage($package);
        $catalogue->setLocale('en');
        self::$em->persist($catalogue);

        $location1 = new Location();
        $location1->setName('Newsletter');
        $location1->setPackage($package);
        self::$em->persist($location1);

        $location2 = new Location();
        $location2->setName('Portals');
        $location2->setPackage($package);
        self::$em->persist($location2);

        $code1 = new Code();
        $code1->setPackage($package);
        $code1->setCode('export.easy');
        $code1->setBackend(true);
        $code1->setFrontend(true);
        $code1->setLocation($location1);
        self::$em->persist($code1);

        $code2 = new Code();
        $code2->setPackage($package);
        $code2->setCode('export.great');
        $code2->setBackend(true);
        $code2->setFrontend(false);
        $code2->setLocation($location1);
        self::$em->persist($code2);

        $code3 = new Code();
        $code3->setPackage($package);
        $code3->setCode('export.configurable');
        $code3->setBackend(false);
        $code3->setFrontend(true);
        $code3->setLocation($location2);
        self::$em->persist($code3);

        self::$em->flush();

        $translation1 = new Translation();
        $translation1->setCatalogue($catalogue);
        $translation1->setCode($code1);
        $translation1->setValue('Exports made easy');
        self::$em->persist($translation1);

        $translation2 = new Translation();
        $translation2->setCatalogue($catalogue);
        $translation2->setCode($code2);
        $translation2->setValue('Exports are great');
        self::$em->persist($translation2);

        $translation3 = new Translation();
        $translation3->setCatalogue($catalogue);
        $translation3->setCode($code3);
        $translation3->setValue('Exports are configurable');
        self::$em->persist($translation3);

        self::$em->flush();
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
        if (file_exists(__DIR__ . '/Export.en.xlf')) {
            unlink(__DIR__ . '/Export.en.xlf');
        }
        if (file_exists(__DIR__ . '/../Fixtures/Export.en.xlf')) {
            unlink(__DIR__ . '/../Fixtures/Export.en.xlf');
        }
    }

    public function setUpSchema()
    {
        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Catalogue'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Code'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Location'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Package'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Translation'),
        );

        self::$tool->createSchema(self::$entities);
    }

    public function testXliffExport()
    {
        $this->export->setPackageId(1);
        $this->export->setLocale('en');
        $this->export->setFormat(Export::XLIFF);
        $this->export->execute();

        $expectedHash = md5_file(__DIR__ . '/../Fixtures/export.xlf');
        $actualHash = md5_file(__DIR__ . '/Export.en.xlf');

        $this->assertEquals($expectedHash, $actualHash);
    }

    public function testXliffExportLocation()
    {
        $this->export->setPackageId(1);
        $this->export->setLocale('en');
        $this->export->setFormat(Export::XLIFF);
        $this->export->setLocation('Newsletter');
        $this->export->execute();

        $expectedHash = md5_file(__DIR__ . '/../Fixtures/export.newsletter.xlf');
        $actualHash = md5_file(__DIR__ . '/Export.en.xlf');

        $this->assertEquals($expectedHash, $actualHash);

        $this->export->setPackageId(1);
        $this->export->setLocale('en');
        $this->export->setFormat(Export::XLIFF);
        $this->export->setLocation('Portals');
        $this->export->execute();

        $expectedHash = md5_file(__DIR__ . '/../Fixtures/export.portals.xlf');
        $actualHash = md5_file(__DIR__ . '/Export.en.xlf');

        $this->assertEquals($expectedHash, $actualHash);
    }

    public function testXliffExportBackendFrontend()
    {
        $this->export->setPackageId(1);
        $this->export->setLocale('en');
        $this->export->setFormat(Export::XLIFF);
        $this->export->setFrontend(null);
        $this->export->setBackend(true);
        $this->export->execute();

        $expectedHash = md5_file(__DIR__ . '/../Fixtures/export.backend.xlf');
        $actualHash = md5_file(__DIR__ . '/Export.en.xlf');

        $this->assertEquals($expectedHash, $actualHash);

        $this->export->setPackageId(1);
        $this->export->setLocale('en');
        $this->export->setFormat(Export::XLIFF);
        $this->export->setFrontend(true);
        $this->export->setBackend(null);
        $this->export->execute();

        $expectedHash = md5_file(__DIR__ . '/../Fixtures/export.frontend.xlf');
        $actualHash = md5_file(__DIR__ . '/Export.en.xlf');

        $this->assertEquals($expectedHash, $actualHash);
    }

    public function testXliffExportPath()
    {
        $this->export->setPackageId(1);
        $this->export->setLocale('en');
        $this->export->setFormat(Export::XLIFF);
        $this->export->setPath(__DIR__ . '/../Fixtures/');
        $this->export->execute();

        $expectedHash = md5_file(__DIR__ . '/../Fixtures/export.xlf');
        $actualHash = md5_file(__DIR__ . '/../Fixtures/Export.en.xlf');

        $this->assertEquals($expectedHash, $actualHash);
    }

    public function testJsonExport()
    {
        $this->export->setPackageId(1);
        $this->export->setLocale('en');
        $this->export->setFormat(Export::JSON);
        $this->export->execute();

        $expectedHash = md5_file(__DIR__ . '/../Fixtures/export.json');
        $actualHash = md5_file(__DIR__ . '/Export.en.json');

        $this->assertEquals($expectedHash, $actualHash);
    }
}

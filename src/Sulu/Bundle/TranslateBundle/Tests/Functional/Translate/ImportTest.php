<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Tests\Functional\Translate;

use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Sulu\Bundle\TranslateBundle\Translate\Import;

class ImportTest extends DatabaseTestCase
{
    private static $fixturePath;

    /**
     * @var Import
     */
    protected $import;

    /**
     * @var array
     */
    protected static $entities;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$fixturePath = __DIR__ . '/../../app/Resources/DataFixtures/Files';
    }

    public function setUp()
    {
        $this->setUpSchema();

        $hikaruBundle = $this->getMock('Symfony\Component\HttpKernel\Bundle', array('getName', 'getPath'));
        $hikaruBundle->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('SuluHikaruBundle'));
        $hikaruBundle->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue(self::$fixturePath . '/bundles/SuluHikaruBundle'));

        $sampleBundle = $this->getMock('Symfony\Component\HttpKernel\Bundle', array('getName', 'getPath'));
        $sampleBundle->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('SuluSampleBundle'));
        $sampleBundle->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue(self::$fixturePath . '/bundles/SuluSampleBundle'));

        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $kernel->expects($this->any())
            ->method('getBundles')
            ->will($this->returnValue(array($sampleBundle, $hikaruBundle)));


        $this->import = new Import(self::$em, $kernel);
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$em->clear();
        self::$tool->dropSchema(self::$entities);
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

    public function testXliffFromFile()
    {
        $this->import->setFile(self::$fixturePath . '/samples/import.xlf');
        $this->import->setName('Import');
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('de');
        $this->import->executeFromFile(true, true);

        $package = self::$em->getRepository('SuluTranslateBundle:Package')->find(1);
        $this->assertEquals(1, $package->getId());
        $this->assertEquals('Import', $package->getName());

        $catalogue = self::$em->getRepository('SuluTranslateBundle:Catalogue')->find(1);
        $this->assertEquals(1, $catalogue->getId());
        $this->assertEquals('de', $catalogue->getLocale());

        $codes = self::$em->getRepository('SuluTranslateBundle:Code')->findAll();
        $this->assertEquals(1, $codes[0]->getId());
        $this->assertEquals('sulu.great', $codes[0]->getCode());
        $this->assertEquals(true, $codes[0]->getBackend());
        $this->assertEquals(true, $codes[0]->getFrontend());
        $this->assertEquals(null, $codes[0]->getLength());
        $this->assertEquals(2, $codes[1]->getId());
        $this->assertEquals('sulu.open', $codes[1]->getCode());
        $this->assertEquals(true, $codes[1]->getBackend());
        $this->assertEquals(true, $codes[1]->getFrontend());
        $this->assertEquals(null, $codes[1]->getLength());

        $translations = self::$em->getRepository('SuluTranslateBundle:Translation')->findAll();
        $this->assertEquals('Sulu ist toll!', $translations[0]->getValue());
        $this->assertEquals('Sulu ist OpenSource!', $translations[1]->getValue());

        // http://stackoverflow.com/questions/18268464/doctrine-lazy-loading-in-symfony-test-environment
        self::$em->clear();

        // test new import
        $this->import->setFile(self::$fixturePath . '/samples/import_better.xlf');
        $this->import->setName('Import Update');
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('de');
        $this->import->setPackageId(1);
        $this->import->executeFromFile(true, true);

        $package = self::$em->getRepository('SuluTranslateBundle:Package')->find(1);
        $this->assertEquals(1, $package->getId());
        $this->assertEquals('Import Update', $package->getName());

        $catalogue = self::$em->getRepository('SuluTranslateBundle:Catalogue')->find(1);
        $this->assertEquals(1, $catalogue->getId());
        $this->assertEquals('de', $catalogue->getLocale());

        $codes = self::$em->getRepository('SuluTranslateBundle:Code')->findAll();
        $this->assertEquals(1, $codes[0]->getId());
        $this->assertEquals('sulu.great', $codes[0]->getCode());
        $this->assertEquals(true, $codes[0]->getBackend());
        $this->assertEquals(true, $codes[0]->getFrontend());
        $this->assertEquals(null, $codes[0]->getLength());
        $this->assertEquals(2, $codes[1]->getId());
        $this->assertEquals('sulu.open', $codes[1]->getCode());
        $this->assertEquals(true, $codes[1]->getBackend());
        $this->assertEquals(true, $codes[1]->getFrontend());
        $this->assertEquals(null, $codes[1]->getLength());
        $this->assertEquals('sulu.very.great', $codes[2]->getCode());
        $this->assertEquals(true, $codes[2]->getBackend());
        $this->assertEquals(true, $codes[2]->getFrontend());
        $this->assertEquals(null, $codes[2]->getLength());
        $this->assertEquals('sulu.even.open', $codes[3]->getCode());
        $this->assertEquals(true, $codes[3]->getBackend());
        $this->assertEquals(true, $codes[3]->getFrontend());
        $this->assertEquals(null, $codes[3]->getLength());

        $translations = self::$em->getRepository('SuluTranslateBundle:Translation')->findAll();

        $values = array(
            'Sulu ist wirklich toll!',
            'Sulu ist OpenSource!',
            'Sulu ist sehr toll!',
            'Sulu ist sogar OpenSource!'
        );
        $this->assertTrue(in_array($translations[0]->getValue(), $values));
        $values = array_diff($values, array($translations[0]->getValue()));
        $this->assertTrue(in_array($translations[1]->getValue(), $values));
        $values = array_diff($values, array($translations[1]->getValue()));
        $this->assertTrue(in_array($translations[2]->getValue(), $values));
        $values = array_diff($values, array($translations[2]->getValue()));
        $this->assertTrue(in_array($translations[3]->getValue(), $values));
        $values = array_diff($values, array($translations[3]->getValue()));
        $this->assertEquals(0, sizeof($values));

        // http://stackoverflow.com/questions/18268464/doctrine-lazy-loading-in-symfony-test-environment
        self::$em->clear();

        // test new import with new language code
        $this->import->setFile(self::$fixturePath . '/samples/import.xlf');
        $this->import->setName('Import');
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('en');
        $this->import->setPackageId(1);
        $this->import->executeFromFile(true, true);

        $package = self::$em->getRepository('SuluTranslateBundle:Package')->find(1);
        $this->assertEquals(1, $package->getId());
        $this->assertEquals('Import', $package->getName());

        $catalogue = self::$em->getRepository('SuluTranslateBundle:Catalogue')->find(2);
        $this->assertEquals(2, $catalogue->getId());
        $this->assertEquals('en', $catalogue->getLocale());

        $codes = self::$em->getRepository('SuluTranslateBundle:Code')->findBy(
            array(
                'package' => 1
            )
        );
        $this->assertEquals(4, count($codes));
        $this->assertEquals(1, $codes[0]->getId());
        $this->assertEquals('sulu.great', $codes[0]->getCode());
        $this->assertEquals(true, $codes[0]->getBackend());
        $this->assertEquals(true, $codes[0]->getFrontend());
        $this->assertEquals(null, $codes[0]->getLength());
        $this->assertEquals(2, $codes[1]->getId());
        $this->assertEquals('sulu.open', $codes[1]->getCode());
        $this->assertEquals(true, $codes[1]->getBackend());
        $this->assertEquals(true, $codes[1]->getFrontend());
        $this->assertEquals(null, $codes[1]->getLength());

        $translations = self::$em->getRepository('SuluTranslateBundle:Translation')->findBy(
            array(
                'catalogue' => 2
            )
        );
        $this->assertEquals('Sulu ist toll!', $translations[0]->getValue());
        $this->assertEquals('Sulu ist OpenSource!', $translations[1]->getValue());
    }

    /**
     * @expectedException \Symfony\Component\Translation\Exception\NotFoundResourceException
     */
    public function testXliffNoFile()
    {
        $this->import->setFile('somewhere/this-file-does-not-exist.xliff');
        $this->import->setName('sulu');
        $this->import->setLocale('en');
        $this->import->executeFromFile();
    }

    /**
     * @expectedException \Symfony\Component\Translation\Exception\InvalidResourceException
     */
    public function testXliffFailFile()
    {
        $this->import->setFile(self::$fixturePath . '/samples/import_fail.xlf');
        $this->import->setName('sulu');
        $this->import->setLocale('en');
        $this->import->executeFromFile();
    }

    /**
     * @expectedException \Sulu\Bundle\TranslateBundle\Translate\Exception\PackageNotFoundException
     */
    public function testPackageNotFound()
    {
        $this->import->setFile(self::$fixturePath . '/samples/import.xliff');
        $this->import->setPackageId(10);
        $this->import->setName('Fail');
        $this->import->executeFromFile();
    }

    public function testImportFromBundles()
    {
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('en');
        $this->import->setFrontendDomain('frontend');
        $this->import->setBackendDomain('backend');
        $this->import->setPath('Resources/translations/sulu');
        $this->import->executeFromBundles();

        // are all packages here ?
        $packages = self::$em->getRepository('SuluTranslateBundle:Package')->findAll();
        $this->assertEquals(2, count($packages));
        $this->assertEquals('SuluSampleBundle', $packages[0]->getName());
        $this->assertEquals('SuluHikaruBundle', $packages[1]->getName());

        // are all catalogues here?
        $catalogues = self::$em->getRepository('SuluTranslateBundle:Catalogue')->findAll();
        $this->assertEquals(2, count($catalogues));
        $this->assertEquals('en', $catalogues[0]->getLocale());
        $this->assertEquals(1, $catalogues[0]->getPackage()->getId());
        $this->assertEquals('en', $catalogues[1]->getLocale());
        $this->assertEquals(2, $catalogues[1]->getPackage()->getId());

        // are all codes here ?
        $codes = self::$em->getRepository('SuluTranslateBundle:Code')->findAll();
        $this->assertEquals(8, count($codes));
        // 1. Code
        $this->assertEquals('sample.backend.test1', $codes[0]->getCode());
        $this->assertEquals(1, $codes[0]->getPackage()->getId());
        $this->assertTrue($codes[0]->getBackend());
        $this->assertFalse($codes[0]->getFrontend());
        // 2. Code
        $this->assertEquals('sample.backend.test2', $codes[1]->getCode());
        $this->assertEquals(1, $codes[1]->getPackage()->getId());
        $this->assertTrue($codes[1]->getBackend());
        $this->assertFalse($codes[1]->getFrontend());
        // 3. Code
        $this->assertEquals('sample.frontend.test1', $codes[2]->getCode());
        $this->assertEquals(1, $codes[2]->getPackage()->getId());
        $this->assertFalse($codes[2]->getBackend());
        $this->assertTrue($codes[2]->getFrontend());
        // 4. Code
        $this->assertEquals('sample.frontend.test2', $codes[3]->getCode());
        $this->assertEquals(1, $codes[3]->getPackage()->getId());
        $this->assertFalse($codes[3]->getBackend());
        $this->assertTrue($codes[3]->getFrontend());
        // 5. Code
        $this->assertEquals('hikaru.backend.test1', $codes[4]->getCode());
        $this->assertEquals(2, $codes[4]->getPackage()->getId());
        $this->assertTrue($codes[4]->getBackend());
        $this->assertFalse($codes[4]->getFrontend());
        // 6. Code
        $this->assertEquals('hikaru.backend.test2', $codes[5]->getCode());
        $this->assertEquals(2, $codes[5]->getPackage()->getId());
        $this->assertTrue($codes[5]->getBackend());
        $this->assertFalse($codes[5]->getFrontend());
        // 7. Code
        $this->assertEquals('hikaru.frontend.test1', $codes[6]->getCode());
        $this->assertEquals(2, $codes[6]->getPackage()->getId());
        $this->assertFalse($codes[6]->getBackend());
        $this->assertTrue($codes[6]->getFrontend());
        // 8. Code
        $this->assertEquals('hikaru.frontend.test2', $codes[7]->getCode());
        $this->assertEquals(2, $codes[7]->getPackage()->getId());
        $this->assertFalse($codes[7]->getBackend());
        $this->assertTrue($codes[7]->getFrontend());

        // are all translations here ?
        $translations = self::$em->getRepository('SuluTranslateBundle:Translation')->findAll();
        $this->assertEquals(8, count($translations));
        // 1. Translation
        $this->assertEquals('Sample Backend import 1', $translations[0]->getValue());
        $this->assertEquals('sample.backend.test1', $translations[0]->getCode()->getCode());
        $this->assertEquals(1, $translations[0]->getCatalogue()->getId());
        // 2. Translation
        $this->assertEquals('Sample Backend import 2', $translations[1]->getValue());
        $this->assertEquals('sample.backend.test2', $translations[1]->getCode()->getCode());
        $this->assertEquals(1, $translations[1]->getCatalogue()->getId());
        // 3. Translation
        $this->assertEquals('Sample Frontend import 1', $translations[2]->getValue());
        $this->assertEquals('sample.frontend.test1', $translations[2]->getCode()->getCode());
        $this->assertEquals(1, $translations[2]->getCatalogue()->getId());
        // 4. Translation
        $this->assertEquals('Sample Frontend import 2', $translations[3]->getValue());
        $this->assertEquals('sample.frontend.test2', $translations[3]->getCode()->getCode());
        $this->assertEquals(1, $translations[3]->getCatalogue()->getId());
        // 5. Translation
        $this->assertEquals('Hikaru Backend import 1', $translations[4]->getValue());
        $this->assertEquals('hikaru.backend.test1', $translations[4]->getCode()->getCode());
        $this->assertEquals(2, $translations[4]->getCatalogue()->getId());
        // 6. Translation
        $this->assertEquals('Hikaru Backend import 2', $translations[5]->getValue());
        $this->assertEquals('hikaru.backend.test2', $translations[5]->getCode()->getCode());
        $this->assertEquals(2, $translations[5]->getCatalogue()->getId());
        // 7. Translation
        $this->assertEquals('Hikaru Frontend import 1', $translations[6]->getValue());
        $this->assertEquals('hikaru.frontend.test1', $translations[6]->getCode()->getCode());
        $this->assertEquals(2, $translations[6]->getCatalogue()->getId());
        // 8. Translation
        $this->assertEquals('Hikaru Frontend import 2', $translations[7]->getValue());
        $this->assertEquals('hikaru.frontend.test2', $translations[7]->getCode()->getCode());
        $this->assertEquals(2, $translations[7]->getCatalogue()->getId());
    }

    public function testResetPackages()
    {
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('en');
        $this->import->setFrontendDomain('frontend');
        $this->import->setBackendDomain('backend');
        $this->import->setPath('Resources/translations/sulu');
        $this->import->executeFromBundles();

        $this->import->resetPackages();

        $packages = self::$em->getRepository('SuluTranslateBundle:Package')->findAll();
        $this->assertEquals(0, count($packages));

        $catalogues = self::$em->getRepository('SuluTranslateBundle:Catalogue')->findAll();
        $this->assertEquals(0, count($catalogues));

        $codes = self::$em->getRepository('SuluTranslateBundle:Code')->findAll();
        $this->assertEquals(0, count($codes));

        $translations = self::$em->getRepository('SuluTranslateBundle:Translation')->findAll();
        $this->assertEquals(0, count($translations));
    }
}

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
use Sulu\Bundle\TranslateBundle\Translate\Import;

class ImportTest extends SuluTestCase
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
        self::$fixturePath = __DIR__ . '/../../DataFixtures/Files';
    }

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();

        $hikaruBundle = $this->getMock('Symfony\Component\HttpKernel\Bundle', ['getName', 'getPath']);
        $hikaruBundle->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('SuluHikaruBundle'));
        $hikaruBundle->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue(self::$fixturePath . '/bundles/SuluHikaruBundle'));

        $sampleBundle = $this->getMock('Symfony\Component\HttpKernel\Bundle', ['getName', 'getPath']);
        $sampleBundle->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('SuluSampleBundle'));
        $sampleBundle->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue(self::$fixturePath . '/bundles/SuluSampleBundle'));

        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $kernel->expects($this->any())
            ->method('getBundles')
            ->will($this->returnValue([$sampleBundle, $hikaruBundle]));

        $this->import = new Import($this->em, $kernel);
    }

    public function testXliffFromFile()
    {
        $this->import->setFile(self::$fixturePath . '/samples/import.xlf');
        $this->import->setName('Import');
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('de');
        $this->import->executeFromFile(true, true);

        $package = $this->em->getRepository('SuluTranslateBundle:Package')->findOneByName('Import');
        $this->assertNotNull($package);

        $catalogue = $this->em->getRepository('SuluTranslateBundle:Catalogue')->findOneByLocale('de');
        $this->assertNotNull($catalogue);

        $codes = $this->em->getRepository('SuluTranslateBundle:Code')->findAll();
        $this->assertEquals('sulu.great', $codes[0]->getCode());
        $this->assertEquals(true, $codes[0]->getBackend());
        $this->assertEquals(true, $codes[0]->getFrontend());
        $this->assertEquals(null, $codes[0]->getLength());
        $this->assertEquals('sulu.open', $codes[1]->getCode());
        $this->assertEquals(true, $codes[1]->getBackend());
        $this->assertEquals(true, $codes[1]->getFrontend());
        $this->assertEquals(null, $codes[1]->getLength());

        $translations = $this->em->getRepository('SuluTranslateBundle:Translation')->findAll();
        $this->assertEquals('Sulu ist toll!', $translations[0]->getValue());
        $this->assertEquals('Sulu ist OpenSource!', $translations[1]->getValue());

        // http://stackoverflow.com/questions/18268464/doctrine-lazy-loading-in-symfony-test-environment
        $this->em->clear();

        // test new import
        $this->import->setFile(self::$fixturePath . '/samples/import_better.xlf');
        $this->import->setName('Import Update');
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('de');
        $this->import->setPackageId($package->getId());
        $this->import->executeFromFile(true, true);

        $package = $this->em->getRepository('SuluTranslateBundle:Package')->find($package->getId());
        $this->assertNotNull($package);
        $this->assertEquals('Import Update', $package->getName());

        $catalogues = $package->getCatalogues();
        $catalogues = $catalogues->toArray();
        $catalogue = reset($catalogues);
        $this->assertNotNull($catalogue);
        $this->assertEquals('de', $catalogue->getLocale());

        $codes = $this->em->getRepository('SuluTranslateBundle:Code')->findAll();
        $this->assertEquals('sulu.great', $codes[0]->getCode());
        $this->assertEquals(true, $codes[0]->getBackend());
        $this->assertEquals(true, $codes[0]->getFrontend());
        $this->assertEquals(null, $codes[0]->getLength());
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

        $translations = $this->em->getRepository('SuluTranslateBundle:Translation')->findAll();

        $values = [
            'Sulu ist wirklich toll!',
            'Sulu ist OpenSource!',
            'Sulu ist sehr toll!',
            'Sulu ist sogar OpenSource!',
        ];
        $this->assertTrue(in_array($translations[0]->getValue(), $values));
        $values = array_diff($values, [$translations[0]->getValue()]);
        $this->assertTrue(in_array($translations[1]->getValue(), $values));
        $values = array_diff($values, [$translations[1]->getValue()]);
        $this->assertTrue(in_array($translations[2]->getValue(), $values));
        $values = array_diff($values, [$translations[2]->getValue()]);
        $this->assertTrue(in_array($translations[3]->getValue(), $values));
        $values = array_diff($values, [$translations[3]->getValue()]);
        $this->assertEquals(0, count($values));

        // http://stackoverflow.com/questions/18268464/doctrine-lazy-loading-in-symfony-test-environment
        $this->em->clear();

        // test new import with new language code
        $this->import->setFile(self::$fixturePath . '/samples/import.xlf');
        $this->import->setName('Import');
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('en');
        $this->import->setPackageId($package->getId());
        $this->import->executeFromFile(true, true);

        $package = $this->em->getRepository('SuluTranslateBundle:Package')->find($package->getId());
        $this->assertEquals('Import', $package->getName());

        $catalogue = $this->em->getRepository('SuluTranslateBundle:Catalogue')->findOneBy([
            'locale' => 'en',
            'package' => $package->getId(),
        ]);
        $this->assertNotNull($catalogue);
        $this->assertEquals('en', $catalogue->getLocale());

        $codes = $this->em->getRepository('SuluTranslateBundle:Code')->findBy(
            [
                'package' => $package->getId(),
            ]
        );
        $this->assertEquals(4, count($codes));
        $this->assertEquals('sulu.great', $codes[0]->getCode());
        $this->assertEquals(true, $codes[0]->getBackend());
        $this->assertEquals(true, $codes[0]->getFrontend());
        $this->assertEquals(null, $codes[0]->getLength());
        $this->assertEquals('sulu.open', $codes[1]->getCode());
        $this->assertEquals(true, $codes[1]->getBackend());
        $this->assertEquals(true, $codes[1]->getFrontend());
        $this->assertEquals(null, $codes[1]->getLength());

        $translations = $this->em->getRepository('SuluTranslateBundle:Translation')->findBy(
            [
                'catalogue' => $catalogue->getId(),
            ]
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
        $packages = $this->em->getRepository('SuluTranslateBundle:Package')->findAll();
        $this->assertEquals(2, count($packages));
        $this->assertEquals('SuluSampleBundle', $packages[0]->getName());
        $this->assertEquals('SuluHikaruBundle', $packages[1]->getName());

        // are all catalogues here?
        $catalogues = $this->em->getRepository('SuluTranslateBundle:Catalogue')->findAll();
        $this->assertEquals(2, count($catalogues));
        $this->assertEquals('en', $catalogues[0]->getLocale());
        $this->assertEquals('en', $catalogues[1]->getLocale());

        // are all codes here ?
        $codes = $this->em->getRepository('SuluTranslateBundle:Code')->findAll();
        $this->assertEquals(8, count($codes));
        // 1. Code
        $this->assertEquals('sample.backend.test1', $codes[0]->getCode());
        $this->assertTrue($codes[0]->getBackend());
        $this->assertFalse($codes[0]->getFrontend());
        // 2. Code
        $this->assertEquals('sample.backend.test2', $codes[1]->getCode());
        $this->assertTrue($codes[1]->getBackend());
        $this->assertFalse($codes[1]->getFrontend());
        // 3. Code
        $this->assertEquals('sample.frontend.test1', $codes[2]->getCode());
        $this->assertFalse($codes[2]->getBackend());
        $this->assertTrue($codes[2]->getFrontend());
        // 4. Code
        $this->assertEquals('sample.frontend.test2', $codes[3]->getCode());
        $this->assertFalse($codes[3]->getBackend());
        $this->assertTrue($codes[3]->getFrontend());
        // 5. Code
        $this->assertEquals('hikaru.backend.test1', $codes[4]->getCode());
        $this->assertTrue($codes[4]->getBackend());
        $this->assertFalse($codes[4]->getFrontend());
        // 6. Code
        $this->assertEquals('hikaru.backend.test2', $codes[5]->getCode());
        $this->assertTrue($codes[5]->getBackend());
        $this->assertFalse($codes[5]->getFrontend());
        // 7. Code
        $this->assertEquals('hikaru.frontend.test1', $codes[6]->getCode());
        $this->assertFalse($codes[6]->getBackend());
        $this->assertTrue($codes[6]->getFrontend());
        // 8. Code
        $this->assertEquals('hikaru.frontend.test2', $codes[7]->getCode());
        $this->assertFalse($codes[7]->getBackend());
        $this->assertTrue($codes[7]->getFrontend());

        // are all translations here ?
        $translations = $this->em->getRepository('SuluTranslateBundle:Translation')->findAll();
        $this->assertEquals(8, count($translations));
        // 1. Translation
        $this->assertEquals('Sample Backend import 1', $translations[0]->getValue());
        $this->assertEquals('sample.backend.test1', $translations[0]->getCode()->getCode());
        // 2. Translation
        $this->assertEquals('Sample Backend import 2', $translations[1]->getValue());
        $this->assertEquals('sample.backend.test2', $translations[1]->getCode()->getCode());
        // 3. Translation
        $this->assertEquals('Sample Frontend import 1', $translations[2]->getValue());
        $this->assertEquals('sample.frontend.test1', $translations[2]->getCode()->getCode());
        // 4. Translation
        $this->assertEquals('Sample Frontend import 2', $translations[3]->getValue());
        $this->assertEquals('sample.frontend.test2', $translations[3]->getCode()->getCode());
        // 5. Translation
        $this->assertEquals('Hikaru Backend import 1', $translations[4]->getValue());
        $this->assertEquals('hikaru.backend.test1', $translations[4]->getCode()->getCode());
        // 6. Translation
        $this->assertEquals('Hikaru Backend import 2', $translations[5]->getValue());
        $this->assertEquals('hikaru.backend.test2', $translations[5]->getCode()->getCode());
        // 7. Translation
        $this->assertEquals('Hikaru Frontend import 1', $translations[6]->getValue());
        $this->assertEquals('hikaru.frontend.test1', $translations[6]->getCode()->getCode());
        // 8. Translation
        $this->assertEquals('Hikaru Frontend import 2', $translations[7]->getValue());
        $this->assertEquals('hikaru.frontend.test2', $translations[7]->getCode()->getCode());
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

        $packages = $this->em->getRepository('SuluTranslateBundle:Package')->findAll();
        $this->assertEquals(0, count($packages));

        $catalogues = $this->em->getRepository('SuluTranslateBundle:Catalogue')->findAll();
        $this->assertEquals(0, count($catalogues));

        $codes = $this->em->getRepository('SuluTranslateBundle:Code')->findAll();
        $this->assertEquals(0, count($codes));

        $translations = $this->em->getRepository('SuluTranslateBundle:Translation')->findAll();
        $this->assertEquals(0, count($translations));
    }
}

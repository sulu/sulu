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
        self::$fixturePath = __DIR__ . '/../../Resources/DataFixtures/Files';
    }

    public function setUp()
    {
        $this->setUpSchema();

        $this->import = new Import(self::$em);
    }

    public function tearDown()
    {
        parent::tearDown();
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

    public function testXliff()
    {
        $this->import->setFile(self::$fixturePath . '/import.xlf');
        $this->import->setName('Import');
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('de');
        $this->import->execute();

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

        // FIXME needed because of strange doctrine behaviour
        // http://stackoverflow.com/questions/18268464/doctrine-lazy-loading-in-symfony-test-environment
        self::$em->clear();

        // test new import
        $this->import->setFile(self::$fixturePath . '/import_better.xlf');
        $this->import->setName('Import Update');
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('de');
        $this->import->setPackageId(1);
        $this->import->execute();

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

        // FIXME needed because of strange doctrine behaviour
        // http://stackoverflow.com/questions/18268464/doctrine-lazy-loading-in-symfony-test-environment
        self::$em->clear();

        // test new import with new language code
        $this->import->setFile(self::$fixturePath . '/import.xlf');
        $this->import->setName('Import');
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('en');
        $this->import->setPackageId(1);
        $this->import->execute();

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
        $this->import->setFile('this-file-does-not-exist.xliff');
        $this->import->execute();
    }

    /**
     * @expectedException \Symfony\Component\Translation\Exception\InvalidResourceException
     */
    public function testXliffFailFile()
    {
        $this->import->setFile(self::$fixturePath . '/import_fail.xlf');
        $this->import->execute();
    }

    /**
     * @expectedException \Sulu\Bundle\TranslateBundle\Translate\Exception\PackageNotFoundException
     */
    public function testPackageNotFound()
    {
        $this->import->setFile(self::$fixturePath . '/import.xliff');
        $this->import->setPackageId(10);
        $this->import->setName('Fail');
        $this->import->execute();
    }
}

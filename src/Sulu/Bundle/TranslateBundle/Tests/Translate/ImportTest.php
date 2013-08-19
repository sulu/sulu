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
use Sulu\Bundle\TranslateBundle\Translate\Import;

class ImportTest extends DatabaseTestCase
{
    /**
     * @var Import
     */
    protected $import;

    /**
     * @var array
     */
    protected static $entities;

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
        $this->import->setFile(__DIR__ . '/../Fixtures/import.xlf');
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
    }

    /**
     * @expectedException Symfony\Component\Translation\Exception\NotFoundResourceException
     */
    public function testXliffNoFile()
    {
        $this->import->setFile('this-file-does-not-exist.xliff');
        $this->import->execute();
    }

    /**
     * @expectedException Symfony\Component\Translation\Exception\InvalidResourceException
     */
    public function testXliffFailFile()
    {
        $this->import->setFile(__DIR__ . '/../Fixtures/import_fail.xlf');
        $this->import->execute();
    }
}

<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Tests\Translate;

use Sulu\Bundle\CoreBundle\Tests\DatabaseTestCase;
use Sulu\Bundle\TranslateBundle\Translate\Import;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\SchemaTool;

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

    /**
     * @var SchemaTool
     */
    protected static $tool;

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
        self::$tool = new SchemaTool(self::$em);

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
        $this->import->setFile(__DIR__ . '/import.xliff');
        $this->import->setName('Import');
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('de');
        $this->import->execute();

        $package = self::$em->getRepository('SuluTranslateBundle:Package')->find(1);

        $this->assertEquals('Import', $package->getName());
    }
}

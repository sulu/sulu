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

class ImportTest extends DatabaseTestCase {
    /**
     * @var Import
     */
    protected $import;

    public function setUp() {
        $this->import = new Import(self::$em);
    }

    public function tearDown() {
        parent::tearDown();
        self::$em->getConnection()->query(
            "START TRANSACTION; SET FOREIGN_KEY_CHECKS=0;
                        TRUNCATE TABLE tr_packages;
                        TRUNCATE TABLE tr_catalogues;
                        TRUNCATE TABLE tr_codes;
                        TRUNCATE TABLE tr_translations;
                        SET FOREIGN_KEY_CHECKS=1;COMMIT;
                    "
        );
    }

    public function testXliff() {
        $this->import->setFile(__DIR__ . '/import.xliff');
        $this->import->setName('Import');
        $this->import->setFormat(Import::XLIFF);
        $this->import->setLocale('de');
        $this->import->execute();

        $package = self::$em->getRepository('SuluTranslateBundle:Package')->find(1);
        $catalogue = $package->getCatalogues()[0];

        $this->assertEquals('Import', $package->getName());
        $this->assertEquals('de', $catalogue->getLocale());
    }
}

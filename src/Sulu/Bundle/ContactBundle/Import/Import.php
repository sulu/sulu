<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Import;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * Configures and starts an import from an translation catalogue
 *
 * @package Sulu\Bundle\TranslateBundle\Translate
 */
class Import
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var $contactFile
     */
    private $contactFile;

    /**
     * @var $headerData
     */
    private $headerData;

    /**
     * @var array
     */
    private $mappings = array(
        'email' => 'E_Mail',
        'account_name' => 'Firma',
        'account_type' => 'Klasse',
        'account_division' => 'Name2',
        'url' => 'Internet',
        'country' => 'LKZ',
        'plz' => 'PLZ',
        'street' => 'Strasse',
        'city' => 'Ort',
        'phone' => 'Telefon',
        'phone2' => 'Tel2_Ort',
        'fax' => 'Fax',
        'parent' => 'gehört_zu',
        'uid' => 'UID_Nr',
    );

    /**
     * @var array
     */
    private $countryMappings = array(
        'de' => 'D',
        'at' => 'A',
        'ch' => 'CH'
    );


    function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Executes the import
     */
    public function execute()
    {
        try {
            if (!$this->contactFile) {
                throw new InvalidArgumentException('no contact file specified for import');
            }

            // clear database
//            $this->clearDatabase();

            //
            $this->processContactCsv($this->contactFile);
            $this->em->flush();
        } catch (\Exception $e) {
            print($e->getMessage());
            exit();
        }
    }

    /**
     * Loads the CSV Files and the Entities for the import
     */
    public function processContactCsv($filename)
    {
        $row = 0;

        // load all Files
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                // for first row, save headers
                if ($row === 0) {
                    $this->setHeaderData($data);
                } else {
                    // get associativeData
                    $associativeData = $this->mapRowToAssociativeArray($data);
                    $this->createAccount($associativeData);
                }

                $row++;

                //TODO: remove
                if ($row>1) { break; }
            }
            fclose($handle);
        }

    }


    private function createAccount($data) {

        // check if account allready exists
        $account = new Account();
        $account->setName($data['company_name']);
        $account->setChanged(new \DateTime());
        $account->setCreated(new \DateTime());

        if ($data['email']!=='') {
            $email = new Email();
            $email->setEmail($data['email']);
            $this->em->persist($email);
            $account->addEmail($email);
        }

        if ($data['account_division'] !== '') {

        }

        // set address
        $address = new Address();
        if ($data['street'] !== '') {
            $address->setStreet($data['street']);
        }
        if ($data['country'] !== '') {
            $address->setStreet($this->mapCountryCode($data['country']));
        }
        if ($data['account_division'] !== '') {
            $address->setStreet($data['street']);
        }
        if ($data['account_division'] !== '') {
            $address->setStreet($data['street']);
        }

        $account->addAddresse($address);



        $this->em->persist($account);

    }

    private function clearDatabase() {
        $this->clearTable('SuluContactBundle:Account');
    }

    private function clearTable($tableName) {
        $connection = $this->em->getConnection();
        $platform   = $connection->getDatabasePlatform();

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
        $truncateSql = $platform->getTruncateTableSQL($tableName);
        $connection->executeUpdate($truncateSql);
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
    }

    private function mapRowToAssociativeArray($data) {
        foreach($data as $index => $value) {
            if ($mappingIndex = array_search($this->headerData[$index], $this->mappings)) {
                $associativeData[$mappingIndex] = $value;
            } else {
                $associativeData[($this->headerData[$index])] = $value;
            }
        }
        return $associativeData;
    }

    private function mapCountryCode($countryCode) {
        if ($mappingIndex = array_search($countryCode, $this->countryMappings)) {
            return $mappingIndex;
        } else {
            return mb_strtolower($countryCode);
        }
    }


    private function setHeaderData($data) {
        $this->headerData = $data;
    }


    /**
     * @param mixed $contactFile
     */
    public function setContactFile($contactFile)
    {
        $this->contactFile = $contactFile;
    }

    /**
     * @return mixed
     */
    public function getContactFile()
    {
        return $this->contactFile;
    }

    /**
     * @param array $mappings
     */
    public function setMappings($mappings)
    {
        $this->mappings = $mappings;
    }

    /**
     * @return array
     */
    public function getMappings()
    {
        return $this->mappings;
    }



}

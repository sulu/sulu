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
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Component\Rest\Exception\EntityNotFoundException;

/**
 * configures and executes an import for contact and account data from a CSV file
 * @package Sulu\Bundle\ContactBundle\Import
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
     * @var $accountFile
     */
    private $accountFile;

    /**
     * @var $headerData
     */
    private $headerData;

    /**
     * @var $configDefaults
     */
    private $configDefaults;

    /**
     * limit of rows to import
     * @var
     */
    private $limit;

    /**
     * @var array $defaultTypes
     */
    private $defaultTypes = array();

    protected $options = array(
        'streetNumberSplit' => true
    );

    // TODO: split mappings for accounts and contacts
    /**
     * @var array
     */
    protected $columnMappings = array(
//        'email1' => 'E_Mail',
//        'account_name' => 'Firma',
//        'account_type' => 'Klasse',
//        'account_division' => 'Name2',
//        'account_disabled' => 'gesperrt',
//        'account_uid' => 'UID_Nr',
//        'url1' => 'Internet',
//        'country' => 'LKZ',
//        'plz' => 'PLZ',
//        'street' => 'Strasse',
//        'city' => 'Ort',
//        'phone1' => 'Telefon',
//        'phone2' => 'Tel2_Ort',
//        'phone_isdn' => 'ISDN',
//        'phone_mobile' => 'Mobil',
//        'phone_emergency' => 'Notruf',
//        'fax' => 'Fax',
//        'uid' => 'UID_Nr',
//        'note1' => 'Bemerkung',
//        'contact_parent' => 'gehört_zu',
//        'contact_title' => 'Titel',
//        'contact_position' => 'Hauptfunktion',
//        'contact_firstname' => 'Vorname',
//        'contact_lastname' => 'Vor_Nachname',
    );

    protected $idMappings = array(
        'account_id' => 'MatchCode'
    );

    /**
     * @var array
     */
    protected $countryMappings = array(
        'DE' => 'D',
        'AT' => 'A',
        'CH' => 'CH'
    );

    /**
     * @var array
     */
    protected $accountTypeMappings = array(
        Account::TYPE_BASIC => '',
        Account::TYPE_LEAD => '',
        Account::TYPE_CUSTOMER => '',
        Account::TYPE_SUPPLIER => 'Lieferant',
    );

    /**
     * used for saving accounts
     * @var array
     */
    private $accounts = array();

    /**
     * used for saving accounts
     * @var array
     */
    private $associativeAccounts = array();

    function __construct(EntityManager $em, $configDefaults)
    {
        $this->em = $em;
        $this->configDefaults = $configDefaults;
    }

    /**
     * Executes the import
     */
    public function execute()
    {
        try {
            if (!$this->accountFile) {
                throw new InvalidArgumentException('no account file specified for import');
            }

            // TODO clear database: $this->clearDatabase();

            // set default types
            $this->defaultTypes = $this->getDefaults();

            // process account file if exists
            if ($this->accountFile) {
                $this->processAccountFile($this->accountFile);
            }

            // process contact file if exists
            if ($this->contactFile) {
                $this->processContactFile($this->contactFile);
            }

        } catch (\Exception $e) {
            print($e->getMessage());
        }
    }

    /**
     * processes the account file
     * @param string $filename path to fil file
     */
    public function processAccountFile($filename)
    {
        $createParentRelations = function ($data, $row) {
            $this->createAccountParentRelation($data, $row);
        };

        // create accounts
        $this->processCsvLoop(
            $filename,
            function ($data, $row) {
                $this->createAccount($data, $row);
            }
        );

        // check for parents
        $this->processCsvLoop($filename, $createParentRelations);
    }

    /**
     * processes the contact file
     * @param string $filename path to file
     */
    public function processContactFile($filename)
    {
        $createContact = function ($data, $row) {
            $this->createContact($data, $row);
        };

        // create accounts
        $this->processCsvLoop($filename, $createContact);
    }


    /**
     * Loads the CSV Files and the Entities for the import
     * @param string $filename path to file
     * @param callable $function will be called for each row in file
     */
    protected function processCsvLoop($filename, $function)
    {
        $row = 0;

        // load all Files
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                try {
                    // for first row, save headers
                    if ($row === 0) {
                        $this->setHeaderData($data);
                    } else {
                        // get associativeData
                        $associativeData = $this->mapRowToAssociativeArray($data);

                        $function($associativeData, $row);

                        // now save to database
                        $this->em->flush();
                    }

                    // check limit and break loop if necessary
                    $limit = $this->getLimit();
                    if (!is_null($limit) && $row >= $limit) {
                        break;
                    }

                    $row++;
                } catch (\Exception $e) {
                    print("error while processing data row $row \n");
                }
            }
            fclose($handle);
        }
    }

    /**
     * creates an account for given row data
     */
    private function createAccount($data, $row)
    {
        // check if account already exists
        $account = new Account();
        $this->accounts[] = $account;
        $this->associativeAccounts[$data[$this->idMappings['account_id']]] = $account;
        $account->setChanged(new \DateTime());
        $account->setCreated(new \DateTime());

        if ($this->checkData('account_name', $data)) {
            $account->setName($data['account_name']);
        } else {
            // TODO: catch this exception
            //throw new \Exception('Account name not set at row ' . $row);
            return;
        }

        if ($this->checkData('account_division', $data)) {
            $account->setDivision($data['account_division']);
        }
        if ($this->checkData('account_disabled', $data)) {
            $account->setDisabled($data['account_disabled']);
        }
        if ($this->checkData('account_uid', $data)) {
            $account->setUid($data['account_uid']);
        }
        if ($this->checkData('account_type', $data)) {
            $account->setType($this->mapAccountType($data['account_type']));
        }

        // set address
        $address = new Address();
        $addAddress = false;

        if ($this->checkData('street', $data)) {
            $street = $data['street'];

            // separate street and number
            if ($this->options['streetNumberSplit']) {
                preg_match('/([^\d]+)\s?(.+)/i', $street, $result);

                $street = trim($result[1]);
                $number = trim($result[2]);
            }
            $address->setStreet($street);
            $addAddress = true;
        }
        if (isset($number) || $this->checkData('number', $data)) {
            $number = isset($number) ? $number : $data['number'];
            $address->setNumber($number);
        }
        if ($this->checkData('plz', $data)) {
            $address->setZip($data['plz']);
            $addAddress = $addAddress && true;
        }
        if ($this->checkData('city', $data)) {
            $address->setCity($data['city']);
            $addAddress = $addAddress && true;
        } else {
            $addAddress = $addAddress && false;
        }
        if ($this->checkData('country', $data)) {
            $country = $this->em->getRepository('SuluContactBundle:Country')->findOneByCode(
                $this->mapCountryCode($data['country'])
            );

            if (!$country) {
                throw new EntityNotFoundException('Country', $data['country']);
            }

            $address->setCountry($country);
            $addAddress = $addAddress && true;
        } else {
            $addAddress = $addAddress && false;
        }

        // only add address if part of it is defined
        if ($addAddress) {
            $address->setAddressType($this->defaultTypes['addressType']);
            $this->em->persist($address);
            $account->addAddresse($address);
        }

        // add emails
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('email' . $i, $data)) {
                $email = new Email();
                $email->setEmail($data['email' . $i]);
                $email->setEmailType($this->defaultTypes['emailType']);
                $this->em->persist($email);
                $account->addEmail($email);
            } else {
                break;
            }
        }

        // add phones
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('phone' . $i, $data)) {
                $phone = new Phone();
                $phone->setPhone($data['phone' . $i]);
                $phone->setPhoneType($this->defaultTypes['phoneType']);
                $this->em->persist($phone);
                $account->addPhone($phone);
            } else {
                break;
            }
        }

        // phone with type isdn
        if ($this->checkData('phone_isdn', $data)) {
            $phone = new Phone();
            $phone->setPhone($data['phone_isdn']);
            $phone->setPhoneType($this->defaultTypes['phoneTypeIsdn']);
            $this->em->persist($phone);
            $account->addPhone($phone);
        }

        // add faxes
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('fax' . $i, $data)) {
                $fax = new Fax();
                $fax->setFax($data['fax' . $i]);
                $fax->setFaxType($this->defaultTypes['faxType']);
                $this->em->persist($fax);
                $account->addFax($fax);
            } else {
                break;
            }
        }

        // add urls
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('url' . $i, $data)) {
                $url = new Url();
                $url->setUrl($data['url' . $i]);
                $url->setUrlType($this->defaultTypes['urlType']);
                $this->em->persist($url);
                $account->addUrl($url);
            } else {
                break;
            }
        }

        // add notes
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('note' . $i, $data)) {
                $note = new Note();
                $note->setValue($data['note' . $i]);
                $this->em->persist($note);
                $account->addNote($note);
            } else {
                break;
            }
        }

        $this->em->persist($account);
    }

    /**
     * creates an contact for given row data
     */
    private function createContact($data, $row)
    {
        $contact = new Contact();
        // $contact->addEmail();

        if ($this->checkData('contact_firstname', $data) && $this->checkData('contact_lastname', $data)) {
            // TODO: catch this exception
            //   throw new \Exception('contact lastname not set at row ' . $row);
        }

        if ($this->checkData('contact_firstname', $data)) {
            $contact->setFirstName($data['contact_firstname']);
        } else {
            // TODO: dont accept this
            $contact->setFirstName('');
        }
        if ($this->checkData('contact_lastname', $data)) {
            $contact->setLastName($data['contact_lastname']);
        } else {
            // TODO: dont accept this
            $contact->setLastName('');
        }
        if ($this->checkData('contact_title', $data)) {
            $contact->setPosition($data['contact_title']);
        }

        if ($this->checkData('contact_position', $data)) {
            $contact->setPosition($data['contact_position']);
        }

        $contact->setChanged(new \DateTime());
        $contact->setCreated(new \DateTime());

        // check company
        if ($this->checkData('contact_parent', $data)) {
            $account = $this->getAccountByKey($data['contact_parent']);

            if (!$account) {
                // throw new \Exception('could not find '.$data['contact_parent'].' in accounts');
            } else {
                $contact->setAccount($account);
            }
        }

        // add emails
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('email' . $i, $data)) {
                $email = new Email();
                $email->setEmail($data['email' . $i]);
                $email->setEmailType($this->defaultTypes['emailType']);
                $this->em->persist($email);
                $contact->addEmail($email);
            } else {
                break;
            }
        }

        // add phones
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('phone' . $i, $data)) {
                $phone = new Phone();
                $phone->setPhone($data['phone' . $i]);
                $phone->setPhoneType($this->defaultTypes['phoneType']);
                $this->em->persist($phone);
                $contact->addPhone($phone);
            } else {
                break;
            }
        }

        // phone with type mobile
        if ($this->checkData('phone_mobile', $data)) {
            $phone = new Phone();
            $phone->setPhone($data['phone_mobile']);
            $phone->setPhoneType($this->defaultTypes['phoneTypeMobile']);
            $this->em->persist($phone);
            $contact->addPhone($phone);
        }

        // add faxes
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('fax' . $i, $data)) {
                $fax = new Fax();
                $fax->setFax($data['fax' . $i]);
                $fax->setFaxType($this->defaultTypes['faxType']);
                $this->em->persist($fax);
                $contact->addFax($fax);
            } else {
                break;
            }
        }

        $this->em->persist($contact);
    }

    /**
     * checks data for validity
     */
    private function checkData($index, $data)
    {
        return array_key_exists($index, $data) && $data[$index] !== '';
    }

    /**
     * creates relation between parent and account
     */
    private function createAccountParentRelation($data, $row)
    {
        // if account has parent
        if (array_key_exists('account_parent', $data) && $data['account_parent'] !== '') {
            // get account
            /** @var Account $account */
            $account = $this->accounts[$row - 1];

            // get parent account
            $parent = $this->getAccountByKey($data['account_parent']);
            $account->setParent($parent);
        }
    }

    /**
     * truncate table for account and contact
     */
    private function clearDatabase()
    {
        $this->clearTable('SuluContactBundle:Account');
        $this->clearTable('SuluContactBundle:Contact');
    }

    /**
     * truncate one single table for given entity name
     * @param string $entityName name of entity
     */
    private function clearTable($entityName)
    {
        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform();

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
        $truncateSql = $platform->getTruncateTableSQL($entityName);
        $connection->executeUpdate($truncateSql);
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * returns an associative array of data mapped by configuration
     * @param array $data data of a single csv row
     * @return array
     */
    private function mapRowToAssociativeArray($data)
    {
        $associativeData = array();
        foreach ($data as $index => $value) {
            // search index in mapping config
            if ($mappingIndex = array_search($this->headerData[$index], $this->columnMappings)) {
                $associativeData[$mappingIndex] = $value;
            } else {
                $associativeData[($this->headerData[$index])] = $value;
            }
        }
        return $associativeData;
    }

    /**
     * TODO
     * @param $countryCode
     * @return mixed|string
     */
    private function mapCountryCode($countryCode)
    {
        if ($mappingIndex = array_search($countryCode, $this->countryMappings)) {
            return $mappingIndex;
        } else {
            return mb_strtolower($countryCode);
        }
    }

    /**
     * TODO
     * @param $typeString
     * @return int|mixed
     */
    private function mapAccountType($typeString)
    {
        if ($mappingIndex = array_search($typeString, $this->accountTypeMappings)) {
            return $mappingIndex;
        } else {
            return Account::TYPE_BASIC;
        }
    }

    /**
     * TODO
     * @param $data
     */
    private function setHeaderData($data)
    {
        $this->headerData = $data;
    }

    /**
     * TODO
     * @param mixed $contactFile
     */
    public function setContactFile($contactFile)
    {
        $this->contactFile = $contactFile;
    }

    /**
     * TODO
     * @return mixed
     */
    public function getContactFile()
    {
        return $this->contactFile;
    }

    /**
     * TODO
     * @param mixed $accountFile
     */
    public function setAccountFile($accountFile)
    {
        $this->accountFile = $accountFile;
    }

    /**
     * TODO
     * @return mixed
     */
    public function getAccountFile()
    {
        return $this->accountFile;
    }

    /**
     * TODO
     * @param mixed $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * TODO
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param array $columnMappings
     */
    public function setColumnMappings($columnMappings)
    {
        $this->columnMappings = $columnMappings;
    }

    /**
     * @return array
     */
    public function getColumnMappings()
    {
        return $this->columnMappings;
    }

    /**
     * TODO
     * @param $key
     * @return null
     */
    public function getAccountByKey($key)
    {
        if (array_key_exists($key, $this->associativeAccounts)) {
            return $this->associativeAccounts[$key];
        }
        return null;
    }

    /**
     * @param array $countryMappings
     */
    public function setCountryMappings($countryMappings)
    {
        $this->countryMappings = $countryMappings;
    }

    /**
     * @return array
     */
    public function getCountryMappings()
    {
        return $this->countryMappings;
    }

    /**
     * @param array $idMappings
     */
    public function setIdMappings($idMappings)
    {
        $this->idMappings = $idMappings;
    }

    /**
     * @return array
     */
    public function getIdMappings()
    {
        return $this->idMappings;
    }

    /**
     * @param array $accountTypeMappings
     */
    public function setAccountTypeMappings($accountTypeMappings)
    {
        $this->accountTypeMappings = $accountTypeMappings;
    }

    /**
     * @return array
     */
    public function getAccountTypeMappings()
    {
        return $this->accountTypeMappings;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }



    /**
     * TODO outsource this into a service! also used in template controller
     * Returns the default values for the dropdowns
     * @return array
     */
    private function getDefaults()
    {
        $config = $this->configDefaults;
        $defaults = array();

        $emailTypeEntity = 'SuluContactBundle:EmailType';
        $defaults['emailType'] = $this->em
            ->getRepository($emailTypeEntity)
            ->find($config['emailType']);

        $phoneTypeEntity = 'SuluContactBundle:PhoneType';
        $defaults['phoneType'] = $this->em
            ->getRepository($phoneTypeEntity)
            ->find($config['phoneType']);

        $defaults['phoneTypeIsdn'] = $this->em
            ->getRepository($phoneTypeEntity)
            ->find($config['phoneTypeIsdn']);

        $defaults['phoneTypeMobile'] = $this->em
            ->getRepository($phoneTypeEntity)
            ->find($config['phoneTypeMobile']);

        $addressTypeEntity = 'SuluContactBundle:AddressType';
        $defaults['addressType'] = $this->em
            ->getRepository($addressTypeEntity)
            ->find($config['addressType']);

        $urlTypeEntity = 'SuluContactBundle:UrlType';
        $defaults['urlType'] = $this->em
            ->getRepository($urlTypeEntity)
            ->find($config['urlType']);

        $faxTypeEntity = 'SuluContactBundle:FaxType';
        $defaults['faxType'] = $this->em
            ->getRepository($faxTypeEntity)
            ->find($config['faxType']);

        $countryEntity = 'SuluContactBundle:Country';
        $defaults['country'] = $this->em
            ->getRepository($countryEntity)
            ->find($config['country']);

        return $defaults;
    }

}

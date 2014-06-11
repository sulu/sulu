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
use Symfony\Component\Translation\Exception\NotFoundResourceException;

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
     * location of contacts import file
     * @var $contactFile
     */
    private $contactFile;

    /**
     * location of accounts import file
     * @var $accountFile
     */
    private $accountFile;

    /**
     * location of the mappings file
     * @var $mappingsFile
     */
    private $mappingsFile;

    /**
     * Default values for different types, as defined in config (emailType, phoneType,..)
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

    /**
     * import options
     * @var array
     * @param {Boolean=true} importIds defines if ids of import file should be imported
     * @param {Boolean=false} streetNumberSplit defines if street is provided as street- number string and must be splitted
     */
    protected $options = array(
        'importIds' => true,
        'streetNumberSplit' => false,
    );

    // TODO: split mappings for accounts and contacts
    /**
     * defines mappings of columns in import file
     * @var array
     *
     * defaults are:
     * 'account_name'
     * 'account_type'
     * 'account_division'
     * 'account_disabled'
     * 'account_uid'
     * 'email1' (1..n)
     * 'url1' (1..n)
     * 'note1' (1..n)
     * 'phone1' (1..n)
     * 'phone_isdn'
     * 'phone_mobile'
     * 'country'
     * 'plz'
     * 'street'
     * 'city'
     * 'fax'
     * 'uid'
     * 'contact_parent'
     * 'contact_title'
     * 'contact_position'
     * 'contact_firstname'
     * 'contact_lastname'
     */
    protected $columnMappings = array();

    /**
     * defines mappings of ids in import file
     * @var array
     */
    protected $idMappings = array(
        'account_id' => 'account_id'
    );

    /**
     * @var array
     */
    protected $countryMappings = array();

    /**
     * defines mappings of accountTypes in import file
     * @var array
     */
    protected $accountTypeMappings = array(
        Account::TYPE_BASIC => '',
        Account::TYPE_LEAD => 'lead',
        Account::TYPE_CUSTOMER => 'customer',
        Account::TYPE_SUPPLIER => 'supplier',
    );

    /**
     * used as temp storage for newly created accounts
     * @var array
     */
    private $accounts = array();

    /**
     * used as temp associative storage for newly created accounts
     * @var array
     */
    private $associativeAccounts = array();

    /**
     * @param EntityManager $em
     * @param $configDefaults
     */
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
            // check if specified files do exist
            if (!$this->accountFile || !file_exists($this->accountFile) ||
                ($this->mappingsFile && !file_exists($this->mappingsFile)) ||
                ($this->contactFile && !file_exists($this->contactFile))
            ) {
                throw new NotFoundResourceException;
            }

            // set default types
            $this->defaultTypes = $this->getDefaults();

            // process mappings file
            if ($this->mappingsFile) {
                $this->processMappingsFile($this->mappingsFile);
            }

            // TODO clear database: $this->clearDatabase();

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
            throw $e;
        }
    }

    /**
     * assigns mappings as defined in mappings file
     * @param $mappingsFile
     */
    protected function processMappingsFile($mappingsFile)
    {
        // set mappings
        if ($mappingsFile && ($mappingsContent = file_get_contents($mappingsFile))) {
            $mappings = json_decode($mappingsContent, true);
            if (array_key_exists('columns', $mappings)) {
                $this->setColumnMappings($mappings['columns']);
            }
            if (array_key_exists('ids', $mappings)) {
                $this->setIdMappings($mappings['ids']);
            }
            if (array_key_exists('options', $mappings)) {
                $this->setOptions($mappings['options']);
            }
            if (array_key_exists('countries', $mappings)) {
                $this->setCountryMappings($mappings['countries']);
            }
            if (array_key_exists('accountTypes', $mappings)) {
                $this->setAccountTypeMappings($mappings['accountTypes']);
            }
        }
    }

    /**
     * processes the account file
     * @param string $filename path to fil file
     */
    protected function processAccountFile($filename)
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
    protected function processContactFile($filename)
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
                        $headerData = $data;
                    } else {
                        // get associativeData
                        $associativeData = $this->mapRowToAssociativeArray($data, $headerData);

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

        // check if id mapping is defined
        if (array_key_exists('account_id', $this->idMappings)) {
            if (!array_key_exists($this->idMappings['account_id'], $data)) {
                throw new \Exception('no key ' + $this->idMappings['account_id'] + ' found in column definition of accounts file');
            } else {
                $this->associativeAccounts[$data[$this->idMappings['account_id']]] = $account;
            }
        }

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

        // add address if set
        $this->addAddress($data, $account);

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

    private function addAddress($data, $entity)
    {
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
        if ($this->checkData('zip', $data)) {
            $address->setZip($data['zip']);
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
            $entity->addAddresse($address);
        }
    }


    /**
     * creates an contact for given row data
     * @param $data
     * @param $row
     * @return Contact
     */
    protected function createContact($data, $row)
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
            $contact->setTitle($data['contact_title']);
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
                printf('could not assign contact at row %d to %s. (account could not be found)', $row, $data['contact_parent']);
            } else {
//                $contact->setAccount($account);
                $accountContact = new AccountContact();
            }
        }

        // add address if set
        $this->addAddress($data, $contact);

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

        // add notes
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('note' . $i, $data)) {
                $note = new Note();
                $note->setValue($data['note' . $i]);
                $this->em->persist($note);
                $contact->addNote($note);
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
        if ($this->checkData('account_parent', $data)) {
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
     * @param array $headerData header data of csv containing column names
     * @return array
     */
    private function mapRowToAssociativeArray($data, $headerData)
    {
        $associativeData = array();
        foreach ($data as $index => $value) {
            // search index in mapping config
            if ($mappingIndex = array_search($headerData[$index], $this->columnMappings)) {
                $associativeData[$mappingIndex] = $value;
            } else {
                $associativeData[($headerData[$index])] = $value;
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
            return mb_strtoupper($countryCode);
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
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @param mixed $mappingsFile
     */
    public function setMappingsFile($mappingsFile)
    {
        $this->mappingsFile = $mappingsFile;
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

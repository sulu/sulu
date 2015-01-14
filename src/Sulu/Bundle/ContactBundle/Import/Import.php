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
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Sulu\Bundle\ContactBundle\Contact\AbstractContactManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountCategory;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\BankAccount;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Import\Exception\ImportException;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * configures and executes an import for contact and account data from a CSV file
 * @package Sulu\Bundle\ContactBundle\Import
 */
class Import
{
    const DEBUG = true;

    /**
     * import options
     * @var array
     * @param {Boolean=true} importIds defines if ids of import file should be imported
     * @param {Boolean=false} streetNumberSplit defines if street is provided as street- number string and must be splitted
     * @param {Boolean=false|int} fixedAccountType defines if accountType should be set to a fixed type for all imported accounts
     */
    protected $options = array(
        'importIds' => true,
        'streetNumberSplit' => false,
        'fixedAccountType' => false,
        'delimiter' => ';',
        'enclosure' => '"',
    );

    /**
     * defaults that are set for a specific key (if not set in data) e.g.: address1_label = 'mobile'
     * @var array
     */
    protected $defaults = array();

    /**
     * define entity names
     * @var string
     */
    protected $contactEntityName = 'SuluContactBundle:Contact';
    protected $accountEntityName = 'SuluContactBundle:Account';
    protected $accountContactEntityName = 'SuluContactBundle:AccountContact';
    protected $accountCategoryEntityName = 'SuluContactBundle:AccountCategory';
    protected $tagEntityName = 'SuluTagBundle:Tag';
    protected $titleEntityName = 'SuluContactBundle:ContactTitle';
    protected $positionEntityName = 'SuluContactBundle:Position';
    protected $countryEntityName = 'SuluContactBundle:Country';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var AbstractContactManager $accountManager
     */
    protected $accountManager;

    /**
     * @var AbstractContactManager $contactManager
     */
    protected $contactManager;

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
    protected $configDefaults;
    /**
     * Account Types
     * @var $configAccountTypes
     */
    protected $configAccountTypes;
    /**
     * different forms of address
     * @var $configFormOfAddress
     */
    protected $configFormOfAddress;

    /**
     * limit of rows to import
     * @var
     */
    private $limit;

    /**
     * @var array $defaultTypes
     */
    protected $defaultTypes = array();

    /**
     * storage for log messages
     * @var array
     */
    protected $log = array();

    /**
     * storage for storing header data
     * @var array
     */
    protected $headerData = array();

    /**
     * holds the amount of header variables
     * @var int
     */
    protected $headerCount;

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
     * 'account_registerNumber'
     * 'account_category'
     * 'account_tag'
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
     * 'contact_parent'
     * 'contact_title'
     * 'contact_position'
     * 'contact_firstname'
     * 'contact_lastname'
     * contact_formOfAddress
     * contact_salutation
     * contact_birthday
     *
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
     * mappings for form of address
     * @var array
     */
    protected $formOfAddressMappings = array();

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
     * defines mappings of address / url / email / phone / fax types in import file
     * @var array
     */
    protected $contactLabelMappings = array(
        'work' => '.work',
        'home' => '.home',
        'mobile' => '.mobile',
    );

    /**
     * used as temp storage for newly created accounts
     * @var array
     */
    protected $accountExternalIds = array();

    /**
     * used as temp associative storage for newly created accounts
     * @var array
     */
    protected $associativeAccounts = array();

    /**
     * used as temp storage for account categories
     * @var array
     */
    protected $accountCategories = array();

    /**
     * used as temp storage for tags
     * @var array
     */
    protected $tags = array();

    /**
     * used as temp storage for titles
     * @var array
     */
    protected $titles = array();

    /**
     * used as temp storage for positions
     * @var array
     */
    protected $positions = array();

    /**
     * @param EntityManager $em
     * @param $accountManager
     * @param $contactManager
     * @param $configDefaults
     * @param $configAccountTypes
     * @param $configFormOfAddress
     */
    public function __construct(EntityManager $em, $accountManager, $contactManager, $configDefaults, $configAccountTypes, $configFormOfAddress)
    {
        $this->em = $em;
        $this->configDefaults = $configDefaults;
        $this->configAccountTypes = $configAccountTypes;
        $this->configFormOfAddress = $configFormOfAddress;
        $this->accountManager = $accountManager;
        $this->contactManager = $contactManager;
    }

    /**
     * Executes the import
     */
    public function execute()
    {
        // enable garbage collector
        gc_enable();
        // disable sql logger
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        try {
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
     * loads type defaults, tags and account-categories
     * gets called by processcsvloop
     */
    protected function initDefaults()
    {
        // set default types
        $this->defaultTypes = $this->getDefaults();
        $this->loadTags();
        $this->loadTitles();
        $this->loadPositions();
        $this->loadAccountCategories();
    }

    /**
     * assigns mappings as defined in mappings file
     * @param $mappingsFile
     * @return bool|mixed
     * @throws \Symfony\Component\Translation\Exception\NotFoundResourceException
     */
    protected function processMappingsFile($mappingsFile)
    {
        try {
            // set mappings
            if ($mappingsFile && ($mappingsContent = file_get_contents($mappingsFile))) {
                $mappings = json_decode($mappingsContent, true);
                if (!$mappings) {
                    throw new \Exception('no valid JSON in mappings file');
                }
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
                if (array_key_exists('formOfAddress', $mappings)) {
                    $this->setFormOfAddressMappings($mappings['formOfAddress']);
                }
                if (array_key_exists('contactLabels', $mappings)) {
                    $this->contactLabelMappings = $mappings['contactLabels'];
                }
                if (array_key_exists('defaults', $mappings)) {
                    $this->defaults = $mappings['defaults'];
                }
                if (array_key_exists('mainContactTypes', $mappings)) {
                    $this->defaults = $mappings['mainContactTypes'];
                }

                return $mappings;
            }

            return false;
        } catch (\Exception $e) {
            throw new NotFoundResourceException($mappingsFile);
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
        $this->debug("Create Accounts:\n");
        $this->processCsvLoop(
            $filename,
            function ($data, $row) {
                $this->createAccount($data, $row);
            }
        );

        // check for parents
        $this->debug("Creating Account Parent Relations:\n");
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

        // create contacts
        $this->debug("Create Contacts:\n");
        $this->processCsvLoop($filename, $createContact);
    }

    /**
     * Loads the CSV Files and the Entities for the import
     * @param string $filename path to file
     * @param callable $function will be called for each row in file
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    protected function processCsvLoop($filename, $function)
    {
        // initialize default values
        $this->initDefaults();

        $row = 0;
        $successCount = 0;
        $errorCount = 0;
        $this->headerData = array();

        try {
            // load all Files
            $handle = fopen($filename, 'r');
        } catch (\Exception $e) {
            throw new NotFoundResourceException($filename);
        }

        while (($data = fgetcsv($handle, 0, $this->options['delimiter'], $this->options['enclosure'])) !== false) {
            try {
                // for first row, save headers
                if ($row === 0) {
                    $this->headerData = $data;
                    $this->headerCount = count($data);
                } else {

                    if ($this->headerCount !== count($data)) {
                        throw new ImportException('The number of fields does not match the number of header values');
                    }

                    // get associativeData
                    $associativeData = $this->mapRowToAssociativeArray($data, $this->headerData);

                    $entity = $function($associativeData, $row);
                    if ($row % 20 === 0) {
                        $this->em->flush();
                        $this->em->clear();
                        gc_collect_cycles();
                        // reinitialize defaults (lost with call of clear)
                        $this->initDefaults();
                    }

                    $successCount++;
                }
            } catch (DBALException $dbe) {
                $this->debug(sprintf("ABORTING DUE TO DATABASE ERROR: %s \n", $dbe->getMessage()));
                throw $dbe;
            } catch (\Exception $e) {
                $this->debug(sprintf("ERROR while processing data row %d: %s \n", $row, $e->getMessage()));
                $errorCount++;
            }

            // check limit and break loop if necessary
            $limit = $this->getLimit();
            if (!is_null($limit) && $row >= $limit) {
                break;
            }
            $row++;

            if (self::DEBUG) {
                print(sprintf("%d ", $row));
            }
        }
        // finish with a flush
        $this->em->flush();

        $this->debug("\n");
        fclose($handle);
    }

    /**
     * creates an account for given row data
     * @param $data
     * @param $row
     * @return Account
     * @throws \Exception
     */
    protected function createAccount($data, $row)
    {
        // check if account already exists
        $account = new Account();
        $persistAccount = true;

        // check if id mapping is defined
        if (array_key_exists('account_id', $this->idMappings)) {
            if (!array_key_exists($this->idMappings['account_id'], $data)) {
                $this->accountExternalIds[] = null;
                throw new \Exception('no key ' + $this->idMappings['account_id'] + ' found in column definition of accounts file');
            }
            $externalId = $data[$this->idMappings['account_id']];

            $accountFromDb = $this->getAccountByKey($externalId);
            if ($accountFromDb !== null) {
                $account = $accountFromDb;
                $persistAccount = false;
            } else {
                $account->setExternalId($externalId);
            }
        }
        $this->accountExternalIds[] = $externalId;

        // clear notes
        if (!$account->getNotes()->isEmpty()) {
            $account->getNotes()->clear();
        }

        $account->setChanged(new \DateTime());
        $account->setCreated(new \DateTime());

        if ($this->checkData('account_name', $data)) {
            $account->setName($data['account_name']);
        } else {
            throw new \Exception('ERROR: account name not set');
        }

        if ($this->checkData('account_corporation', $data)) {
            $account->setCorporation($data['account_corporation']);
        }
        if ($this->checkData('account_disabled', $data)) {
            $account->setDisabled($this->getBoolValue($data['account_disabled']));
        }
        if ($this->checkData('account_uid', $data)) {
            $account->setUid($data['account_uid']);
        }
        if ($this->checkData('account_number', $data)) {
            $account->setNumber($data['account_number']);
        }
        if ($this->checkData('account_registerNumber', $data)) {
            $account->setRegisterNumber($data['account_registerNumber']);
        }
        if ($this->checkData('account_jurisdiction', $data)) {
            $account->setPlaceOfJurisdiction($data['account_jurisdiction']);
        }
        // set account type
        if ($this->options['fixedAccountType'] != false && is_numeric($this->options['fixedAccountType'])) {
            // set account type to a fixed number
            $account->setType($this->options['fixedAccountType']);
        } elseif ($this->checkData('account_type', $data)) {
            $account->setType($this->mapAccountType($data['account_type']));
        }

        if ($this->checkData('account_category', $data)) {
            $this->addCategory($data['account_category'], $account);
        }
        if ($this->checkData('account_tag', $data)) {
            $this->addTag($data['account_tag'], $account);
        }

        // process emails, phones, faxes, urls and notes
        $this->processEmails($data, $account);
        $this->processPhones($data, $account);
        $this->processFaxes($data, $account);
        $this->processUrls($data, $account);
        $this->processNotes($data, $account, 'account_');

        // add address if set
        $address = $this->createAddresses($data, $account);
        if ($address !== null) {
            $this->getAccountManager()->addAddress($account, $address, true);
        }

        // add bank accounts
        $this->addBankAccounts($data, $account);

        if ($persistAccount) {
            $this->em->persist($account);
        }

        return $account;
    }

    /**
     * iterate through data and find first of a specific type (which is enumerable)
     * @param $identifier
     * @param $data
     * @return string|bool
     */
    protected function getFirstOf($identifier, $data)
    {
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData($identifier . $i, $data)) {
                return $data[$identifier . $i];
            }
        }

        return false;
    }

    /**
     * adds emails to the entity
     * @param $data
     * @param $entity
     */
    protected function processEmails($data, $entity)
    {
        // add emails
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('email' . $i, $data)) {
                $emailIndex = 'email' . $i;
                $prefix = $emailIndex . '_';

                $email = new Email();
                $email->setEmail($data[$emailIndex]);

                // set label
                if ($this->checkData($prefix . 'label', $data)) {
                    $contactLabel = $this->mapContactLabels($data[$prefix . 'label']);
                    $type = $this->getContactManager()->getEmailTypeByName($contactLabel);
                } else {
                    $type = $this->defaultTypes['emailType'];
                }
                $email->setEmailType($type);

                $this->em->persist($email);
                $entity->addEmail($email);
            }
        }
        $this->getContactManager()->setMainEmail($entity);
    }

    /**
     * adds phones to an entity
     * @param $data
     * @param $entity
     */
    protected function processPhones($data, $entity)
    {
        // add phones
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('phone' . $i, $data, null, 60)) {
                $phoneIndex = 'phone' . $i;
                $prefix = $phoneIndex . '_';

                $phone = new Phone();
                $phone->setPhone($data[$phoneIndex]);

                // set label
                if ($this->checkData($prefix . 'label', $data)) {
                    $contactLabel = $this->mapContactLabels($data[$prefix . 'label']);
                    $type = $this->getContactManager()->getPhoneTypeByName($contactLabel);
                } else {
                    $type = $this->defaultTypes['phoneType'];
                }
                $phone->setPhoneType($type);

                $this->em->persist($phone);
                $entity->addPhone($phone);
            }
        }
        $this->getContactManager()->setMainPhone($entity);
    }

    /**
     * adds faxes to an entity
     * @param $data
     * @param $entity
     */
    protected function processFaxes($data, $entity)
    {
        // add faxes
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('fax' . $i, $data, null, 60)) {
                $faxIndex = 'fax' . $i;
                $prefix = $faxIndex . '_';

                $fax = new Fax();
                // set fax
                $fax->setFax($data[$faxIndex]);
                // set label
                if ($this->checkData($prefix . 'label', $data)) {
                    $contactLabel = $this->mapContactLabels($data[$prefix . 'label']);
                    $type = $this->getContactManager()->getFaxTypeByName($contactLabel);
                } else {
                    $type = $this->defaultTypes['faxType'];
                }
                $fax->setFaxType($type);

                $this->em->persist($fax);
                $entity->addFax($fax);
            }
        }
        $this->getContactManager()->setMainFax($entity);
    }

    /**
     * adds urls to an entity
     * @param $data
     * @param $entity
     */
    protected function processUrls($data, $entity)
    {
        // add urls
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData('url' . $i, $data, null, 255)) {
                $urlIndex = 'url' . $i;
                $prefix = $urlIndex . '_';

                $url = new Url();
                // set url
                $url->setUrl($data[$urlIndex]);
                // set label
                if ($this->checkData($prefix . 'label', $data)) {
                    $contactLabel = $this->mapContactLabels($data[$prefix . 'label']);
                    $type = $this->getContactManager()->getUrlTypeByName($contactLabel);
                } else {
                    $type = $this->defaultTypes['urlType'];
                }
                $url->setUrlType($type);

                // persist
                $this->em->persist($url);
                $entity->addUrl($url);
            }
        }
        $this->getContactManager()->setMainUrl($entity);
    }

    /**
     * concats notes and adds it to the entity
     * @param $data
     * @param $entity
     */
    protected function processNotes($data, $entity, $prefix = '')
    {
        // add note -> only use one note
        // TODO: use multiple notes, when contact is extended
        $noteValues = array();
        if ($this->checkData($prefix . 'note', $data)) {
            $noteValues[] = $data[$prefix . 'note'];
        }
        for ($i = 0, $len = 10; ++$i < $len;) {
            if ($this->checkData($prefix . 'note' . $i, $data)) {
                $noteValues[] = $data[$prefix . 'note' . $i];
            }
        }
        // concat all notes to one single note
        if (sizeof($noteValues) > 0) {
            $note = new Note();
            $note->setValue(implode("\n", $noteValues));
            $this->em->persist($note);
            $entity->addNote($note);
        }
    }

    /**
     * lookup if category already exists, otherwise, it will be created
     * @param $categoryName
     * @param Account $account
     */
    protected function addCategory($categoryName, Account $account)
    {
        $categoryName = trim($categoryName);
        if (array_key_exists($categoryName, $this->accountCategories)) {
            $category = $this->accountCategories[$categoryName];
        } else {
            $category = new AccountCategory();
            $category->setCategory($categoryName);
            $this->em->persist($category);
            $this->accountCategories[$category->getCategory()] = $category;
        }
        $account->setAccountCategory($category);
    }

    /**
     * Adds a tag to an account / contact
     * @param $tagName
     * @param $entity
     */
    protected function addTag($tagName, $entity)
    {
        $tagName = trim($tagName);
        if (array_key_exists($tagName, $this->tags)) {
            $tag = $this->tags[$tagName];
        } else {
            $tag = new Tag();
            $tag->setName($tagName);
            $tag->setCreated(new \DateTime());
            $tag->setChanged(new \DateTime());
            $this->em->persist($tag);
            $this->tags[$tag->getName()] = $tag;
        }
        $entity->addTag($tag);
    }

    /**
     * Adds a title to an account / contact
     * @param $titleName
     * @param $entity
     */
    protected function addTitle($titleName, $entity)
    {
        $titleName = trim($titleName);
        if (array_key_exists($titleName, $this->titles)) {
            $title = $this->titles[$titleName];
        } else {
            $title = new ContactTitle();
            $title->setTitle($titleName);
            $this->em->persist($title);
            $this->titles[$title->getTitle()] = $title;
        }
        $entity->setTitle($title);
    }

    /**
     * Adds a position to an account / contact
     * @param $positionName
     * @param $entity
     */
    protected function addPosition($positionName, $entity)
    {
        $positionName = trim($positionName);
        if (array_key_exists($positionName, $this->positions)) {
            $position = $this->positions[$positionName];
        } else {
            $position = new Position();
            $position->setPosition($positionName);
            $this->em->persist($position);
            $this->positions[$position->getPosition()] = $position;
        }
        $entity->setPosition($position);
    }

    /**
     * iterates through data and adds addresses
     * @param $data
     * @param $entity
     */
    protected function createAddresses($data, $entity) {
        // add urls
        $first = true;
        for ($i = 0, $len = 10; ++$i < $len;) {
            foreach ($data as $key => $value) {
                if (strpos($key, 'address' . $i) !== false) {
                    $address = $this->createAddress($data, $i);
                    // add address to entity
                    if ($address !== null) {
                        $first = $first || $address->getPrimaryAddress();
                        $this->getManager($entity)->addAddress($entity, $address, $first);
                    }
                    break;
                }
            }
        }
    }

    /**
     * creates an address entity based on passed data
     * @param $data
     * @param int $id index of the address in data array (e.g. 1 => address1_street)
     * @return null|Address
     * @throws EntityNotFoundException
     */
    protected function createAddress($data, $id = 1)
    {
        // set address
        $address = new Address();
        $addAddress = false;
        $prefix = 'address' . $id . '_';

        // street
        if ($this->checkData($prefix . 'street', $data)) {
            $street = $data[$prefix . 'street'];

            // separate street and number
            if ($this->options['streetNumberSplit']) {
                preg_match('/(*UTF8)([^\d]+)\s?(.+)/iu', $street, $result); // UTF8 is to ensure correct utf8 encoding

                // check if number is given, else do not apply preg match
                if (array_key_exists(2, $result) && is_numeric($result[2])) {
                    $number = trim($result[2]);
                    $street = trim($result[1]);
                }
            }
            $address->setStreet($street);
            $addAddress = true;
        }
        // number
        if (isset($number) || $this->checkData($prefix . 'number', $data)) {
            $number = isset($number) ? $number : $data[$prefix . 'number'];
            $address->setNumber($number);
        }
        // zip
        if ($this->checkData($prefix . 'zip', $data)) {
            $address->setZip($data[$prefix . 'zip']);
            $addAddress = true;
        }
        // city
        if ($this->checkData($prefix . 'city', $data)) {
            $address->setCity($data[$prefix . 'city']);
            $addAddress = true;
        }
        // state
        if ($this->checkData($prefix . 'state', $data)) {
            $address->setState($data[$prefix . 'state']);
            $addAddress = true;
        }
        // extension
        if ($this->checkData($prefix . 'extension', $data)) {
            $address->setAddition($data[$prefix . 'extension']);
            $addAddress = true;
        }
        // postbox
        if ($this->checkData($prefix . 'postbox', $data)) {
            $address->setPostboxNumber($data[$prefix . 'postbox']);
            $addAddress = true;
        }
        if ($this->checkData($prefix . 'postbox_zip', $data)) {
            $address->setPostboxPostcode($data[$prefix . 'postbox_zip']);
            $addAddress = true;
        }
        if ($this->checkData($prefix . 'postbox_city', $data)) {
            $address->setPostboxCity($data[$prefix . 'postbox_city']);
            $addAddress = true;
        }
        // note
        if ($this->checkData($prefix . 'note', $data)) {
            $address->setNote($data[$prefix . 'note']);
            $addAddress = true;
        }
        // billing address
        if ($this->checkData($prefix . 'isbilling', $data)) {
            $address->setBillingAddress(
                $this->getBoolValue($data[$prefix . 'isbilling'])
            );
        }
        // delivery address
        if ($this->checkData($prefix . 'isdelivery', $data)) {
            $address->setDeliveryAddress(
                $this->getBoolValue($data[$prefix . 'isdelivery'])
            );
        }
        // primary address
        if ($this->checkData($prefix . 'isprimary', $data)) {
            $address->setPrimaryAddress(
                $this->getBoolValue($data[$prefix . 'isprimary'])
            );
        }
        // country
        if ($this->checkData($prefix . 'country', $data)) {
            $country = $this->em->getRepository($this->countryEntityName)->findOneByCode(
                $this->mapCountryCode($data[$prefix . 'country'])
            );

            if (!$country) {
                throw new EntityNotFoundException('Country', $data[$prefix . 'country']);
            }

            $address->setCountry($country);
            $addAddress = $addAddress && true;
        } else {
            $addAddress = false;
        }

        // only add address if part of it is defined
        if ($addAddress) {
            if ($this->checkData($prefix . 'label', $data)) {
                $contactLabel = $this->mapContactLabels($data[$prefix . 'label']);
                $addressType = $this->getContactManager()->getAddressTypeByName($contactLabel);
            } else {
                $addressType = $this->defaultTypes['addressType'];
            }

            $address->setAddressType($addressType);


            $this->em->persist($address);

            return $address;
        }

        return null;
    }

    /**
     * returns true if $value is true or y or j or 1
     * @param $value
     * @return bool
     */
    protected function getBoolValue($value) {
        if ($value == true ||
            strtolower($value) === 'y' ||
            strtolower($value) === 'j' ||
            $value == '1'
        ) {
            return true;
        }
        return false;
    }

    // gets financial information and adds it
    protected function addBankAccounts($data, $entity)
    {
        // TODO handle one or more bank accounts
        for ($i = 0, $len = 10; ++$i < $len;) {
            // if iban is set, then add bank account
            if ($this->checkData('bank' . $i, $data)) {
                $bankIndex = 'bank' . $i;
                $prefix = $bankIndex . '_';

                $bank = new BankAccount();
                if ($this->checkData($prefix . 'iban', $data)) {
                    $bank->setIban($data[$prefix . 'iban']);
                } else {
                    throw new ImportException('no Iban provided for entity with id '. $entity->getId());
                }

                if ($this->checkData($prefix . 'bic', $data)) {
                    $bank->setBic($data[$prefix . 'bic']);
                }
                if ($this->checkData($bankIndex, $data)) {
                    $bank->setBankName($data[$bankIndex]);
                }
                // set bank to public
                if ($this->checkData($prefix . 'public', $data, 'bool')) {
                    $bank->setPublic($data[$prefix . 'public']);
                } else {
                    $bank->setPublic(false);
                }

                $this->em->persist($bank);
                $entity->addBankAccount($bank);
            }

            // create comments for old bank addresses
            if ($this->checkData('blz' . $i, $data)) {
                $noteTxt = '';
                // check if note already exists, or create a new one
                if (sizeof($notes = $entity->getNotes()) > 0) {
                    $note = $notes[0];
                    $noteTxt = $note->getValue() . "\n";
                } else {
                    $note = new Note();
                    $this->em->persist($note);
                    $entity->addNote($note);
                }

                $noteTxt .= 'Old Bank Account: ';
                $noteTxt .= 'BLZ: ';
                $noteTxt .= $data['blz' . $i];

                if ($this->checkData('accountNumber' . $i, $data)) {
                    $noteTxt .= '; Account-Number: ';
                    $noteTxt .= $data['accountNumber' . $i];
                }
                if ($this->checkData('bank' . $i, $data)) {
                    $noteTxt .= '; Bank-Name: ';
                    $noteTxt .= $data['bank' . $i];
                }

                $note->setValue($noteTxt);
            }
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
        try {
            // check if contact already exists
            $contact = $this->getContactByData($data);

            // or create a new one
            if (!$contact) {
                $contact = new Contact();
                $this->em->persist($contact);
                $this->setContactData($data, $contact);
            }
            // create account relation
            $this->setAccountContactRelations($data, $contact, $row);

            return $contact;

        } catch (NonUniqueResultException $nur) {
            $this->debug(sprintf("Non unique result for contact at row %d \n", $row));
        }

    }

    /**
     * @param Contact $contact
     * @param $data
     */
    protected function setContactData($data, Contact $contact)
    {
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
            $this->addTitle($data['contact_title'], $contact);
        }

        if ($this->checkData('contact_form_of_address', $data)) {
            $contact->setFormOfAddress($this->mapFormOfAddress($data['contact_form_of_address']));
        }

        if ($this->checkData('contact_salutation', $data)) {
            $contact->setSalutation($data['contact_salutation']);
        }

        if ($this->checkData('contact_birthday', $data)) {
            $contact->setBirthday(new \DateTime($data['contact_birthday']));
        }

        if ($this->checkData('contact_disabled', $data)) {
            $contact->setDisabled($this->getBoolValue($data['contact_disabled']));
        }

        if ($this->checkData('contact_tag', $data)) {
            $this->addTag($data['contact_tag'], $contact);
        }

        $contact->setChanged(new \DateTime());
        $contact->setCreated(new \DateTime());

        // check company
        $this->em->persist($contact);

        // add address if set
        $this->createAddresses($data, $contact);

        // process emails, phones, faxes, urls and notes
        $this->processEmails($data, $contact);
        $this->processPhones($data, $contact);
        $this->processFaxes($data, $contact);
        $this->processUrls($data, $contact);
        $this->processNotes($data, $contact, 'contact_');
    }

    /**
     * checks if a main account-contact relation exists
     * @param $entity
     * @return mixed
     */
    private function mainRelationExists($entity)
    {
        return $entity->getAccountContacts()->exists(function ($index, $entity) {
            return $entity->getMain() === true;
        });
    }

    /**
     * either creates a single contact relation (contact_account) or multiple relations (contact_account1..n)
     * @param $data
     * @param $contact
     * @param $row
     */
    protected function setAccountContactRelations($data, $contact, $row)
    {
        $index = 'contact_account';

        if ($this->checkData($index, $data)) {
            $this->setAccountContactRelation($data, $contact, $row, $index);
        } else {
            // iterate through account_contacts
            for ($i = 0, $len = 10; ++$i < $len;) {
                if ($this->checkData($index . $i, $data)) {
                    $this->setAccountContactRelation($data, $contact, $row, $index . $i);
                }
            }
        }
    }

    /**
     * adds a accountcontact relation if not existent
     * @param $data
     * @param $contact
     * @param $row
     * @param $index - account-index in data array
     */
    protected function setAccountContactRelation($data, $contact, $row, $index)
    {
        if ($this->checkData($index, $data)) {
            $account = $this->getAccountByKey($data[$index]);

            if (!$account) {
                // throw new \Exception('could not find '.$data['contact_parent_id'].' in accounts');
                $this->debug(sprintf("Could not assign contact at row %d to %s. (account could not be found)\n", $row, $data[$index]));
            } else {

                // check if relation already exists
                $accountContact = null;
                if (!$this->em->getUnitOfWork()->isScheduledForInsert($contact)) {
                    $accountContact = $this->em
                        ->getRepository($this->accountContactEntityName)
                        ->findOneBy(array(
                            $account => $account,
                            $contact => $contact
                            )
                        );
                }

                if (!$accountContact) {
                    // account contact relation
                    $accountContact = new AccountContact();
                    $accountContact->setContact($contact);
                    $accountContact->setAccount($account);
                    $contact->addAccountContact($accountContact);
                    $account->addAccountContact($accountContact);
                    $this->em->persist($accountContact);
                }

                // check if relation should be set to main
                $main = false;
                if ($this->checkData('contact_account_is_main', $data)) {
                    $main = $this->getBoolValue($data['contact_account_is_main']);
                } else {
                    // check if main relation exists
                    if (!$this->mainRelationExists($contact)) {
                        $main = true;
                    }
                }
                $accountContact->setMain($main);

                // set position
                if ($this->checkData('contact_position', $data)) {
                    $this->addPosition($data['contact_position'], $accountContact);
                }
            }
        }
    }

    /**
     * returns a contact based on data array if it already exists in DB
     * @param $data
     * @return mixed
     */
    protected function getContactByData($data)
    {

        $criteria = array();
        $email = null;
        $phone = null;

        if ($this->options['importIds'] == true && $this->checkData('contact_id', $data)) {
            $criteria['id'] = $data['contact_id'];
        } else {
            // TODO:
            // check if contacts already exists
            if ($this->checkData('contact_firstname', $data)) {
                $criteria['firstName'] = $data['contact_firstname'];
            }
            if ($this->checkData('contact_lastname', $data)) {
                $criteria['lastName'] = $data['contact_lastname'];
            }
            $email = $this->getFirstOf('email', $data);
            $phone = $this->getFirstOf('phone', $data);
        }

        /** @var ContactRepository $repo */
        $repo = $this->em->getRepository($this->contactEntityName);
        $contact = $repo->findByCriteriaEmailAndPhone($criteria, $email, $phone);

        return $contact;
    }

    /**
     * checks data for validity
     */
    protected function checkData($index, $data, $type = null, $maxLength = null)
    {
        $isDataSet = array_key_exists($index, $data) && $data[$index] !== '';
        if ($isDataSet) {
            if ($type !== null) {
                // TODO check for types
                if ($type === 'bool' && $data[$index] != 'true' && $data[$index] != 'false' && $data[$index] != '1' && $data[$index] != '0') {
                    throw new \InvalidArgumentException($data[$index] . ' is not a boolean!');
                }
            }
            if ($maxLength !== null && intval($maxLength) && strlen($data[$index]) > $maxLength) {
                throw new \InvalidArgumentException($data[$index] . ' exceeds max length of ' . $index);
            }
        }

        return $isDataSet;
    }

    /**
     * creates relation between parent and account
     */
    protected function createAccountParentRelation($data, $row)
    {
        // if account has parent
        if ($this->checkData('account_parent_id', $data)) {
            // get account
            $externalId = $this->getExternalId($data, $row);
            /** @var Account $account */
            $account = $this->getAccountByKey($externalId);

            if (!$account) {
                throw new \Exception(sprintf('account with id %s could not be found.', $externalId));
            }
            // get parent account
            $parent = $this->getAccountByKey($data['account_parent_id']);
            $account->setParent($parent);
        }
    }

    /**
     * truncate table for account and contact
     */
    protected function clearDatabase()
    {
        $this->clearTable($this->accountEntityName);
        $this->clearTable($this->contactEntityName);
    }

    /**
     * truncate one single table for given entity name
     * @param string $entityName name of entity
     */
    protected function clearTable($entityName)
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
    protected function mapRowToAssociativeArray($data, $headerData)
    {
        $associativeData = array();
        foreach ($data as $index => $value) {
            if ($index >= sizeof($headerData)) {
                break;
            }
            // search index in mapping config
            if (sizeof($resultArray = array_keys($this->columnMappings, $headerData[$index])) > 0) {
                foreach ($resultArray as $key) {
                    $associativeData[$key] = $value;
                }
            } else {
                $associativeData[($headerData[$index])] = $value;
            }
        }

        return array_merge($this->defaults, $associativeData);
    }

    protected function loadAccountCategories()
    {
        $categories = $this->em->getRepository($this->accountCategoryEntityName)->findAll();
        /** @var AccountCategory $category */
        foreach ($categories as $category) {
            $this->accountCategories[$category->getCategory()] = $category;
        }
    }

    protected function loadTags()
    {
        $tags = $this->em->getRepository($this->tagEntityName)->findAll();
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $this->tags[$tag->getName()] = $tag;
        }
    }

    protected function loadTitles()
    {
        $titles = $this->em->getRepository($this->titleEntityName)->findAll();
        /** @var Title $title */
        foreach ($titles as $title) {
            $this->titles[$title->getTitle()] = $title;
        }
    }

    protected function loadPositions()
    {
        $positions = $this->em->getRepository($this->positionEntityName)->findAll();
        /** @var Position $position */
        foreach ($positions as $position) {
            $this->positions[$position->getPosition()] = $position;
        }
    }

    /**
     * @param $countryCode
     * @return mixed|string
     */
    protected function mapCountryCode($countryCode)
    {
        if ($mappingIndex = array_search($countryCode, $this->countryMappings)) {
            return $mappingIndex;
        } else {
            return mb_strtoupper($countryCode);
        }
    }

    /**
     * returns form of addresses id, if defined
     * @param $formOfAddress
     * @return mixed
     */
    protected function mapFormOfAddress($formOfAddress)
    {
        return $this->mapReverseByConfigId($formOfAddress, $this->formOfAddressMappings, $this->configFormOfAddress);
    }

    /**
     * @param $typeString
     * @return int|mixed
     */
    protected function mapAccountType($typeString)
    {
        if ($mappingIndex = array_search($typeString, $this->accountTypeMappings)) {
            return $mappingIndex;
        } else {
            return Account::TYPE_BASIC;
        }
    }

    /**
     * @param $typeString
     * @return int|mixed
     */
    protected function mapContactLabels($typeString)
    {
        if (array_key_exists($typeString, $this->contactLabelMappings)) {
            return $this->contactLabelMappings[$typeString];
        } else {
            return $typeString;
        }
    }

    /**
     * @param array $formOfAddressMappings
     */
    public function setFormOfAddressMappings($formOfAddressMappings)
    {
        $this->formOfAddressMappings = $formOfAddressMappings;
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
     * TODO
     * @param mixed $accountFile
     */
    public function setAccountFile($accountFile)
    {
        $this->accountFile = $accountFile;
    }

    /**
     * @return mixed
     */
    public function getAccountFile()
    {
        return $this->accountFile;
    }

    /**
     * @param mixed $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
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
     * @param $key
     * @return Account|null
     */
    public function getAccountByKey($key)
    {
        return $this->em->getRepository($this->accountEntityName)->findOneBy(array('externalId' => $key));
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
    protected function getDefaults()
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

    /**
     * prints messages if debug is set to true
     * @param $message
     */
    protected function debug($message)
    {
        $this->log[] = $message;
        if (self::DEBUG) {
            print($message);
        }
    }

    /**
     * creates a logfile in import-files folder
     */
    public function createLogFile()
    {
        $root = 'import-files/logs/contactimport/';
        $timestamp = time();
        $file = fopen($root . 'log-' . $timestamp . '.txt', 'w');
        fwrite($file, implode("\n", $this->log));
        fclose($file);
    }

    /**
     * maps a certain index to a mappings array and returns it's index as defined in config array
     * mapping is defined as mappingindex => $index
     * @param $index
     * @param array $mappings
     * @param array $config
     * @return mixed
     */
    protected function mapByConfigId($index, $mappings, $config)
    {
        if ($mappingIndex = array_search($index, $mappings)) {
            if (array_key_exists($mappingIndex, $config)) {
                return $config[$mappingIndex]['id'];
            }

            return $mappingIndex;
        } else {
            return $index;
        }
    }

    /**
     * maps a certain index to a mappings array and returns it's index as defined in config array
     * @param $index
     * @param array $mappings
     * @param array $config
     * @return mixed
     */
    protected function mapReverseByConfigId($index, $mappings, $config)
    {
        if (array_key_exists($index, $mappings)) {
            $mappingIndex = $mappings[$index];
            if (array_key_exists($mappingIndex, $config)) {
                return $config[$mappingIndex]['id'];
            }

            return $mappingIndex;
        } else {
            return $index;
        }
    }

    /**
     * gets the external id of an account by providing the dataset
     * @param $data
     * @param $row
     * @return mixed
     * @throws \Exception
     */
    protected function getExternalId($data, $row)
    {
        if (array_key_exists('account_id', $this->idMappings)) {
            if (!array_key_exists($this->idMappings['account_id'], $data)) {
                throw new \Exception('no key ' + $this->idMappings['account_id'] + ' found in column definition of accounts file');
            }
            $externalId = $data[$this->idMappings['account_id']];
        } else {
            $externalId = $this->accountExternalIds[$row - 1];
        }

        return $externalId;

    }

    /**
     * @param $entity
     * @return AbstractContactManager
     */
    protected function getManager($entity)
    {
        if ($entity instanceof Contact) {
            return $this->getContactManager();
        } else {
            return $this->getAccountManager();
        }
    }

    /**
     * @return AbstractContactManager
     */
    protected function getContactManager()
    {
        return $this->contactManager;
    }
    /**
     * @return AbstractContactManager
     */
    protected function getAccountManager()
    {
        return $this->accountManager;
    }
}

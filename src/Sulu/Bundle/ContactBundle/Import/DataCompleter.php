<?php
/*
 * (c) MASSIVE ART WebServices GmbH
 */

namespace Sulu\Bundle\ContactBundle\Import;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\AccountRepository;
use Sulu\Bundle\ContactBundle\Import\Exception\ImportException;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

/**
 * @package Sulu\Bundle\ContactBundle\Import
 */
class DataCompleter
{
    /**
     * constants
     */
    const DEBUG = true;
    const BATCH_SIZE = 20;
    const API_CALL_LIMIT_PER_SECOND = 9;
    const API_CALL_SLEEP_TIME = 2;

    /**
     * geocode API
     * @var string
     */
    private static $geocode_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=';

    /**
     * options
     * @var array
     */
    protected $options = array(
        'delimiter' => ';',
        'enclosure' => '"',
    );

    /**
     * TODO create setter
     * mappings
     * @var array
     */
    protected $columnMappings = array(
        'country' => 'LKZ',
        'street' => 'strasse',
        'zip' => 'PLZ',
        'state' => 'state',
        'city' => 'Ort',
    );

    /**
     * @var array
     */
    protected $headerData = array();

    /**
     * language of the api results
     * @var string
     */
    protected $locale;

    /**
     * logfile
     * @var array
     */
    protected $log;

    /**
     * filepath of import file
     * @var string
     */
    protected $file;

    /**
     * limit execution
     * @var int
     */
    protected $limit;

    /**
     * currently processed row
     * @var int
     */
    protected $currentRow;

    /**
     * timestamp of the last api call
     * @var int
     */
    protected $lastApiCallTime;

    /**
     * number of api calls that are made in this sencond
     * @var
     */
    protected $lastApiCallCount;

    /**
     * @var EntityManagerInterface
     */
    protected $em;
    
    /**
     * @var AccountRepository
     */
    protected $accountRepository;

    /**
     * constructor
     */
    public function __construct(
        EntityManagerInterface $em,
        $accountRepository
    )
    {
        $this->log = array();
        
        $this->em = $em;
        $this->accountRepository = $accountRepository;
    }

    /**
     * set limit of rows to process
     *
     * @param $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * set language of completion
     *
     * @param $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * set file to process
     * @param $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * appends a string to a path + filename
     *
     * @param $oldPath
     * @param $postfix
     * @param bool $keepExtension
     * @return string
     */
    protected function extendFileName($oldPath, $postfix, $keepExtension = true)
    {
        $parts = pathinfo($oldPath);
        $filename = $parts['dirname'] . '/' . $parts['filename'] . $postfix;
        if ($keepExtension) {
            $filename .=  '.' . $parts['extension'];
        }
        return $filename;
    }

    /**
     * process csv file
     */
    public function executeCsvCompletion()
    {
        $outputFileName = $this->extendFileName($this->file,'_processed');
        $output = fopen($outputFileName, "w");

        $this->processCsvLoop(
            $this->file,
            function ($data) use ($output) {

                $data = $this->completeAddress($data);

                fputcsv($output, $data, $this->options['delimiter'], $this->options['enclosure']);
            },
            function ($data) use ($output) {
                fputcsv($output, $data, $this->options['delimiter'], $this->options['enclosure']);
            }
        );
        fclose($output);

        $this->createLogFile();
    }

    /**
     * process csv file
     */
    public function executeDbCompletion($databaseOptions)
    {
        if (in_array('state', $databaseOptions)) {
            $this->debug("Completing states:\n");
            
            /** @var ContentQueryBuilderInterface $qb */
            $qb = $this->accountRepository->createQueryBuilder('account')
                ->select('account.id');
            
            if ($this->limit) {
                $qb->setMaxResults($this->limit);
            }

            $ids = $qb->getQuery()->getScalarResult();
            $accountIds = array_column($ids, 'id');
            $this->debug(sprintf("Found %d accounts to complete addresses.\n", count($accountIds)));
            
            $counter = 0;
            foreach ($accountIds as $id) {
                $counter++;
                $account = $this->accountRepository->find($id);
                $this->updateStateOfAddresses($account->getAccountAddresses());
                
                // store
                if ($counter % self::BATCH_SIZE === 0) {
                    $this->em->flush();
                    $this->em->clear();
                }
            }
            
            $this->em->flush();
        }
//        $this->createLogFile();
    }

    /**
     * @param $addresses
     * @internal param $entity
     */
    protected function updateStateOfAddresses($addresses)
    {
        if (!$addresses || $addresses->isEmpty()) {
            return;
        }
        /** @var AccountAddress $accountAddress */
        foreach ($addresses as $accountAddress) {
            $this->debug('.', false);
            $address = $accountAddress->getAddress();
         
            $zip = $address->getZip();
            $country = $address->getCountry()->getName();
            
            // identify state by zip and country
            if($zip && $country) {
                $state = $this->getStateByApiCall(array($zip, $country));
                if ($state) {
                    $address->setState($state);
                }
            }
        }
    }

    /**
     * callback loop
     *
     * @param $filename
     * @param callable $callback
     * @param callable $headerCallback
     */
    protected function processCsvLoop(
        $filename,
        callable $callback,
        callable $headerCallback
    ) {
        $row = 0;
        $this->currentRow = 0;
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

                    $headerCallback($data);
                } else {

                    if ($this->headerCount !== count($data)) {
                        throw new ImportException('The number of fields does not match the number of header values');
                    }

                    $callback($data);

                }
            } catch (\Exception $e) {
                $this->debug(sprintf("ERROR while processing data row %d: %s \n", $row, $e->getMessage()));
            }

            // check limit and break loop if necessary
            $limit = $this->limit;
            if (!is_null($limit) && $row >= $limit) {
                break;
            }
            $row++;
            $this->currentRow = $row;

            if (self::DEBUG) {
                print(sprintf("%d ", $row));
            }
        }
        $this->debug("\n");
        fclose($handle);
    }

    /**
     * function checks for missing
     *
     * @param $data
     * @return mixed
     */
    protected function completeAddress($data)
    {
        $city = $this->getColumnValue('city', $data);
        $street = $this->getColumnValue('street', $data);
        $state = $this->getColumnValue('state', $data);
        $country = $this->getColumnValue('country', $data);
        $zip = $this->getColumnValue('zip', $data);
        if ($city || $street || $state || $country || $zip) {

            // address data is given
            // check if country is set
            if (!$country) {
                // we need to make an api call to fetch country
                $country = $this->getCountryByApiCall(array($street, $city, $zip, $state));

                $this->setColumnValue('country', $data, $country);
            }
        }

        return $data;
    }

    /**
     * performs an api call and returns shorcode for a country
     * @param $data
     * @return null|void
     */
    protected function getCountryByApiCall($data = array())
    {
        return $this->getDataByApiCall($data, array($this, 'getDataFromApiResultByKey'), array('country'));
    }

    /**
     * performs an api call and returns shorcode for a country
     * @param $data
     * @return null|void
     */
    protected function getStateByApiCall($data = array())
    {
        // FIXME: this is a workaround for a google geocode bug: austrian state names are only properly returned in
        //      short_name. for all other countries take long_name
        $resultKey = in_array('Austria', $data) ? 'short_name' : 'long_name';
        return $this->getDataByApiCall($data, array($this, 'getDataFromApiResultByKey'), array('administrative_area_level_1', $resultKey));
    }

    /**
     * returns key from google geocode api result
     * @param object $result
     * @param string $key
     * @param $returnKey $key (either short_name or long_name)
     * @return null|string (short_name)
     */
    protected function getDataFromApiResultByKey($result, $key, $returnKey = 'short_name')
    {
        if (property_exists($result, 'address_components')) {
            foreach ($result->address_components as $resultBlock) {
                if ($resultBlock->types[0] === $key) {
                    return $resultBlock->$returnKey;
                }
            }
        }
        return null;
    }

    /**
     * performs an api call and returns shorcode for a country
     *
     * @param array $dataArray
     * @param callable $resultCallback will be called, passing the api-result object
     * @param array $callbackData Possibility to pass additional data to the callback
     *
     * @return mixed|void
     */
    protected function getDataByApiCall($dataArray = array(), callable $resultCallback, $callbackData = array())
    {
        // limit api calls per second
        if ($this->lastApiCallTime == time()) {
            if ($this->lastApiCallCount >= static::API_CALL_LIMIT_PER_SECOND) {
                sleep(static::API_CALL_SLEEP_TIME);
                return $this->getDataByApiCall($dataArray, $resultCallback);
            }
            $this->lastApiCallCount++;
        } else {
            $this->lastApiCallCount = 1;
            $this->lastApiCallTime = time();
        }

        // remove null values
        $params = array_filter($dataArray);
        // create string
        $params = implode(',', $params);
        // avoid spaces
        $urlparams = urlencode($params);

        $apiResult = json_decode(file_get_contents(static::$geocode_url . $urlparams . '&language=' . $this->locale));

        $results = $apiResult->results;

        if (count($results) === 0) {
            $this->debug(sprintf("ERROR: No valid data found at row %d (by api)", $this->currentRow, $params));
            return null;
        }

        // take first result (if not unique)
        $result = $results[0];

        // get data by callback user function
        $callbackDataArray = array_merge(array($result), $callbackData);
        $data = call_user_func_array($resultCallback, $callbackDataArray);

        if (!$data) {
            $this->debug(sprintf("ERROR: No data found in result for row %d", $this->currentRow));
            return null;
        }

        if (count($results) >1) {
            $this->debug(
                sprintf("Non unique result at row %d! chose data %s (params: %s)",
                    $this->currentRow,
                    $data,
                    $params
                )
            );
        }

        return $data;
    }

    /**
     * prints messages if debug is set to true
     * @param $message
     */
    protected function debug($message, $addToLog = true)
    {
        if ($addToLog) {
            $this->log[] = $message;
        }
        if (self::DEBUG) {
            print($message);
        }
    }

    /**
     * gets index of a column
     * @param $key
     * @return int
     */
    protected function getColumnIndex($key)
    {
        // if in column mappings
        if (array_key_exists($key, $this->columnMappings)) {
            $key = $this->columnMappings[$key];
        }

        $index = array_search($key, $this->headerData);
        return $index;
    }

    /**
     * returns value of a column
     *
     * @param $key
     * @param $data
     * @return null|string
     */
    protected function getColumnValue($key, $data)
    {
        if (($index = $this->getColumnIndex($key)) !== false) {
            return $data[$index];
        }
        return null;
    }

    /**
     * set value of a column
     *
     * @param $key
     * @param $data
     * @param $value
     * @throws \Exception
     */
    protected function setColumnValue($key, &$data, $value)
    {
        if (($index = $this->getColumnIndex($key)) !== false) {
            $data[$index] = $value;
        } else {
            throw new \Exception("column $key not set");
        }
    }

    /**
     * creates a logfile in import-files folder
     */
    public function createLogFile()
    {
        $file = fopen($this->extendFileName($this->file, '_log_' . time(), false), 'w');
        fwrite($file, implode("\n", $this->log));
        fclose($file);
    }
}
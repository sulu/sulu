<?php
/*
 * (c) MASSIVE ART WebServices GmbH
 */

namespace Sulu\Bundle\ContactBundle\Import;

use Sulu\Bundle\ContactBundle\Import\Exception\ImportException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

/**
 * @package Sulu\Bundle\ContactBundle\Import
 */
class DataCompleter
{

    const DEBUG = true;
    const API_CALL_LIMIT_PER_SECOND = 9;
    const API_CALL_SLEEP_TIME = 2;

    private static $geocode_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=';

    protected $options = array(
        'streetNumberSplit' => false,
        'delimiter' => ';',
        'enclosure' => '"',
    );

    protected $defaults = array();

    protected $headerData = array();

    protected $columnMappings = array(
        'country' => 'LKZ',
        'street' => 'strasse',
        'zip' => 'PLZ',
        'state' => 'state',
        'city' => 'Ort',
    );

    protected $log;
    protected $file;
    protected $limit;
    protected $currentRow;

    protected $lastApiCallTime;
    protected $lastApiCallCount;

    public function __construct()
    {
        $this->log = array();
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
    }
    public function setFile($file)
    {
        $this->file = $file;
    }

    protected function extendFileName($oldPath, $postfix, $keepExtension = true)
    {
        $parts = pathinfo($oldPath);
        $filename = $parts['dirname'] . '/' . $parts['filename'] . $postfix;
        if ($keepExtension) {
            $filename .=  '.' . $parts['extension'];
        }
        return $filename;
    }

    public function execute()
    {
        $outputFileName = $this->extendFileName($this->file,'_processed');
        $output = fopen($outputFileName, "w");

        $this->processCsvLoop(
            $this->file,
            function ($data, $row) use ($output) {

                $data = $this->completeAddress($data, $row);

                fputcsv($output, $data, $this->options['delimiter'], $this->options['enclosure']);
            },
            function ($data) use ($output) {
                fputcsv($output, $data, $this->options['delimiter'], $this->options['enclosure']);
            }
        );
        fclose($output);

        $this->createLogFile();
    }

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

                    $headerCallback($data, $row);
                } else {

                    if ($this->headerCount !== count($data)) {
                        throw new ImportException('The number of fields does not match the number of header values');
                    }

                    $callback($data, $row);

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

    protected function completeAddress($data, $row)
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
                $country = $this->getCountryByApiCall($street, $city, $zip, $state);

                $this->setColumnValue('country', $data, $country);
            }
        }

        return $data;
    }

    protected function getCountryByApiCall($street, $city, $zip, $state)
    {
        // limit api calls per second
        if ($this->lastApiCallTime == time()) {
            if ($this->lastApiCallCount >= static::API_CALL_LIMIT_PER_SECOND) {
                sleep(static::API_CALL_SLEEP_TIME);
                return $this->getCountryByApiCall($street, $city, $zip, $state);
            }
            $this->lastApiCallCount++;
        } else {
            $this->lastApiCallCount = 1;
            $this->lastApiCallTime = time();
        }

//        echo $this->lastApiCallCount. ' ';

        // remove null values
        $params = array_filter(
            array(
                $street,
                $city,
                $zip,
                $state
            )
        );
        // create string
        $params = implode(',', $params);
        // avoid spaces
        $urlparams = urlencode($params);

        $apiResult = json_decode(file_get_contents(static::$geocode_url . $urlparams));

//        $apiResult = json_decode($this->simulateApiCall());

        $results = $apiResult->results;

        if (count($results) === 0) {
            $this->debug(sprintf("ERROR: No valid country found at row %d (by api)", $this->currentRow, $params));
            return;
        }

        // take first result (if not unique)
        $result = $results[0];

        $country = $this->getCountryFromApiResult($result);

        if (!$country) {
            $this->debug(sprintf("ERROR: No country found in result for row %d", $this->currentRow));
            return;
        }

        //
        if (count($results) >1) {
            $this->debug(
                sprintf("Non unique country result at row %d! chose countrycode %s (params: %s)",
                    $this->currentRow,
                    $country,
                    $params
                )
            );
        }

        return $country;
    }

    protected function getCountryFromApiResult($result)
    {
        foreach($result as $value) {
            foreach ($value as $resultBlock) {
                if ($resultBlock->types[0] === 'country') {
                    return $resultBlock->short_name;
                }
            }
        }
        return null;
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

    protected function getColumnIndex($key)
    {
        // if in column mappings
        if (array_key_exists($key, $this->columnMappings)) {
            $key = $this->columnMappings[$key];
        }

        $index = array_search($key, $this->headerData);
        return $index;
    }

    protected function getColumnValue($key, $data)
    {
        if (($index = $this->getColumnIndex($key)) !== false) {
            return $data[$index];
        }
        return null;
    }

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

    private function simulateApiCall()
    {
        return '
{
   "results" : [
      {
         "address_components" : [
            {
               "long_name" : "Wien",
               "short_name" : "Wien",
               "types" : [ "locality", "political" ]
            },
            {
               "long_name" : "Wien",
               "short_name" : "Wien",
               "types" : [ "administrative_area_level_1", "political" ]
            },
            {
               "long_name" : "Österreich",
               "short_name" : "AT",
               "types" : [ "country", "political" ]
            }
         ],
         "formatted_address" : "Wien, Österreich",
         "geometry" : {
            "bounds" : {
               "northeast" : {
                  "lat" : 48.3230999,
                  "lng" : 16.5774999
               },
               "southwest" : {
                  "lat" : 48.1182699,
                  "lng" : 16.1826199
               }
            },
            "location" : {
               "lat" : 48.2081743,
               "lng" : 16.3738189
            },
            "location_type" : "APPROXIMATE",
            "viewport" : {
               "northeast" : {
                  "lat" : 48.3230999,
                  "lng" : 16.5774999
               },
               "southwest" : {
                  "lat" : 48.1182699,
                  "lng" : 16.1826199
               }
            }
         },
         "types" : [ "locality", "political" ]
      },
      {
         "address_components" : [
            {
               "long_name" : "Vienna",
               "short_name" : "Vienna",
               "types" : [ "locality", "political" ]
            },
            {
               "long_name" : "Hunter Mill",
               "short_name" : "Hunter Mill",
               "types" : [ "administrative_area_level_3", "political" ]
            },
            {
               "long_name" : "Fairfax County",
               "short_name" : "Fairfax County",
               "types" : [ "administrative_area_level_2", "political" ]
            },
            {
               "long_name" : "Virginia",
               "short_name" : "VA",
               "types" : [ "administrative_area_level_1", "political" ]
            },
            {
               "long_name" : "USA",
               "short_name" : "US",
               "types" : [ "country", "political" ]
            }
         ],
         "formatted_address" : "Vienna, Virginia, USA",
         "geometry" : {
            "bounds" : {
               "northeast" : {
                  "lat" : 38.92182409999999,
                  "lng" : -77.24126509999999
               },
               "southwest" : {
                  "lat" : 38.8784939,
                  "lng" : -77.284763
               }
            },
            "location" : {
               "lat" : 38.9012225,
               "lng" : -77.2652604
            },
            "location_type" : "APPROXIMATE",
            "viewport" : {
               "northeast" : {
                  "lat" : 38.92182409999999,
                  "lng" : -77.24126509999999
               },
               "southwest" : {
                  "lat" : 38.8784939,
                  "lng" : -77.284763
               }
            }
         },
         "types" : [ "locality", "political" ]
      },
      {
         "address_components" : [
            {
               "long_name" : "Vienna",
               "short_name" : "Vienna",
               "types" : [ "locality", "political" ]
            },
            {
               "long_name" : "Wood County",
               "short_name" : "Wood County",
               "types" : [ "administrative_area_level_2", "political" ]
            },
            {
               "long_name" : "West Virginia",
               "short_name" : "WV",
               "types" : [ "administrative_area_level_1", "political" ]
            },
            {
               "long_name" : "USA",
               "short_name" : "US",
               "types" : [ "country", "political" ]
            }
         ],
         "formatted_address" : "Vienna, West Virginia, USA",
         "geometry" : {
            "bounds" : {
               "northeast" : {
                  "lat" : 39.3485379,
                  "lng" : -81.5074403
               },
               "southwest" : {
                  "lat" : 39.2960208,
                  "lng" : -81.55644099999999
               }
            },
            "location" : {
               "lat" : 39.3270191,
               "lng" : -81.54845779999999
            },
            "location_type" : "APPROXIMATE",
            "viewport" : {
               "northeast" : {
                  "lat" : 39.3485379,
                  "lng" : -81.5074403
               },
               "southwest" : {
                  "lat" : 39.2960208,
                  "lng" : -81.55644099999999
               }
            }
         },
         "types" : [ "locality", "political" ]
      }
   ],
   "status" : "OK"
}
';
    }
}
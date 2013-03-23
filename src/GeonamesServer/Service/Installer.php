<?php
namespace GeonamesServer\Service;

use Zend\ServiceManager\ServiceLocatorInterface;

class Installer
{
    const GEONAMES_DUMP_URL = 'http://download.geonames.org/export/dump/';
    const ALL_COUNTRY_FILE  = 'allCountries.zip';
    const ALTERNATE_NAMES_FILE  = 'alternateNames.zip';
    const CACHE_KEY_ALTERNATE_NAMES = 'alternate_names';

    /**
     * Countries load
     * @var array|string $countries
     *      "FR" or array("FR", "US")
     */
    protected $countries = null;

    /**
     * Translate name enable or not
     * @var boolean
     */
    protected $translateName = false;

    /**
     * @var ServiceLocatorInterface
     */
    protected $sm = null;

    /**
     * Altername of geonameid
     * @var array
     */
    protected $alternateNames = array();

    /**
     * Ranked filename
     * @var string
     */
    protected $rankedFilename = null;

    /**
     * Geonames feature code allowed
     * @var array
     */
    protected $featureCode = array(
        'countries' => array(
            'PCL', 'PCLD', 'PCLF', 'PCLI',
            'PCLIX', 'PCLS'
        ),
        'admins' => array(
            'ADM1', 'ADM2'
        ),
        'cities' => array(
            'PPL', 'PPLA', 'PPLA2', 'PPLA3', 'PPLA4', 'PPLC', 'PPLX'
        )
    );

    /**
     * Local path of geonames files
     * @var string
     */
    protected $dataLocalPath = null;
    public function getDataLocalPath()
    {
        return $this->dataLocalPath;
    }

    /**
     * Constructor
     * @param array $config
     * @param ServiceLocatorInterface $sm
     */
    public function __construct($config, ServiceLocatorInterface &$sm)
    {
        if (!isset($config['countries']) || empty($config['countries']) || $config['countries'] == 'all') {
            $this->countries = array(substr(self::ALL_COUNTRY_FILE, 0, strpos(self::ALL_COUNTRY_FILE, '.')));
        } elseif (is_string($config['countries'])) {
            $this->countries = array(strtoupper($config['countries']));
        } elseif (is_array($config['countries'])) {
            $this->countries = array_map('strtoupper', $config['countries']);
        }

        $this->translateName = $config['translateName'];
        $this->dataLocalPath = $config['dataLocalPath'];
        $this->sm = &$sm;
    }

    /**
     * Get alternateNames of geonameid
     * @param string $geonameId
     * @return array
     */
    public function getAlternateNames($geonameId = null)
    {
        // Load alternateNames array if not yet
        if (empty($this->alternateNames)) {
            $cache = $this->sm->get('geonamesCache');
            $cacheKey = self::CACHE_KEY_ALTERNATE_NAMES . (int)$this->translateName;

            if (!$this->alternateNames = $cache->getItem($cacheKey)) {
                $files[] = $this->dataLocalPath . DS . substr(self::ALTERNATE_NAMES_FILE, 0, strpos(self::ALTERNATE_NAMES_FILE, '.')) . '.txt';
                $files[] = $this->dataLocalPath . DS . 'customAlternateNames.txt';

                foreach ($files as $file) {
                    if (file_exists($file)) {
                        $fp = fopen($file, 'r');

                        // Build array alternateNames
                        $line = null;
                        $langs = null;
                        while (!feof($fp)) {
                            $line = explode("\t", fgets($fp));
                            $line = array_map('trim', $line);
                            $langs = array();

                            if (count($line) > 6) {
                                if ($line[2] == 'post') {
                                    if ($line[4] == 1 || empty($this->alternateNames[$line[1]]['zipcode'])) {
                                        $this->alternateNames[$line[1]]['zipcode'] = $line[3];
                                    }
                                } elseif (empty($line[2]) && $line[4]) {
                                    $this->alternateNames[$line[1]]['name'] = $line[3];
                                } elseif ($this->translateName
                                          && preg_match('(^[a-z]{2}$)', $line[2])
                                          && !in_array($line[2], $langs)
                                          && $line[4] == 1
                                ) {
                                    $this->alternateNames[$line[1]]['langs'][$line[2]] = $line[3];
                                    $langs[] = $line[2];
                                }
                            }
                        }
                        fclose($fp);
                    }
                }
                $cache->setItem($cacheKey, $this->alternateNames);
            }
        }

        // Load cache
        return $geonameId && isset($this->alternateNames[$geonameId]) ? $this->alternateNames[$geonameId] : false;
    }

    /**
     * Get urls to download files
     * @return array
     */
    public function getUrlDownloadFiles()
    {
        // Create directory if not yet
        if (!file_exists($this->dataLocalPath)) {
            mkdir($this->dataLocalPath);
            mkdir($this->dataLocalPath . DS . 'cache');
        } elseif (!file_exists($this->dataLocalPath . DS . 'cache')) {
            mkdir($this->dataLocalPath . DS . 'cache');
        }

        // Generate URLs array
        $urls = array('alternateNames' => self::GEONAMES_DUMP_URL . self::ALTERNATE_NAMES_FILE);
        foreach ($this->countries as &$country) {
            $urls[$country] = self::GEONAMES_DUMP_URL . $country . '.zip';
            $this->throwExceptionIfUrlCountryNoExist($urls[$country]);
        }

        return $urls;
    }

    /**
     * Sort countries files in ranked file
     * @return string Ranked filename
     */
    public function getRankedFilename()
    {
        $splitDirectory = $this->getDataLocalPath() . DS . substr(md5(implode('', $this->countries)), 0, 12);

        // Test if countries files already sort
        if (file_exists($this->rankedFilename = $this->dataLocalPath . DS . basename($splitDirectory) . '.txt')) {
            return $this->rankedFilename;
        }

        // Create directory
        !file_exists($splitDirectory) ?: $this->rrmdir($splitDirectory);
        mkdir($splitDirectory);

        // Create writers
        $writers = array();
        foreach($this->featureCode as $group => &$featureCodes) {
            if ($group == 'admins') {
                foreach ($featureCodes as $featureCode) {
                    $writers[';'.$featureCode.';'] = fopen($splitDirectory . DS . $featureCode . '.txt', 'w+');
                }
            } else {
                $writers[';'.implode(';', $featureCodes).';'] = fopen($splitDirectory . DS . $group . '.txt', 'w+');
            }
        }

        // Split files countries
        $writersKeys = array_keys($writers);
        foreach ($this->countries as &$country) {
            $fp = fopen($this->dataLocalPath . DS . $country . '.txt', 'r');
            $line = null;

            while (!feof($fp)) {
                $line = array_map('trim', explode("\t", $l = fgets($fp)));
                if (count($line) == 19) {
                    if ($line[14] != 0 && ($line[6] == 'P' || $line[6] == 'A')) {
                        $key = preg_grep('(;'.$line[7].';)', $writersKeys);
                        !$key ?: fwrite($writers[array_shift($key)], $l);
                    }
                }
            }
        }

        // Assemble split files in correct order
        $item = 0; $fp = null;
        $countriesFile = $splitDirectory . DS . 'countries.txt';
        foreach ($writers as $key => &$writer) {
            if (++$item == 1) {
                fclose($writer);
                $fp = fopen($countriesFile, 'a');
            } else {
                rewind($writer);
                while (!feof($writer)) {
                    fwrite($fp, fgets($writer));
                }
                fclose($writer);
            }
        }

        fclose($fp);

        // Delete folder split files
        rename($countriesFile, $this->rankedFilename);
        $this->rrmdir($splitDirectory);

        return $this->rankedFilename;
    }

    /**
     * Import data in elasticsearcg index
     */
    public function importData()
    {
        $fp = fopen($this->rankedFilename, 'r');
        $adminsCodeData = array();
        $elasticsearch = $this->sm->get('GeonamesServer\Service\Elasticsearch');
        $elasticsearch->deleteAll();

        while (!feof($fp)) {
            $line = array_map('trim', explode("\t", $l = fgets($fp)));

            // Build data and insert row
            if (count($line) == 19) {
                $data = array(
                    'geonameid' => $line[0],
                    'country'   => $line[8],
                    'name'      => $line[1],
                    'latitude'  => $line[4],
                    'longitude' => $line[5],
                    'population'=> (int)$line[14],
                    'timezone'  => $line[17],
                );

                // Add languages name and postal code with alternateNames
                if ($alternateNames = $this->getAlternateNames($line[0])) {
                    if (isset($alternateNames['zipcode'])) {
                        $data['zipcode'] = $alternateNames['zipcode'];
                    }
                    if (isset($alternateNames['langs'])) {
                        foreach ($alternateNames['langs'] as $code => &$alternateName) {
                            $data['language_name'][$code] = $alternateName;
                        }
                    }
                    if (isset($alternateNames['name'])) {
                        $data['name'] = $alternateNames['name'];
                    }
                }

                // Feature class is A
                if ($line[6] == 'A') {
                    $data['type'] = (strpos($line[7], 'ADM') !== false) ? $line[7] : 'country';
                    $code = $this->getCode($line, $data['type']);

                    // Add parents data
                    if ($parents = $this->getParentsCode($code)) {
                        foreach ($parents as &$parentCode) {
                            $parent = &$adminsCodeData[$parentCode];
                            $data['parents'][] = $parent;
                        }
                    }

                    // Insert collection
                    $elasticsearch->addCity($data);

                    // Create data parents
                    $parentData = array();
                    $fields = array('geonameid', 'name', 'language_name', 'country', 'type');
                    foreach ($fields as &$field) {
                        if (isset($data[$field])) {
                            $parentData[$field] = $data[$field];
                        }
                    }
                    $adminsCodeData[$code] = $parentData;
                }

                // Feature class is P => add your parent ADM and yours parents
                elseif ($line[6] == 'P') {
                    $data['type'] = 'city';
                    $code = $this->getCode($line, $data['type']);

                    if ($parents = $this->getParentsCode($code, true)) {
                        foreach ($parents as $parentCode) {
                            if (isset($adminsCodeData[$parentCode])) {
                                $parentData = $adminsCodeData[$parentCode];
                                $data['parents'][] = $parentData;
                            }
                        }
                    }

                    $elasticsearch->addCity($data);
                }
            }
        }
        fclose($fp);
    }

    /**
     * Download remote file
     * @param string $remoteUrl
     * @param string $localPath
     */
    public function downloadRemoteFile($remoteUrl, $localPath)
    {
        $ch = curl_init($remoteUrl);
        $fp = fopen($localPath, 'w');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    /**
     * Return code of geoname line => combine column country, adm1, adm2 => ex : FR.B9.53
     * @param array $line
     * @param string $type
     * @return string
     */
    protected function getCode($line, $type)
    {
        $code = null;

        if ($type == 'country') $code = $line[8];
        elseif (strpos($line[7], 'ADM') !== false) {
            $level = str_replace('ADM', '', $line[7]);
            $code = $line[8];
            for ($i=0; $i<$level; $i++) {
                $code .= '.'.$line[10+$i];
            }
        } elseif($line[6] == 'P') {
            $code = $line[8];
            for ($i=10; $i<12; $i++) {
                if (isset($line[$i]) && !empty($line[$i])) {
                    $code .= '.'.$line[$i];
                } else break;
            }
        }

        return $code;
    }

    /**
     * Returns parents code
     * @param string $code
     * @param bool $includeCurrentCode
     * @return array|null
     */
    protected function getParentsCode($code, $includeCurrentCode = false)
    {
        $codes = explode('.', $code);
        $includeCurrentCode ?: array_pop($codes);
        $parentsCodes = array();
        foreach ($codes as $_code) {
            $parentsCodes[] = implode('.', $codes);
            array_pop($codes);
        }

        return $parentsCodes;
    }

    /**
     * Throw exception if URL country no exist
     * @param string $url
     * @throws RuntimeException
     */
    protected function throwExceptionIfUrlCountryNoExist($url)
    {
        $name = basename($url);
        $error = !preg_match('(^[A-Z]{2}.zip$)', $name);
        if ($url != self::ALL_COUNTRY_FILE) {
            return true;
        }

        if ($error === false) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_NOBODY, true);
            $result = curl_exec($curl);

            if ($result !== false) {
                $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $error = $statusCode != 200;
            }

            curl_close($curl);
        }

        if ($error) {
            throw new \RuntimeException('Country "'.substr($name, 0, strpos($name, '.')).'" no available, you can see all countries here : ' . self::GEONAMES_DUMP_URL);
        }
    }

    /**
     * Recursively remove a directory
     * @param string $dir
     * @return bool true on success or false on failure.
     */
    protected function rrmdir($dir)
    {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->rrmdir($dir.DS.$item)) return false;
        }
        return rmdir($dir);
    }
}

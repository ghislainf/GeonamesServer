<?php
namespace GeonamesServer\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface;

class ConsoleController extends AbstractActionController
{
    public function installAction()
    {
        $timer = time();
        $installer = $this->getServiceLocator()->get('GeonamesServer\Service\Installer');
        $elasticsearch = $this->getServiceLocator()->get('GeonamesServer\Service\Elasticsearch');
        $zip = new \ZipArchive;

        // Get console
        $console = $this->getServiceLocator()->get('console');
        if (!$console instanceof Console) {
            throw new \RuntimeException('Cannot obtain console adapter. Are we running in a console ?');
        }

        echo "+----------------------------------------------------+\n" .
             "|             Run install GeonameServer              |\n" .
             "+----------------------------------------------------+\n";

        // Run test and show results
        $console->write("Test your environment : \n");
        if ($this->showResultTest('    PHP extension "zip" loaded   ', extension_loaded('zip'), $console)
         && $this->showResultTest('    PHP extension "curl" loaded  ', extension_loaded('curl'), $console)
         && $this->showResultTest('    PHP extension "http" loaded  ', extension_loaded('http'), $console)
         && $this->showResultTest('    Elasticsearch connection     ', $elasticsearch->testService(), $console)
        ) {
            $console->write("==> Your environment are ready\n\n");
        } else {
            $console->write("\n[NOT OK] ", ColorInterface::RED);
            $console->write("Your environment is not available\n\n");
            exit();
        }

        $urls = $installer->getUrlDownloadFiles();
        $localPath = $installer->getDataLocalPath();

        $console->write("Processing download & unzip geonames files : \n");
        foreach ($urls as &$url) {
            $name = basename($url);
            $path = $localPath . DS . $name;

            // Test file exist
            if (file_exists($localPath . DS . substr($name, 0, strpos($name, '.')) . '.txt')) {
                $console->write('    File "'.$name.'" already download, process continue ... ');
                $console->write("[OK]\n", ColorInterface::GREEN);
                continue;
            }

            // Download file
            $console->write('    Download file "'.$name.'" ... ');
            $installer->downloadRemoteFile($url, $path);
            $console->write("[OK]\n", ColorInterface::GREEN);

            // Unzip file
            $console->write('    Unzip file "'.$name.'" ... ');
            if ($zip->open($path) === true) {
                $zip->extractTo($localPath);
                $zip->close();

                unlink($path);
                $console->write("[OK]\n", ColorInterface::GREEN);
            } else {
                $console->write("[NOT OK]\n", ColorInterface::RED);
                $console->write('Exit install ...');
            }
        }

        // Delete files useless
        $this->deleteFiles(array(
            $localPath . DS . 'iso-languagecodes.txt',
            $localPath . DS . 'readme.txt'
        ));

        // Caching AlternateName
        $console->write("\nCaching geonames AlternateName ...");
        $installer->getAlternateNames();
        $console->write("[OK]\n", ColorInterface::GREEN);

        // Generate ranked file
        $console->write("Sort countries files for optimize indexing ... ");
        $installer->getRankedFilename();
        $console->write("[OK]\n", ColorInterface::GREEN);

        // Import data
        $console->write("Import data to elasticsearch index ... ");
        $installer->importData();
        $console->write("[OK]\n\n", ColorInterface::GREEN);

        // Stats
        $console->write("------------------------------------------------------\n");
        $console->write("[Success] ", ColorInterface::GREEN);
        $console->write("Install complete\n");
        $console->write("------------------------------------------------------\n");
        $console->write("Memory usage : " . round(memory_get_peak_usage()/1048576) . " Mo\n");
        $console->write("Execution time : " . (time()-$timer) . " secs\n");
        $console->write("------------------------------------------------------\n");
    }

    protected function showResultTest($message, $bool, Console &$console)
    {
        // Print status
        $console->write($message);
        $bool ? $console->write('[OK]', ColorInterface::GREEN)
              : $console->write('[NOT OK]', ColorInterface::RED);
        $console->writeLine();

        return $bool;
    }

    /**
     * Deletes files and test if exist before
     * @param array $filesPath
     */
    protected function deleteFiles($filesPath)
    {
        foreach ($filesPath as &$files) {
            if (file_exists($files)) {
                unlink($files);
            }
        }
    }
}
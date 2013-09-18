<?php

/*
 * Sample command-line interface using Zend_Config and https://github.com/nategood/commando
 */

require_once 'Zend/Config.php';
require_once 'vendor/autoload.php';
require_once 'Asis/Common/InputDataProvider.php';
require_once 'Asis/Tester.php';

class Asis_Commander
{
    private $_asisTester;

    public function __construct($argv)
    {
        $config = new Zend_Config_Ini('asisConfig.ini');
        $cmd = new Commando\Command($argv);

        $this->_asisTester = new Asis_Tester_Tester(array(
            'inputDataPath' => $config->inputPath,
            'outputDataPath' => $config->outputPath,
            'inputExtension' => $config->inputExtension,
            'outputExtension' => $config->outputExtension)
        );

        $cmd->option('r')->boolean()->aka('run')->describedAs('Run testing');
        $cmd->option('s')->boolean()->aka('statistics')->describedAs('Show statistics');
        $cmd->option('c')->boolean()->aka('clean')->describedAs('Clean up');
        $cmd->option('a')->boolean()->aka('approve')->describedAs('Approve received tests');

        if ($cmd['run'])        $this->run();
        if ($cmd['statistics']) $this->showStatistics();
        if ($cmd['clean'])      $this->clean();
        if ($cmd['approve'])    $this->approve($config->inputPath, $config->outputPath);
    }

    private function showStatistics()
    {
        $inputDataAnalytics = $this->_asisTester->showStatistics();
        echo "Total amount of functions in tests: " . $inputDataAnalytics['totalCountOfFunctions'] . "\n";
        echo "Functions without datasets:         " . $inputDataAnalytics['countOfFunctionsWithoutDatasets'] . "\n";
        return;
    }

    private function run()
    {
        return $this->_asisTester->run();
    }

    // Should clean the failed tests files
    private function clean()
    {
        return;
    }

    private function approve()
    {
        return $this->_asisTester->approve();
    }
}


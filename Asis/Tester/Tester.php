<?php

require_once 'Approvals.php';
require_once 'Asis/Common/InputDataProvider.php';
require_once 'Asis/Common/Serializer/XML.php';
require_once 'Asis/Common/Serializer/Native.php';
require_once 'Asis/Common/Util.php';

class Asis_Tester_Tester
{
    private $_serializer = null;
    private $_inputDataProvider = null;
    private $_inputDataPath = '/var/www/zavg/tests/inputs';
    private $_inputExtension = 'xml';
    private $_outputDataPath = '/var/www/zavg/tests/outputs';
    private $_outputExtension = 'xml';
    private $_assertions = array();

    public function __construct($options)
    {
        if ($options) {
            foreach ($options as $key => $value) {
                if (array_key_exists("_" . $key, get_object_vars($this)))
                    $this->{"_" . $key} = $value;
            }
        }
        $this->_serializer = $this->getSerializer();
        $this->_inputDataProvider = $this->getInputDataProvider();
    }

    private function getSerializer()
    {
        if (!$this->_serializer)
            $this->_serializer = new Asis_Common_Serializer_Native();
        return $this->_serializer;
    }

    private function getInputDataProvider()
    {
        if (!$this->_inputDataProvider)
            $this->_inputDataProvider = new Asis_Common_InputDataProvider(
                array('fullTemplatesPath' => $this->_inputDataPath,
                    "inputExtension" => $this->_inputExtension)
            );
        return $this->_inputDataProvider;
    }

    public function run()
    {
        $testsCount = 0;
        $dp = $this->getInputDataProvider();
        $fullInputDataArray = $dp->getFullInputData();
        $classes = $dp->getClasses();
        foreach ($classes as $className) {
            $functions = $dp->getFunctions($className);
            foreach ($functions as $functionName) {
                foreach ($fullInputDataArray[$className][$functionName] as $dataset) {
                    $result = call_user_func_array(
                        array(new $className, $functionName),
                        $dataset
                    );
                    try {
                        Asis_Tester_Approvals::approve(
                            $this->getSerializer()->serialize(array($result)),
                            array('class' => $className,
                                'function' => "$functionName-" . md5(serialize($dataset)),
                                'path' => $this->_outputDataPath .
                                    $dp->getRelationalPath($className)
                            ),
                            $this->_outputExtension
                        );
                    } catch (Exception $e) {
                        $this->_assertions[] = $e->getMessage();
                    }
                    $testsCount++;
                }
            }
        }
        echo "$testsCount tests executed\n";
        echo count($this->_assertions) . " assertions\n";
        foreach ($this->_assertions as $assertion)
            echo $assertion . "\n";

    }

    public function approve()
    {
        $filesCount = 0;

        // $path = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . (isset($argv[1]) ? $argv[1] : "outputs"));
        $allFiles = Asis_Common_Util::getFileNames($this->_outputDataPath, array('Asis_Tester_Tester', '_isOutput'));
        foreach ($allFiles as $filename) {
            $filesCount++;
            rename($filename, str_replace(
                    Asis_Tester_Approvals::STATUS_RECEIVED,
                    Asis_Tester_Approvals::STATUS_APPROVED,
                    $filename
                )
            );
            echo "$filename renamed\n";
        }

        echo "$filesCount files renamed\n";
    }

    public function showStatistics()
    {
        return $this->getInputDataProvider()->getInputDataAnalytics();
    }

    public static function _isOutput($fileName)
    {
        return preg_match('/(' . Asis_Tester_Approvals::STATUS_RECEIVED . ')/', $fileName);
    }

}
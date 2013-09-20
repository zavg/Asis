<?php

require_once 'Tester/Approvals.php';
require_once 'Common/InputDataProvider.php';
require_once 'Common/Serializer/XML.php';
require_once 'Common/Serializer/Native.php';
require_once 'Common/Util.php';

class Asis_Tester
{
    private $_config = array(
        'inputDataPath' => 'tests/inputs',
        'inputExtension' => 'xml',
        'outputDataPath' => 'tests/output',
        'outputExtension' => 'txt'
    );

    private $_serializer = null;
    private $_inputDataProvider = null;

    private $_assertions = array();

    public function __construct($options = null)
    {
        if ($options) {
            foreach ($options as $key => $value) {
                if (array_key_exists($key, $this->_config))
                    $this->_config[$key] = $value;
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
                array(
                    'inputDataPath' => $this->_config['inputDataPath'],
                    "inputExtension" => $this->_config['inputExtension']
                )
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
                    if (!is_array($dataset))
                        $dataset = array($dataset);
                    $result = call_user_func_array(
                        array(new $className, $functionName),
                        $dataset
                    );
                    try {
                        Asis_Tester_Approvals::approve(
                            $this->getSerializer()->serialize(array($result)),
                            array('class' => $className,
                                'function' => "$functionName-" . md5(serialize($dataset)),
                                'path' => $this->_config['outputDataPath'] .
                                    $dp->getRelationalPath($className)
                            ),
                            $this->_config['outputExtension']
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

        $allFiles = Asis_Common_Util::getFileNames(
            $this->_config['outputDataPath'],
            array('Asis_Tester', '_isOutput')
        );

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

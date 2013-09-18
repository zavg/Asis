<?php

require_once 'Interfaces/InputDataProviderInterface.php';
require_once 'Serializer/XML.php';
require_once 'Util.php';

class Asis_Common_InputDataProvider implements Asis_Common_Interfaces_InputDataProviderInterface
{
    private $_config = array(
        'applicationPath' => '',
        'inputDataPath'  => 'tests/inputs',
        'inputExtension' => 'xml'
    );

    private $_fullInputData = array();
    private $_fileNamesArray = array();

    private $_serializer = null;

    public function __construct($options = null)
    {
        if ($options) {
            foreach ($options as $key => $value) {
                if (array_key_exists($key, $this->_config))
                    $this->_config[$key] = $value;
            }
        }
        $this->readFullInputData();
    }

    private function getSerializer()
    {
        if (!$this->_serializer)
            $this->_serializer = new Asis_Common_Serializer_XML();
        return $this->_serializer;
    }

    public function setInputDataPath($path)
    {
        $this->_config['inputDataPath'] = $path;
        return $this;
    }

    public function getInputDataPath()
    {
        return $this->_config['inputDataPath'];
    }

    public function setApplicationPath($path)
    {
        $this->_config['applicationPath'] = $path;
        return $this;
    }

    public function getApplicationPath()
    {
        return $this->_config['applicationPath'];
    }

    private function getInputExtension()
    {
        return $this->_config['inputExtension'];
    }

    public function setFileNamesArray($data)
    {
        $this->_fileNamesArray = $data;
        return $this;
    }

    public function setFullInputData($data)
    {
        $this->_fullInputData = $data;
        return $this;
    }

    public function getFullInputData()
    {
        return $this->_fullInputData;
    }

    public function getRelationalPath($mapperName)
    {
        return str_replace($this->getInputDataPath(), '', dirname($this->_fileNamesArray[$mapperName]));
    }

    public function getClasses()
    {
        return array_keys($this->_fullInputData);
    }

    public function getFunctions($className)
    {
        return array_keys($this->_fullInputData[$className]);
    }

    public function getDatasets($className, $functionName)
    {
        return $this->_fullInputData[$className][$functionName];
    }

    public function countDatasets($className, $functionName)
    {
        return count($this->getDatasets($className, $functionName));
    }

    public function addDataset($className, $functionName, $newDataset)
    {
        $fullInputDataArray = $this->_fullInputData;
        if ($fullInputDataArray)
            foreach ($fullInputDataArray[$className][$functionName] as $oldDataset) {
                if ($newDataset == $oldDataset) {
                    //throw new Exception('duplicated dataset');
                    return false;
                }
            }
        if (!array_key_exists($className, $fullInputDataArray)) {
            $reflection = new \ReflectionClass($className);
            $pathinfo = pathinfo($reflection->getFileName());
            if ($this->getApplicationPath()) {
                $this->_fileNamesArray[$className] = str_replace($this->getApplicationPath(), $this->getInputDataPath(),
                    ($pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . "." . $this->getInputExtension()));
            } else {
                $this->_fileNamesArray[$className] = $this->getInputDataPath() .
                    ($pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . "." . $this->getInputDataPath());
            }
        }
        $fullInputDataArray[$className][$functionName][] = $newDataset;
        $this->setFullInputData($fullInputDataArray);
        $serializedData = $this->getSerializer()->serialize(array($className => $fullInputDataArray[$className]));
        return $this->saveSerializedData($serializedData, $this->_fileNamesArray[$className]);
    }

    private function saveSerializedData($str, $fileName)
    {
        if (!file_exists(dirname($fileName)))
            mkdir(dirname($fileName), 0777, true);
        $fp = fopen($fileName, "w");
        $fwrite = fwrite($fp, $str);
        fclose($fp);
        if ($fwrite)
            return true;
        return false;
    }

    public function readFullInputData()
    {
        $fileNames = Asis_Common_Util::getFileNames($this->getInputDataPath());
        $fullInputData = array();
        $fileNamesArray = array();
        foreach ($fileNames as $fileName) {
            $uData = $this->getSerializer()->unserialize(file_get_contents($fileName));
            foreach ($uData as $class => $methods) {
                foreach ($methods as $method => $data) {
                    $fullInputData[$class][$method] = $data;
                }
                $fileNamesArray[$class] = $fileName;
            }
        }
        $this->setFullInputData($fullInputData);
        $this->setFileNamesArray($fileNamesArray);
        return;
    }

    public function getInputDataAnalytics()
    {
        $fullInputData = $this->getFullInputData();
        $listOfFunctionsWithoutDatasets = array();
        $totalCountOfFunctions = 0;
        $countOfFunctionsWithoutDatasets = 0;
        foreach ($fullInputData as $className => $functionsArray) {
            foreach ($functionsArray as $functionName => $datasetsArray) {
                $totalCountOfFunctions++;
                if ($datasetsArray == array()) {
                    $countOfFunctionsWithoutDatasets++;
                    $listOfFunctionsWithoutDatasets[$className][] = $functionName;
                }
            }
        }
        return array(
            'totalCountOfFunctions' => $totalCountOfFunctions,
            'listOfFunctionsWithoutDatasets' => $listOfFunctionsWithoutDatasets,
            'countOfFunctionsWithoutDatasets' => $countOfFunctionsWithoutDatasets
        );
    }


}
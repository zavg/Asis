<?php

require_once 'Asis/Common/Util.php';
require_once 'Asis/Common/Interfaces/InputDataProviderInterface.php';

class Asis_Common_InputDataProvider implements Asis_Common_Interfaces_InputDataProviderInterface
{
    private $_fullTemplatesPath = '/var/www/zavg/tests/inputs';
    private $_inputExtension = 'xml';

    private $_fullInputData = array();
    private $_fileNamesArray = array();

    private $_serializer = null;

    public function __construct($options = null)
    {
        if ($options) {
            foreach ($options as $key => $value) {
                if (array_key_exists("_" . $key, get_object_vars($this)))
                    $this->{"_" . $key} = $value;
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

    public function setFullTemplatesPath($path)
    {
        $this->_fullTemplatesDir = $path;
        return $this;
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
        return str_replace($this->_fullTemplatesPath, '', dirname($this->_fileNamesArray[$mapperName]));
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
        foreach ($fullInputDataArray[$className][$functionName] as $oldDataset) {
            if ($newDataset == $oldDataset) {
                //throw new Exception('duplicated dataset');
                return false;
            }
        }
        if (!array_key_exists($className, $fullInputDataArray)) {
            $reflection = new ReflectionClass($className);
            $pathinfo = pathinfo($reflection->getFileName());
            $this->_fileNamesArray[$className] = str_replace(APPLICATION_PATH, $this->_fullTemplatesPath,
                ($pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . "." .
                    $this->_inputExtension));
        }
        $fullInputDataArray[$className][$functionName][] = $newDataset;
        $this->setFullInputData($fullInputDataArray);
        $xmlStr = $this->getXML(array($className => $fullInputDataArray[$className]));
        return $this->saveXML($xmlStr, $this->_fileNamesArray[$className]);
    }

    private function getXML($dataArray)
    {
        return $this->getSerializer()->serialize($dataArray);
    }

    private function saveXML($xmlStr, $fileName)
    {
        if (!file_exists(dirname($fileName)))
            mkdir(dirname($fileName), 0777, true);
        $fp = fopen($fileName, "w");
        $fwrite = fwrite($fp, $xmlStr);
        fclose($fp);
        if ($fwrite)
            return TRUE;
        else
            return FALSE;
    }

    public function readFullInputData()
    {
        $fileNames = Asis_Common_Util::getFileNames($this->_fullTemplatesPath);
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
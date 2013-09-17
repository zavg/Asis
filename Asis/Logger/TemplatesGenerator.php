<?php

require_once 'XML/Serializer.php';
require_once 'Asis/Common/Util.php';

class Asis_Logger_TemplatesGenerator {

    private $_templatesDir = 'tests/inputs';
    private $_excludedMethods = array();
    private $_applicationPath = APPLICATION_PATH;

    public function __construct($options = null) {
        foreach ($options as $key => $value) {
            if (array_key_exists("_" . $key, get_object_vars($this)))
                $this->{"_" . $key} = $value;
        }
    }

    public function setTemplatesDir($dir) {
        $this->_templatesDir = $dir;
        return $this;
    }

    public function setExcludedMethods($methods) {
        $this->_excludedMethods = $methods;
        return $this;
    }

    public function setApplicationPath($path) {
        $this->_applicationPath = $path;
        return $this;
    }

    public function generateTemplateByMappers() {
        $errorsArray = array();
        $fileNames = Asis_Common_Util::getFileNames($this->_applicationPath, array('Asis_Logger_TemplatesGenerator', 'isMapper'));

        foreach ($fileNames as $fileName) {
            $xmlStr = $this->getXML($fileName);
            if ($this->saveXML($xmlStr, $fileName) !== TRUE)
                $errorsArray[] = $fileName;
        }

        if ($errorsArray == array())
            return TRUE;
        else
            return $errorsArray;
    }

    static function isMapper($fileName) {
        return preg_match('/^[A-Z][A-Za-z]*Mapper\.php$/', $fileName);
    }

    private function getXML($fileName) {
        $classNames = $this->fileGetPhpClasses($fileName);
        $resultArray = array();

        foreach ($classNames as $className) {
            if (class_exists($className)) {
                $reflectionClass = new ReflectionClass($className);
                foreach ($reflectionClass->getMethods() as $method) {
                    if ($method->class == $className and !$this->isExcludedMethod($method->name)) {
                        $resultArray[$className][$method->name] = array(array());
                    }
                }
            }
        }

        $options = array(
            XML_SERIALIZER_OPTION_INDENT => "\t",
            XML_SERIALIZER_OPTION_LINEBREAKS => "\n",
            XML_SERIALIZER_OPTION_ROOT_NAME => 'rdf:RDF',
            XML_SERIALIZER_OPTION_DEFAULT_TAG => 'item',
            XML_SERIALIZER_OPTION_TYPEHINTS => true
        );
        $serializer = new XML_Serializer($options);
        $serializer->serialize($resultArray);
        $xmlStr = $serializer->getSerializedData();

        return $xmlStr;
    }

    private function fileGetPhpClasses($filepath) {
        $phpCode = file_get_contents($filepath);
        $classes = $this->getPhpClasses($phpCode);
        return $classes;
    }

    private function getPhpClasses($phpCode) {
        $classes = array();
        $tokens = token_get_all($phpCode);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS
                    && $tokens[$i - 1][0] == T_WHITESPACE
                    && $tokens[$i][0] == T_STRING
            ) {

                $className = $tokens[$i][1];
                $classes[] = $className;
            }
        }
        return $classes;
    }
    
    private function isExcludedMethod($method) {
        return in_array($method, $this->_excludedMethods);
    }

    private function saveXML($xmlStr, $fileName) {
        $fileName = str_replace("/application/", "/" . $this->_templatesDir . "/", $fileName);
        $fileName = str_replace(".php", ".xml", $fileName);
        mkdir(dirname($fileName), 0777, true);
        $fp = fopen($fileName, "w");
        $fwrite = fwrite($fp, $xmlStr);
        fclose($fp);
        if ($fwrite)
            return TRUE;
        else
            return FALSE;
    }

}
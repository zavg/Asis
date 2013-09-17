<?php

require_once 'Asis/Common/InputDataProvider.php';
require_once 'Zend/Config.php';

class Asis_Logger_Logger
{
    private $_inputDataProvider;

    public function __construct($options = null)
    {
        $this->_inputDataProvider = new Asis_Common_InputDataProvider(
            array(
                'inputDataPath' => isset($options['inputPath']) ? $options['inputPath'] : "/var/www/zavg/inputs",
                'inputExtension' => isset($options['inputExtension']) ? $options['inputExtension'] : "xml",
            )
        );
    }

    public function log($className, $functionName, $data)
    {
        $this->_inputDataProvider->addDataset($className, $functionName, $data);
    }

}
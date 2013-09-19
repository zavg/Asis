<?php


require_once dirname(__FILE__) . '/Common/InputDataProvider.php';

class Asis_Logger
{
    private $_inputDataProvider;

    public function __construct($options = null)
    {
        $this->_inputDataProvider = new Asis_Common_InputDataProvider(
            array(
                'applicationPath' => isset($options['applicationPath']) ? $options['applicationPath'] : "",
                'inputDataPath' => isset($options['inputDataPath']) ? $options['inputDataPath'] : "tests/inputs",
                'inputExtension' => isset($options['inputExtension']) ? $options['inputExtension'] : "xml",
            )
        );
    }

    public function log($className, $functionName, $inputData)
    {
        $this->_inputDataProvider->addDataset($className, $functionName, $inputData);
    }

}

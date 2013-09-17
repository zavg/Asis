<?php

require_once "Asis/Common/Interfaces/Serializer.php";
require_once 'XML/Serializer.php';
require_once 'XML/Unserializer.php';

class Asis_Common_Serializer_XML implements Asis_Common_Interfaces_Serializer
{
    public $_serializer = null;
    public $_unserializer = null;

    public function __construct()
    {
        $options = array(
            XML_SERIALIZER_OPTION_INDENT => '    ',
            XML_SERIALIZER_OPTION_LINEBREAKS => "\n",
            XML_SERIALIZER_OPTION_DEFAULT_TAG => 'unnamedItem',
            XML_SERIALIZER_OPTION_TYPEHINTS => true
        );

        $this->_serializer = new XML_Serializer($options);
        $this->_unserializer = new XML_Unserializer();
    }

    function serialize($object)
    {
        $this->_serializer->serialize($object);
        return $this->_serializer->getSerializedData();
    }

    function unserialize($data)
    {
        $this->_unserializer->unserialize($data);
        return $this->_unserializer->getUnserializedData();
    }

}


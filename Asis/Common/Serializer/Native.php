<?php

require_once __DIR__ . "/../Interfaces/SerializerInterface.php";

class Asis_Common_Serializer_Native implements Asis_Common_Interfaces_SerializerInterface
{
    public function __construct()
    {
    }

    function serialize($object)
    {
        return serialize($object);
    }

    function unserialize($data)
    {
        return unserialize($data);
    }

}


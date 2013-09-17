<?php

require_once "Asis/Common/Interfaces/Serializer.php";

class Asis_Common_Serializer_Native implements Asis_Common_Interfaces_Serializer
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


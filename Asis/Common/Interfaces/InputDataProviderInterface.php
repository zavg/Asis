<?php

interface Asis_Common_Interfaces_InputDataProviderInterface  {

    function getClasses();

    function getFunctions($className);

    function getDatasets($className, $functionName);

    function countDatasets($className, $functionName);

    function addDataset($className, $functionName, $dataset);
}
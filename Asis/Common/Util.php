<?php

class Asis_Common_Util
{
    public static function getFileNames($path, $criterion = 'alwaysTrueCriterion')
    {
        $allFiles = array();
        self::getListFiles($path, $allFiles, $criterion);
        return $allFiles;
    }

    private static function getListFiles($folder, &$allFiles, $criterion)
    {
        if (file_exists($folder)) {
            $fp = opendir($folder);
            while ($cv_file = readdir($fp)) {
                if (is_file($folder . "/" . $cv_file) and
                    (is_array($criterion) ? call_user_func($criterion, $cv_file) : self::$criterion($cv_file))
                ) {
                    $allFiles[] = $folder . "/" . $cv_file;
                } elseif ($cv_file != "." && $cv_file != ".." && is_dir($folder . "/" . $cv_file)) {
                    Asis_Common_Util::getListFiles($folder . "/" . $cv_file, $allFiles, $criterion);
                }
            }
            closedir($fp);
        } else {
            mkdir($folder, 0777, true);
        }
    }

    private static function alwaysTrueCriterion()
    {
        return true;
    }

}
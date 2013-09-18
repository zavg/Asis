<?php

class Asis_Tester_Approvals
{
    const STATUS_RECEIVED = "received";
    const STATUS_FAILED = "failed";
    const STATUS_APPROVED = "approved";

    const PASSED_RETURN_VALUE = 1;
    const APPROVED_RETURN_VALUE = 2;

    private static function getDirectory($dir)
    {
        if (!is_dir($dir))
            mkdir($dir, 0777, true);
        return $dir;
    }

    private static function getFilename($call, $status, $extension)
    {
        return self::getDirectory($call['path'] . DIRECTORY_SEPARATOR . $call['class']). DIRECTORY_SEPARATOR .
            $call['function'] . '.' . $status . '.' . $extension;
    }

    public static function approve($resultStr, $call, $extension = "xml")
    {
        $approvedFilename = self::getFilename($call, self::STATUS_APPROVED, $extension);
        if (!file_exists($approvedFilename)) {
            $approvedContents = null;
            echo self::getFilename($call, self::STATUS_RECEIVED, $extension) . "\n";
            file_put_contents(self::getFilename($call, self::STATUS_RECEIVED, $extension), $resultStr);
            return self::APPROVED_RETURN_VALUE;
        } else
            $approvedContents = file_get_contents($approvedFilename);

        if (!($approvedContents === $resultStr)) {
            file_put_contents(self::getFilename($call, self::STATUS_FAILED, $extension), $resultStr);
            throw new Exception('Approval File Mismatch: results does not match contents of ' . $approvedFilename);
        }
        return self::PASSED_RETURN_VALUE;

    }
}
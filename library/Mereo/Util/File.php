<?php
/**
 * Utilities for files.
 *
 * @version 1.0.0
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2011, Mereo GmbH
 *
 */

class Mereo_Util_File {
    /**
     * @param $size int the filesize in bytes
     * @return string rounded filesize with unit
     * @copyright joaoptm78@gmail.com from php.net/manual
     */
    public static function formatBytes($size) {
        $units = array(' B', ' KB', ' MB', ' GB', ' TB');
        for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
        return round($size, 2).$units[$i];
    }
}
?>
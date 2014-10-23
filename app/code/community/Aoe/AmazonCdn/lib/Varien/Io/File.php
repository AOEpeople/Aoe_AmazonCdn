<?php

class Varien_Io_File extends Magento\Varien_Io_File
{

    /**
     * Check source is file.
     *
     * @param string $src
     * @return bool
     */
    protected function _checkSrcIsFile($src)
    {
        $result = false;

        // Fix for bug in core:
        // both is_readable() and is_file() emit E_WARNING if there is a null byte in $src
        if (is_string($src) && @is_readable($src) && @is_file($src)) {
            $result = true;
        }

        return $result;
    }

}

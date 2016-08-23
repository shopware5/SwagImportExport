<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\FileIO\Encoders;

/**
 * CsvEncoder
 */
class CsvEncoder extends \Shopware_Components_Convert_Csv
{
    /**
     * @param $line
     * @param $keys
     * @return string
     */
    public function _encode_line($line, $keys)
    {
        $csv = '';

        if (isset($this->sSettings['fieldmark'])) {
            $fieldmark = $this->sSettings['fieldmark'];
        } else {
            $fieldmark = "";
        }
        $lastkey = end($keys);
        foreach ($keys as $key) {
            if (!empty($line[$key]) || $line[$key] === '0') {
                if (strpos($line[$key], "\r") !== false
                    || strpos($line[$key], "\n") !== false
                    || strpos($line[$key], $fieldmark) !== false
                    || strpos($line[$key], $this->sSettings['separator']) !== false
                ) {
                    $csv .= $fieldmark;
                    if ($this->sSettings['encoding'] == "UTF-8") {
                        $line[$key] = utf8_decode($line[$key]);
                    }
                    if (!empty($fieldmark)) {
                        $csv .= str_replace($fieldmark, $this->sSettings['escaped_fieldmark'], $line[$key]);
                    } else {
                        $csv .= str_replace($this->sSettings['separator'], $this->sSettings['escaped_separator'], $line[$key]);
                    }
                    $csv .= $fieldmark;
                } else {
                    $csv .= $line[$key];
                }
            }
            if ($lastkey != $key) {
                $csv .= $this->sSettings['separator'];
            }
        }

        return $csv;
    }
}

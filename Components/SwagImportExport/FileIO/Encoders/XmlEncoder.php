<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\FileIO\Encoders;

/**
 * XmlEncoder
 */
class XmlEncoder extends \Shopware_Components_Convert_Xml
{
    /**
     * @param $array
     * @param int $pos
     * @param string $ekey
     * @return string
     */
    public function _encode($array, $pos = 0, $ekey = "")
    {
        $ret = "";
        if ($this->sSettings['padding'] !== false) {
            $pad = str_repeat($this->sSettings['padding'], $pos);
        } else {
            $pad = "";
        }
        foreach ($array as $key => $item) {
            if (!empty($ekey)) {
                $key = $ekey;
            }
            $attributes = "";
            if (is_array($item) && isset($item['_attributes'])) {
                foreach ($item['_attributes'] as $k => $v) {
                    $attributes .= " $k=\"" . htmlspecialchars($v) . "\"";
                }
                if (isset($item['_value'])) {
                    $item = $item['_value'];
                } else {
                    unset($item['_attributes'], $item['_value']);
                }
            }
            if ($this->isEmpty($item)) {
                $ret .= "$pad<$key$attributes></$key>{$this->sSettings['newline']}";
            } elseif (is_array($item)) {
                if (is_numeric(key($item))) {
                    $ret .= $this->_encode($item, $pos, $key);
                } else {
                    $ret .= "$pad<$key$attributes>{$this->sSettings['newline']}"
                        . $this->_encode($item, $pos + 1)
                        . "$pad</$key>{$this->sSettings['newline']}";
                }
            } else {
                if ($this->hasSpecialCharacters($item)) {
                    $item = str_replace("]]>", "]]]]><![CDATA[>", $item);
                    $ret .= "$pad<$key$attributes><![CDATA[" . $item . "]]></$key>{$this->sSettings['newline']}";
                } else {
                    if ($item === false) {
                        $item = 0;
                    }

                    $ret .= "$pad<$key$attributes>" . $item . "</$key>{$this->sSettings['newline']}";
                }
            }
        }

        return $ret;
    }

    /**
     * @param $item
     * @return bool
     */
    private function isEmpty($item)
    {
        return (empty($item) && $item !== '0' && $item !== false && $item !== 0);
    }

    /**
     * Checks if special xml characters were used.
     *
     * @param $item
     * @return int
     */
    private function hasSpecialCharacters($item)
    {
        return preg_match("#<|>|&(?<!amp;)#", $item);
    }
}

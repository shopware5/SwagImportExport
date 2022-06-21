<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Converter;

class XmlConverter
{
    /**
     * @var array<string, mixed>
     */
    public array $sSettings = [
        'encoding' => 'UTF-8',
        'standalone' => true,
        'attributes' => true,
        'root_element' => '',
        'padding' => "\t",
        'newline' => "\r\n",
    ];

    /**
     * @param array<string, mixed> $array
     */
    public function encode(array $array): string
    {
        $standalone = $this->sSettings['standalone'] ? 'yes' : 'no';
        $ret =
            "<?xml version=\"1.0\" encoding=\"{$this->sSettings['encoding']}\" standalone=\"$standalone\"?>{$this->sSettings['newline']}";
        $ret .= $this->_encode($array);

        return $ret;
    }

    /**
     * @param array<string, mixed> $array
     */
    public function _encode(array $array, int $pos = 0, string $ekey = ''): string
    {
        $ret = '';
        if ($this->sSettings['padding'] !== false) {
            $pad = \str_repeat($this->sSettings['padding'], $pos);
        } else {
            $pad = '';
        }
        foreach ($array as $key => $item) {
            if (!empty($ekey)) {
                $key = $ekey;
            }
            $attributes = '';
            if (\is_array($item) && isset($item['_attributes'])) {
                foreach ($item['_attributes'] as $k => $v) {
                    $attributes .= " $k=\"" . \htmlspecialchars($v) . '"';
                }
                if (isset($item['_value'])) {
                    $item = $item['_value'];
                } else {
                    unset($item['_attributes'], $item['_value']);
                }
            }
            if ($this->isEmpty($item)) {
                $ret .= "$pad<$key$attributes></$key>{$this->sSettings['newline']}";
            } elseif (\is_array($item)) {
                if (\is_numeric(\key($item))) {
                    $ret .= $this->_encode($item, $pos, $key);
                } else {
                    $ret .= "$pad<$key$attributes>{$this->sSettings['newline']}"
                        . $this->_encode($item, $pos + 1)
                        . "$pad</$key>{$this->sSettings['newline']}";
                }
            } else {
                if ($this->hasSpecialCharacters((string) $item)) {
                    $item = \str_replace(']]>', ']]]]><![CDATA[>', $item);
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
     * @return array<string, mixed>
     */
    public function decode(string $contents): array
    {
        if (!$contents) {
            return [];
        }
        if (!\function_exists('xml_parser_create')) {
            return [];
        }
        $parser = \xml_parser_create();
        \xml_parser_set_option($parser, \XML_OPTION_CASE_FOLDING, 0);
        \xml_parser_set_option($parser, \XML_OPTION_SKIP_WHITE, 1);
        \xml_parser_set_option($parser, \XML_OPTION_TARGET_ENCODING, $this->sSettings['encoding']);
        \xml_parse_into_struct($parser, \file_get_contents($contents), $xml_values);
        \xml_parser_free($parser);

        if (!$xml_values) {
            return [];
        }

        $xml_array = [];
        $current = &$xml_array;

        foreach ($xml_values as $data) {
            unset($attributes, $value); // Remove existing values, or there will be trouble
            \extract($data); // We could use the array by itself, but this cooler.
            $result = '';
            if (!empty($attributes)) { // The second argument of the function decides this.
                $result = [];
                if (isset($value)) {
                    $result['_value'] = $value;
                }

                // Set the attributes too.
                if (isset($attributes)) {
                    foreach ($attributes as $attr => $val) {
                        if ($this->sSettings['attributes']) {
                            $result['_attributes'][$attr] = $val;
                        } // Set all the attributes in a array called 'attr'
                        /*  TO DO should we change the key name to '_attr'? Someone may use the tagname 'attr'. Same goes for 'value' too */
                    }
                }
            } elseif (isset($value)) {
                $result = $value;
            }

            // See tag status and do the needed.
            if ($type === 'open') { // The starting of the tag '<tag>'
                $parent[$level - 1] = &$current;

                if (!\is_array($current) || (!\in_array($tag, \array_keys($current)))) { // Insert New tag
                    $current[$tag] = $result;
                    $current = &$current[$tag];
                } else { // There was another element with the same tag name
                    if (isset($current[$tag][0])) {
                        $current[$tag][] = $result;
                    } else {
                        $current[$tag] = [$current[$tag], $result];
                    }
                    $last = \count($current[$tag]) - 1;
                    $current = &$current[$tag][$last];
                }
            } elseif ($type === 'complete') { // Tags that ends in 1 line '<tag />'
                // See if the key is already taken.
                if (!isset($current[$tag])) { // New Key
                    $current[$tag] = $result;
                } else { // If taken, put all things inside a list(array)
                    if ((\is_array(
                        $current[$tag]
                    ) && $this->sSettings['attributes'] == 0) // If it is already an array...
                        || (isset($current[$tag][0]) && \is_array(
                            $current[$tag]
                        ) && $this->sSettings['attributes'] == 1)
                    ) {
                        // array_push($current[$tag],$result); // ...push the new element into that array.
                        $current[$tag][] = $result;
                    } else { // If it is not an array...
                        $current[$tag] = [
                            $current[$tag], $result,
                        ]; // ...Make it an array using the existing value and the new value
                    }
                }
            } elseif ($type === 'close') { // End of tag '</tag>'
                $current = &$parent[$level - 1];
            }
        }

        return $xml_array;
    }

    private function isEmpty($item): bool
    {
        return empty($item) && $item !== '0' && $item !== false && $item !== 0;
    }

    /**
     * Checks if special xml characters were used.
     */
    private function hasSpecialCharacters(string $item): int
    {
        return \preg_match('#<|>|&(?<!amp;)#', $item);
    }
}

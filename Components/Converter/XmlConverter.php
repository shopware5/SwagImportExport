<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Converter;

class XmlConverter implements ConverterInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $settings = [
        'encoding' => 'UTF-8',
        'standalone' => true,
        'attributes' => true,
        'root_element' => '',
        'padding' => "\t",
        'newline' => "\r\n",
    ];

    public function getNewline(): string
    {
        return "\r\n";
    }

    /**
     * @param array<string, mixed> $array
     */
    public function encode(array $array): string
    {
        $standalone = $this->settings['standalone'] ? 'yes' : 'no';
        $ret =
            "<?xml version=\"1.0\" encoding=\"{$this->settings['encoding']}\" standalone=\"$standalone\"?>{$this->settings['newline']}";
        $ret .= $this->_encode($array);

        return $ret;
    }

    /**
     * @param array<string, mixed> $array
     */
    public function _encode(array $array, int $pos = 0, string $ekey = ''): string
    {
        $ret = '';
        if ($this->settings['padding'] !== false) {
            $pad = \str_repeat($this->settings['padding'], $pos);
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
                    $attributes .= " $k=\"" . \htmlspecialchars($v, \ENT_COMPAT) . '"';
                }
                if (isset($item['_value'])) {
                    $item = $item['_value'];
                } else {
                    unset($item['_attributes'], $item['_value']);
                }
            }
            if ($this->isEmpty($item)) {
                $ret .= "$pad<$key$attributes></$key>{$this->settings['newline']}";
            } elseif (\is_array($item)) {
                if (\is_numeric(\key($item))) {
                    $ret .= $this->_encode($item, $pos, $key);
                } else {
                    $ret .= "$pad<$key$attributes>{$this->settings['newline']}"
                        . $this->_encode($item, $pos + 1)
                        . "$pad</$key>{$this->settings['newline']}";
                }
            } else {
                if ($this->hasSpecialCharacters((string) $item)) {
                    $item = \str_replace(']]>', ']]]]><![CDATA[>', $item);
                    $ret .= "$pad<$key$attributes><![CDATA[" . $item . "]]></$key>{$this->settings['newline']}";
                } else {
                    if ($item === false) {
                        $item = 0;
                    }

                    $ret .= "$pad<$key$attributes>" . $item . "</$key>{$this->settings['newline']}";
                }
            }
        }

        return $ret;
    }

    /**
     * @param string|int|bool $item
     */
    private function isEmpty($item): bool
    {
        return empty($item) && $item !== '0' && $item !== false && $item !== 0;
    }

    /**
     * Checks if special xml characters were used.
     */
    private function hasSpecialCharacters(string $item): bool
    {
        return (bool) \preg_match('#<|>|&(?<!amp;)#', $item);
    }
}

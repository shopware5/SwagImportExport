<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Converter;

class CsvConverter
{
    public array $sSettings = [
        'fieldmark' => '"',
        'separator' => ';',
        'encoding' => 'ISO-8859-1', //UTF-8
        'escaped_separator' => '',
        'escaped_fieldmark' => '""', 'newline' => "\n", 'escaped_newline' => '',
    ];

    /**
     * @param array $array
     *
     * @return string
     */
    public function encode($array, array $keys = [])
    {
        if (!\is_array($keys) || !\count($keys)) {
            $keys = \array_keys(\current($array));
        }
        $csv = $this->_encode_line(\array_combine($keys, $keys), $keys) . $this->sSettings['newline'];
        foreach ($array as $line) {
            $csv .= $this->_encode_line($line, $keys) . $this->sSettings['newline'];
        }

        return $csv;
    }

    /**
     * @deprecated - Unused, will be removed without replacement in 3.0.0
     *
     * @param array         $array
     * @param resource|null $stream
     *
     * @return bool
     */
    public function encode_stream($array, array $keys = [], &$stream = null)
    {
        if (empty($stream)) {
            $stream = \fopen('php://output', 'wb');
        }
        if (!\is_array($keys) || !\count($keys)) {
            $keys = \array_keys(\current($array));
        }
        foreach ($array as $line) {
            \fwrite($stream, $this->_encode_line($line, $keys) . $this->sSettings['newline']);
        }

        return true;
    }

    /**
     * @param array $array
     *
     * @return array
     */
    public function get_all_keys($array)
    {
        $keys = [];
        if (!empty($array) && \is_array($array)) {
            foreach ($array as $line) {
                $keys = \array_merge($keys, \array_diff(\array_keys($line), $keys));
            }
        }

        return $keys;
    }

    /**
     * @return string
     */
    public function _encode_line($line, $keys)
    {
        $csv = '';

        if (isset($this->sSettings['fieldmark'])) {
            $fieldmark = $this->sSettings['fieldmark'];
        } else {
            $fieldmark = '';
        }
        $lastkey = \end($keys);
        foreach ($keys as $key) {
            if (!empty($line[$key]) || $line[$key] === '0') {
                if (\strpos($line[$key], "\r") !== false
                    || \strpos($line[$key], "\n") !== false
                    || \strpos($line[$key], $fieldmark) !== false
                    || \strpos($line[$key], $this->sSettings['separator']) !== false
                ) {
                    $csv .= $fieldmark;
                    if ($this->sSettings['encoding'] === 'UTF-8') {
                        $line[$key] = \utf8_decode($line[$key]);
                    }
                    if (!empty($fieldmark)) {
                        $csv .= \str_replace($fieldmark, $this->sSettings['escaped_fieldmark'], $line[$key]);
                    } else {
                        $csv .= \str_replace($this->sSettings['separator'], $this->sSettings['escaped_separator'], $line[$key]);
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

    /**
     * @param string $csv
     *
     * @return array
     */
    public function decode($csv, array $keys = [])
    {
        $csv = \file_get_contents($csv);

        if ($this->sSettings['encoding'] === 'UTF-8') {
            $csv = \utf8_decode($csv);
        }

        if (isset($this->sSettings['escaped_newline']) && $this->sSettings['escaped_newline'] !== false && isset($this->sSettings['fieldmark']) && $this->sSettings['fieldmark'] !== false) {
            $lines = $this->_split_line($csv);
        } else {
            $lines = \preg_split("/\n|\r/", $csv, -1, \PREG_SPLIT_NO_EMPTY);
        }

        if (empty($keys) || !\is_array($keys)) {
            if (empty($this->sSettings['fieldmark'])) {
                $keys = \explode($this->sSettings['separator'], $lines[0]);
            } else {
                $keys = $this->_decode_line($lines[0]);
            }
            foreach ($keys as $i => $key) {
                $keys[$i] = \trim($key, "? \n\t\r");
            }
            unset($lines[0]);
        }

        foreach ($lines as $line) {
            $tmp = [];
            if (empty($this->sSettings['fieldmark'])) {
                $line = \explode($this->sSettings['separator'], $line);
            } else {
                $line = $this->_decode_line($line);
            }
            foreach ($keys as $pos => $key) {
                if (isset($line[$pos])) {
                    $tmp[$key] = $line[$pos];
                }
            }
            $array[] = $tmp;
        }

        return $array;
    }

    /**
     * @return array
     */
    public function _decode_line($line)
    {
        $fieldmark = $this->sSettings['fieldmark'];
        $elements = \explode($this->sSettings['separator'], $line);
        $tmp_elements = [];
        if (!\is_array($elements)) {
            return $tmp_elements;
        }

        foreach ($elements as $i => $element) {
            $nquotes = \substr_count($elements[$i], $this->sSettings['fieldmark']);
            if ($nquotes % 2 == 1) {
                if (isset($elements[$i + 1])) {
                    $elements[$i + 1] = $element . $this->sSettings['separator'] . $elements[$i + 1];
                }
            } else {
                if ($nquotes > 0) {
                    if (\strpos($elements[$i], $fieldmark) === 0) {
                        $elements[$i] = \substr($elements[$i], 1);
                    }
                    if (\substr($elements[$i], -1, 1) == $fieldmark) {
                        $elements[$i] = \substr($elements[$i], 0, -1);
                    }
                    $elements[$i] = \str_replace(
                        $this->sSettings['escaped_fieldmark'],
                        $this->sSettings['fieldmark'],
                        $elements[$i]
                    );
                }
                $tmp_elements[] = $element;
            }
        }

        return $tmp_elements;
    }

    /**
     * @return array
     */
    public function _split_line($csv)
    {
        $lines = [];
        $elements = \explode($this->sSettings['newline'], $csv);
        if (!\is_array($elements)) {
            return $lines;
        }

        foreach ($elements as $i => $element) {
            $nquotes = \substr_count($elements[$i], $this->sSettings['fieldmark']);
            if ($nquotes % 2 == 1) {
                $elements[$i + 1] = $element . $this->sSettings['newline'] . $elements[$i + 1];
            } else {
                $lines[] = $element;
            }
        }

        return $lines;
    }
}

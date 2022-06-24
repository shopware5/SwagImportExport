<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Converter;

class CsvConverter implements ConverterInterface
{
    /**
     * @var array<string, string>
     */
    private array $settings = [
        'fieldmark' => '"',
        'separator' => ';',
        'encoding' => 'ISO-8859-1', // UTF-8
        'escaped_separator' => '',
        'escaped_fieldmark' => '""',
        'newline' => "\n",
        'escaped_newline' => '',
    ];

    public function encode(array $array, array $keys = []): string
    {
        if (!\is_array($keys) || !\count($keys)) {
            $keys = \array_keys(\current($array));
        }
        $csv = $this->_encode_line(\array_combine($keys, $keys), $keys) . $this->settings['newline'];
        foreach ($array as $line) {
            $csv .= $this->_encode_line($line, $keys) . $this->settings['newline'];
        }

        return $csv;
    }

    /**
     * @param array<string, string>  $line
     * @param array<int, int|string> $keys
     */
    public function _encode_line(array $line, array $keys): string
    {
        $csv = '';

        if (isset($this->settings['fieldmark'])) {
            $fieldmark = $this->settings['fieldmark'];
        } else {
            $fieldmark = '';
        }
        $lastkey = \end($keys);
        foreach ($keys as $key) {
            if (!empty($line[$key]) || $line[$key] === '0') {
                if (\strpos($line[$key], "\r") !== false
                    || \strpos($line[$key], "\n") !== false
                    || \strpos($line[$key], $fieldmark) !== false
                    || \strpos($line[$key], $this->settings['separator']) !== false
                ) {
                    $csv .= $fieldmark;
                    if ($this->settings['encoding'] === 'UTF-8') {
                        $line[$key] = \utf8_decode($line[$key]);
                    }
                    if (!empty($fieldmark)) {
                        $csv .= \str_replace($fieldmark, $this->settings['escaped_fieldmark'], $line[$key]);
                    } else {
                        $csv .= \str_replace($this->settings['separator'], $this->settings['escaped_separator'], $line[$key]);
                    }
                    $csv .= $fieldmark;
                } else {
                    $csv .= $line[$key];
                }
            }
            if ($lastkey != $key) {
                $csv .= $this->settings['separator'];
            }
        }

        return $csv;
    }

    /**
     * @param array<int, string> $keys
     *
     * @return array<int, array<string, string>>
     */
    public function decode(string $csv, array $keys = []): array
    {
        $csv = \file_get_contents($csv);
        $array = [];

        if (\is_bool($csv)) {
            throw new \Exception('File could not be found');
        }

        if ($this->settings['encoding'] === 'UTF-8') {
            $csv = \utf8_decode($csv);
        }

        if (isset($this->settings['escaped_newline']) && $this->settings['escaped_newline'] !== false && isset($this->settings['fieldmark']) && $this->settings['fieldmark'] !== false) {
            $lines = $this->_split_line($csv);
        } else {
            $lines = \preg_split("/\n|\r/", $csv, -1, \PREG_SPLIT_NO_EMPTY);
        }

        if (!\is_array($lines)) {
            throw new \Exception('Invalid lines');
        }

        if (empty($keys) || !\is_array($keys)) {
            if (empty($this->settings['fieldmark'])) {
                $keys = \explode($this->settings['separator'], $lines[0]);
            } else {
                $keys = $this->_decode_line($lines[0]);
            }

            if (!\is_array($keys)) {
                throw new \Exception('Invalid keys');
            }

            foreach ($keys as $i => $key) {
                $keys[$i] = \trim($key, "? \n\t\r");
            }
            unset($lines[0]);
        }

        foreach ($lines as $line) {
            $tmp = [];
            if (empty($this->settings['fieldmark'])) {
                $line = \explode($this->settings['separator'], $line);
            } else {
                $line = $this->_decode_line($line);
            }
            if (!\is_array($line)) {
                throw new \Exception('Invalid line');
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
     * @return array<string>
     */
    public function _decode_line(string $line): array
    {
        $fieldmark = $this->settings['fieldmark'];
        $elements = \explode($this->settings['separator'], $line);
        $tmp_elements = [];
        if (!\is_array($elements)) {
            return $tmp_elements;
        }

        foreach ($elements as $i => $element) {
            $nquotes = \substr_count($elements[$i], $this->settings['fieldmark']);
            if ($nquotes % 2 == 1) {
                if (isset($elements[$i + 1])) {
                    $elements[$i + 1] = $element . $this->settings['separator'] . $elements[$i + 1];
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
                        $this->settings['escaped_fieldmark'],
                        $this->settings['fieldmark'],
                        $elements[$i]
                    );
                }
                $tmp_elements[] = $element;
            }
        }

        return $tmp_elements;
    }

    public function getNewline(): string
    {
        return "\n";
    }

    /**
     * @return array<int, string>
     */
    private function _split_line(string $csv): array
    {
        $lines = [];
        $elements = \explode($this->settings['newline'], $csv);
        if (!\is_array($elements)) {
            return $lines;
        }

        foreach ($elements as $i => $element) {
            $nquotes = \substr_count($elements[$i], $this->settings['fieldmark']);
            if ($nquotes % 2 == 1) {
                $elements[$i + 1] = $element . $this->settings['newline'] . $elements[$i + 1];
            } else {
                $lines[] = $element;
            }
        }

        return $lines;
    }
}

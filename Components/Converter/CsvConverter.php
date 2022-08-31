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
        if (!\count($keys)) {
            $keys = \array_keys(\current($array));
        }
        $csv = $this->encodeLine(\array_combine($keys, $keys), $keys) . $this->settings['newline'];
        foreach ($array as $line) {
            $csv .= $this->encodeLine($line, $keys) . $this->settings['newline'];
        }

        return $csv;
    }

    /**
     * @param array<string, string>  $line
     * @param array<int, int|string> $keys
     */
    public function encodeLine(array $line, array $keys): string
    {
        $csv = '';

        $fieldmark = $this->settings['fieldmark'] ?? '';
        $lastkey = \end($keys);
        foreach ($keys as $key) {
            if (!empty($line[$key]) || (isset($line[$key]) && $line[$key] === '0')) {
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

    public function getNewline(): string
    {
        return "\n";
    }
}

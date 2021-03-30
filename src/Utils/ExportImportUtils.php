<?php

namespace Smart\SonataBundle\Utils;

class ExportImportUtils
{
    const ARRAY_EXPORT_DELIMITER = '###';
    const COMMA_EXPORT_DELIMITER = '&&&';

    /**
     * Transform simple_array attributes in string
     *
     * @param array<string> $simpleArray
     * @return string
     */
    public static function transformSimpleArrayToExport(array $simpleArray): ?string
    {
        if ($simpleArray == null) {
            return null;
        }

        return str_replace("\r", '', implode(self::ARRAY_EXPORT_DELIMITER, $simpleArray));
    }

    /**
     * Convert list import in array
     *
     * @param string $value
     * @return false|string[]|null
     */
    public static function importSimpleArray(string $value)
    {
        if ($value == null) {
            return null;
        }

        return explode(self::ARRAY_EXPORT_DELIMITER, $value);
    }

    /**
     * Replace error characters
     *
     * @param string $text
     * @param bool $isImport // if true = import, if false = export
     * @return string|null
     */
    public static function transformTextToExportImport(string $text, bool $isImport): ?string
    {
        if ($text == null) {
            return null;
        }

        $replaces = [
            "\r\n",
            ","
        ];

        $delimiters = [
            self::ARRAY_EXPORT_DELIMITER,
            self::COMMA_EXPORT_DELIMITER
        ];

        if ($isImport) {
            return str_replace($delimiters, $replaces, $text);
        } else {
            return str_replace($replaces, $delimiters, $text);
        }
    }
}

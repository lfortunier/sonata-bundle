<?php

namespace Smart\SonataBundle\Utils;

class StringUtils
{
    /**
     * Return rows number of textarea
     *
     * @param string $string
     * @return int
     */
    public static function getNbRowsFromTextarea(string $string): int
    {
        return substr_count($string, PHP_EOL) + 1;
    }

    /**
     * Convert string to CamelCase
     *
     * @param string $value
     * @param bool $capitalizeFirstCharacter
     * @return string
     */
    public static function convertToCamelCase(string $value, bool $capitalizeFirstCharacter = false): string
    {
        // https://stackoverflow.com/questions/2791998/convert-dashes-to-camelcase-in-php
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $value)));

        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }

        return $str;
    }

    /**
     * Convert string to snake_case
     *
     * @param string $value
     * @return string
     */
    public static function convertToSnakeCase(string $value): string
    {
        // https://stackoverflow.com/questions/1993721/how-to-convert-pascalcase-to-pascal-case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }
}

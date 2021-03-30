<?php

namespace Smart\SonataBundle\Utils;

use Smart\SonataBundle\Exception\Utils\ArrayUtils\MultiArrayNbColumnsException;
use Smart\SonataBundle\Exception\Utils\ArrayUtils\MultiArrayNbMaxRowsException;

class ArrayUtils
{
    /**
     * Convert and clean data from textarea to array
     *
     * @param string $string
     * @return array<string>
     */
    public static function getArrayFromTextarea(string $string): array
    {
        $toReturn = explode(PHP_EOL, $string);
        $toReturn = array_map(function ($row) {
            return trim($row);
        }, $toReturn);

        $toReturn = array_unique(
            array_filter($toReturn, function ($value) {
                return $value != null and strlen($value) != 0;
            })
        );

        return array_values($toReturn);
    }

    /**
     * Convert and clean data from textarea to a multidimensional array
     *
     * @param string $string The textarea value
     * @param string $delimiter
     * @param array<string> $columnsName
     * @param ?int $nbMaxRows (Optionnel) Nombre maximum de ligne autorisé dans la conversion du string en array
     * @return array<array>
     */
    public static function getMultiArrayFromTextarea(string $string, string $delimiter, array $columnsName, int $nbMaxRows = null): array
    {
        $nbRows = StringUtils::getNbRowsFromTextarea($string);
        if ($nbMaxRows != null and $nbRows > $nbMaxRows) {
            throw new MultiArrayNbMaxRowsException($nbMaxRows, $nbRows);
        }

        $toReturn = self::getArrayFromTextarea($string);

        $nbColumns = count($columnsName);
        $nbColumnsErrorKeys = [];
        foreach ($toReturn as $key => $row) {
            $values = (array)explode($delimiter, $row);

            // Test du nombre de colonnes pour la ligne courante
            $canArrayCombine = true;
            if (count($values) != $nbColumns) {
                $nbColumnsErrorKeys[] = $key + 1;
                $canArrayCombine = false;
            }

            $values = array_map(function ($value) {
                if ($value != null) {
                    $value = trim($value);

                    // Removing quote from current column value
                    if (substr($value, 0, 1) == '"' and substr($value, -1) == '"') {
                        $value = substr($value, 1, strlen($value) - 2);
                    }
                }

                return $value;
            }, $values);

            if ($canArrayCombine) {
                $toReturn[$key] = array_combine($columnsName, $values);
            }
        }

        if (count($nbColumnsErrorKeys) > 0) {
            throw new MultiArrayNbColumnsException($nbColumnsErrorKeys);
        }

        return $toReturn;
    }
}

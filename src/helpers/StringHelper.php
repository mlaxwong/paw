<?php
namespace paw\helpers;

class StringHelper extends \yii\helpers\StringHelper
{
    public static function strtr($message, $params = [], $makeItEmpty = true)
    {
        preg_match_all('/\{([^\}]+)\}/i', $message, $matchs);
        $matchs = array_combine($matchs[0], $matchs[1]);

        if (!$matchs) {
            return $message;
        }

        $placeholders = [];
        foreach ($matchs as $fullMatch => $match) {
            $extraString = '';
            preg_match('/(.+)\|(.+)/i', $match, $extraMatchs);
            if ($extraMatchs) {
                list($fullExtraMatch, $match, $extraString) = $extraMatchs;
            }

            $parts = explode('.', $match);
            $pointer = $params;
            $notFound = false;
            $defaultValue = $makeItEmpty ? '' : $fullMatch;

            foreach ($parts as $part) {
                if (isset($pointer->{$part})) {
                    $pointer = $pointer->{$part};
                } else if (isset($pointer[$part])) {
                    $pointer = $pointer[$part];
                } else {
                    $pointer = $defaultValue;
                    $extraString = '';
                }
            }

            $placeholders[$fullMatch] = is_string($pointer) && !empty($pointer) ? $pointer . $extraString : $defaultValue;
        }

        return ($placeholders === []) ? $message : strtr($message, $placeholders);
    }

    public function numabbr($number)
    {
        $abbrevs = [12 => 'T', 9 => 'B', 6 => 'M', 3 => 'K', 0 => ''];

        foreach ($abbrevs as $exponent => $abbrev) {
            if (abs($number) >= pow(10, $exponent)) {
                $display = $number / pow(10, $exponent);
                $decimals = ($exponent >= 3 && round($display) < 100) ? 1 : 0;
                $number = number_format($display, $decimals) . $abbrev;
                break;
            }
        }

        return $number;
    }
}

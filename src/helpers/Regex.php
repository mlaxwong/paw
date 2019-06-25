<?php
namespace paw\helpers;

use yii\base\Component;

class Regex extends Component
{
    public static function match($patterns, $string)
    {
        $patterns = is_string($patterns) ? [$patterns] : $patterns;
        foreach ($patterns as $index => $pattern) {
            $patterns[$index] = self::parsePattern($pattern);
        }
        $match = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $string)) {
                $match = true;
                break;
            }
        }
        return $match;
    }

    protected static function parsePattern($pattern)
    {
        return preg_match('/^\/.+\/([a-zA-z]*)$/i', $pattern) ? $pattern : '/' . $pattern . '/i';
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: kmarques
 * Date: 10/01/2017
 * Time: 14:03
 */

namespace Incenteev\ParameterHandler\Executor;

class IniExecutor implements ExecutorInterface
{
    /**
     * @inheritdoc
     */
    public function parse($string)
    {
        $result = parse_ini_string($string, true);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function dump(array $data)
    {
        $string = '';
        foreach ($data as $section => $array) {
            $string .= "[$section]" . PHP_EOL;
            foreach ($array as $key => $value) {
                switch (true) {
                    case is_array($value):
                        $isAssoc = $this->isAssoc($value);
                        foreach ($value as $subKey => $subValue) {
                            $string .= $key . "[" . ($isAssoc ? $subKey : '') . "] = " . $this->formatValues($subValue) . PHP_EOL;
                        }
                        break;
                    default:
                        $string .= $key . " = " . $this->formatValues($value) . PHP_EOL;
                        break;
                }
            }
            $string .= PHP_EOL;
        }

        return trim($string, PHP_EOL);
    }

    private function formatValues($value)
    {
        switch (true) {
            case is_numeric($value):
                return $value;
                break;
            case is_bool($value):
                return ($value ? 1 : 0);
                break;
            case is_string($value):
                return '"' . $value . '"';
                break;
        }

        return $value;
    }

    private function isAssoc(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * @inheritdoc
     */
    public function getExtensions()
    {
        return array(
            'ini',
        );
    }

    /**
     * @inheritdoc
     */
    public function getCommentTag()
    {
        return ';';
    }
}
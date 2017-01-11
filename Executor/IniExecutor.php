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
                        foreach ($value as $subKey => $subValue) {
                            $string .= $key . "[" . $subKey . "] = " . $this->formatValues($subValue) . PHP_EOL;
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
            case is_bool($value):
                return ($value ? 1 : 0);
                break;
            case is_string($value):
                return '"' . $value . '"';
                break;
        }

        return $value;
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
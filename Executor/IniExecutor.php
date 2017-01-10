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
                    case is_numeric($value):
                        break;
                    case is_bool($value):
                        $value = $value ? 1 : 0;
                        break;
                    case is_string($value):
                        $value = '"' . $value . '"';
                        break;
                }
                $string .= $key . " = " . $value . PHP_EOL;
            }
            $string .= PHP_EOL;
        }

        return trim($string, PHP_EOL);
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
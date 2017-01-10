<?php
/**
 * Created by PhpStorm.
 * User: kmarques
 * Date: 10/01/2017
 * Time: 14:03
 */

namespace Incenteev\ParameterHandler\Executor;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class YamlExecutor implements ExecutorInterface
{
    /**
     * @inheritdoc
     */
    public function parse($string)
    {
        $parser = new Parser();

        return $parser->parse($string);
    }

    /**
     * @inheritdoc
     */
    public function dump(array $data)
    {
        return Yaml::dump($data, 99);
    }

    /**
     * @inheritdoc
     */
    public function getExtensions()
    {
        return array(
            'yml',
        );
    }

    /**
     * @inheritdoc
     */
    public function getCommentTag()
    {
        return '#';
    }
}
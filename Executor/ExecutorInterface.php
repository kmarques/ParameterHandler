<?php

namespace Incenteev\ParameterHandler\Executor;

interface ExecutorInterface
{
    /**
     * Deserialize an array
     *
     * @param string $string
     *
     * @return array
     */
    public function parse($string);

    /**
     * Serialize an array
     *
     * @param array $data
     *
     * @return string
     */
    public function dump(array $data);

    /**
     * Return an array of supported extensions
     *
     * @return array
     */
    public function getExtensions();

    /**
     * @return string
     */
    public function getCommentTag();
}

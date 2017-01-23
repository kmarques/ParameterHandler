<?php

namespace Incenteev\ParameterHandler;

use Composer\Script\Event;

class ScriptHandler
{
    public static function buildParameters(Event $event)
    {
        $extras = $event->getComposer()->getPackage()->getExtra();

        if (!isset($extras['incenteev-parameters'])) {
            throw new \InvalidArgumentException('The parameter handler needs to be configured through the extra.incenteev-parameters setting.');
        }

        $configs = $extras['incenteev-parameters'];

        if (!is_array($configs)) {
            throw new \InvalidArgumentException('The extra.incenteev-parameters setting must be an array or a configuration object.');
        }

        if (array_keys($configs) !== range(0, count($configs) - 1)) {
            if (isset($configs['constant-file'])) {
                if (!is_file($configs['constant-file'])) {
                    throw new \InvalidArgumentException('The constant-file value is not a valid file.');
                }
                require_once $configs['constant-file'];
                unset($configs['constant-file']);
            }
            if (isset($configs['files'])) {
                if (!is_array($configs['files']) || array_keys($configs['files']) !== range(0, count($configs['files']) - 1)) {
                    throw new \InvalidArgumentException('The files parameter must be a valid array of conf.');
                }
                $configs = $configs['files'];
            } else {
                $configs = array($configs);
            }
        }

        $processor = new Processor($event->getIO());

        foreach ($configs as $config) {
            if (!is_array($config)) {
                throw new \InvalidArgumentException('The extra.incenteev-parameters setting must be an array of configuration objects.');
            }

            $processor->processFile($config);
        }
    }
}

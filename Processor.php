<?php

namespace Incenteev\ParameterHandler;

use Composer\IO\IOInterface;
use Incenteev\ParameterHandler\Executor\ExecutorInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Inline;
use Symfony\Component\Yaml\Yaml;


class Processor
{
    private $io;

    /**
     * @var ExecutorInterface[]
     */
    private $executors;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;

        $this->loadExecutors();
    }

    public function processFile(array $config)
    {
        $config = $this->processConfig($config);

        $fileExecutor = $this->getExecutor($config['file']);
        $distExecutor = $this->getExecutor($config['dist-file']);

        $realFile     = $config['file'];
        $parameterKey = $config['parameter-key'];

        $exists = is_file($realFile);

        $action = $exists ? 'Updating' : 'Creating';
        $this->io->write(sprintf('<info>%s the "%s" file</info>', $action, $realFile));

        // Find the expected params
        $expectedValues = $distExecutor->parse(file_get_contents($config['dist-file']));
        if (!isset($expectedValues[$parameterKey])) {
            throw new \InvalidArgumentException(sprintf('The top-level key %s is missing.', $parameterKey));
        }
        $expectedParams = (array)$expectedValues[$parameterKey];

        // find the actual params
        $actualValues = array_merge(
        // Preserve other top-level keys than `$parameterKey` in the file
            $expectedValues,
            array($parameterKey => array())
        );
        if ($exists) {
            $existingValues = $fileExecutor->parse(file_get_contents($realFile));
            if ($existingValues === null) {
                $existingValues = array();
            }
            if (!is_array($existingValues)) {
                throw new \InvalidArgumentException(sprintf('The existing "%s" file does not contain an array', $realFile));
            }
            $actualValues = array_merge($actualValues, $existingValues);
        }

        $actualValues[$parameterKey] = $this->processParams($config, $expectedParams, (array)$actualValues[$parameterKey]);

        if (!is_dir($dir = dirname($realFile))) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($realFile, $fileExecutor->getCommentTag() . " This file is auto-generated during the composer install\n" . $fileExecutor->dump($actualValues));
    }

    private function processConfig(array $config)
    {
        if (empty($config['file'])) {
            throw new \InvalidArgumentException('The extra.incenteev-parameters.file setting is required to use this script handler.');
        }

        if (empty($config['dist-file'])) {
            $config['dist-file'] = $config['file'] . '.dist';
        }

        if (!is_file($config['dist-file'])) {
            throw new \InvalidArgumentException(sprintf('The dist file "%s" does not exist. Check your dist-file config or create it.', $config['dist-file']));
        }

        if (empty($config['parameter-key'])) {
            $config['parameter-key'] = 'parameters';
        }

        if (empty($config['var-delimiter'])) {
            if (empty($config['var-delimiter-open'])) {
                $config['var-delimiter-open'] = '{%';
            }

            if (empty($config['var-delimiter-close'])) {
                $config['var-delimiter-close'] = '%}';
            }
        } else {
            if (empty($config['var-delimiter-open'])) {
                $config['var-delimiter-open'] = $config['var-delimiter'];
            }

            if (empty($config['var-delimiter-close'])) {
                $config['var-delimiter-close'] = $config['var-delimiter'];
            }
        }

        return $config;
    }

    private function processParams(array $config, array $expectedParams, array $actualParams)
    {
        // Grab values for parameters that were renamed
        $renameMap    = empty($config['rename-map']) ? array() : (array)$config['rename-map'];
        $actualParams = array_replace($actualParams, $this->processRenamedValues($renameMap, $actualParams));

        $keepOutdatedParams = false;
        if (isset($config['keep-outdated'])) {
            $keepOutdatedParams = (boolean)$config['keep-outdated'];
        }

        if (!$keepOutdatedParams) {
            $actualParams = array_intersect_key($actualParams, $expectedParams);
        }

        $envMap = empty($config['env-map']) ? array() : (array)$config['env-map'];

        // Add the params coming from the environment values
        $actualParams = array_replace($actualParams, $this->getEnvValues($envMap));

        if (isset($config['var-file'])) {
            $expectedParams = $this->processVariableFile($config, $expectedParams);
        }

        return $this->getParams($expectedParams, $actualParams);
    }

    private function getEnvValues(array $envMap)
    {
        $params = array();
        foreach ($envMap as $param => $env) {
            $value = getenv($env);
            if ($value) {
                $params[$param] = Inline::parse($value);
            }
        }

        return $params;
    }

    private function processRenamedValues(array $renameMap, array $actualParams)
    {
        foreach ($renameMap as $param => $oldParam) {
            if (array_key_exists($param, $actualParams)) {
                continue;
            }

            if (!array_key_exists($oldParam, $actualParams)) {
                continue;
            }

            $actualParams[$param] = $actualParams[$oldParam];
        }

        return $actualParams;
    }

    private function processVariableFile(array $config, array $values)
    {
        if (!file_exists($config['var-file'])) {
            throw new \InvalidArgumentException("Variable file '${config['var-file']}' doesn't exist.");
        }

        $variables = Yaml::parse(file_get_contents($config['var-file']));

        $values = json_encode($values);
        foreach ($variables as $varName => $varValue) {
            $values = str_replace($config['var-delimiter-open'] . $varName . $config['var-delimiter-close'], $varValue, $values);
        }

        return json_decode($values, true);
    }

    private function getParams(array $expectedParams, array $actualParams)
    {
        // Simply use the expectedParams value as default for the missing params.
        if (!$this->io->isInteractive()) {
            return array_replace($expectedParams, $actualParams);
        }

        $isStarted = false;

        foreach ($expectedParams as $key => $message) {
            if (array_key_exists($key, $actualParams)) {
                continue;
            }

            if (!$isStarted) {
                $isStarted = true;
                $this->io->write('<comment>Some parameters are missing. Please provide them.</comment>');
            }

            $default = Inline::dump($message);
            $value   = $this->io->ask(sprintf('<question>%s</question> (<comment>%s</comment>): ', $key, $default), $default);

            $actualParams[$key] = Inline::parse($value);
        }

        return $actualParams;
    }

    /**
     * Retrieve executor from filename
     *
     * @param $filePath
     *
     * @return ExecutorInterface
     *
     * @throws \InvalidArgumentException
     */
    private function getExecutor($filePath)
    {
        $file = new \SplFileInfo($filePath);
        $ext  = $file->getFilename();

        if (isset($this->executors[$ext])) {
            return $this->executors[$ext];
        }

        $parts = explode('.', $file->getFilename());
        //Skip filename
        array_shift($parts);

        while (($ext = array_pop($parts)) !== null) {
            if (isset($this->executors[$ext])) {
                return $this->executors[$ext];
            }
        }

        throw new \InvalidArgumentException("File type '$ext' not supported");
    }

    /**
     * Load file executors
     */
    private function loadExecutors()
    {
        $this->executors = array();

        $finder = new Finder();
        $finder->files()->name('*Executor.php')->in(__DIR__ . '/Executor');

        $prefix = '\\Incenteev\\ParameterHandler\\Executor';
        foreach ($finder as $file) {
            /**
             * @var SplFileInfo $file
             */
            $ns = $prefix;
            if ($relativePath = $file->getRelativePath()) {
                $ns .= '\\' . str_replace('/', '\\', $relativePath);
            }

            $class = $ns . '\\' . $file->getBasename('.php');
            $r     = new \ReflectionClass($class);

            if ($r->isSubclassOf('Incenteev\\ParameterHandler\\Executor\\ExecutorInterface') && !$r->isAbstract()) {
                /**
                 * @var ExecutorInterface $object
                 */
                $object = $r->newInstance();
                foreach ($object->getExtensions() as $ext) {
                    $this->executors[$ext] = $object;
                }
            }
        }
    }
}
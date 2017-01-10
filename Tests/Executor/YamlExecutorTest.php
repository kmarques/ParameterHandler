<?php
/**
 * Created by PhpStorm.
 * User: kmarques
 * Date: 10/01/2017
 * Time: 16:41
 */

namespace Incenteev\ParameterHandler\Tests\Executor;


use Incenteev\ParameterHandler\Executor\YamlExecutor;
use Prophecy\PhpUnit\ProphecyTestCase;


class YamlExecutorTest extends ProphecyTestCase
{
    public function testAll()
    {
        $testFile = __DIR__ . '/../fixtures/executor/test.yml';

        $executor = new YamlExecutor();

        $data   = $executor->parse(file_get_contents($testFile));

        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('parameters', $data);

        $string = $executor->dump($data);

        $this->assertStringEqualsFile($testFile, $string);
    }
}

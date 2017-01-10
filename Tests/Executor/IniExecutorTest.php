<?php
/**
 * Created by PhpStorm.
 * User: kmarques
 * Date: 10/01/2017
 * Time: 16:41
 */

namespace Incenteev\ParameterHandler\Tests\Executor;


use Incenteev\ParameterHandler\Executor\IniExecutor;
use Prophecy\PhpUnit\ProphecyTestCase;


class IniExecutorTest extends ProphecyTestCase
{
    public function testAll()
    {
        $testFile = __DIR__ . '/../fixtures/executor/test.ini';

        $executor = new IniExecutor();

        $data   = $executor->parse(file_get_contents($testFile));

        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('parameters', $data);

        $string = $executor->dump($data);

        $this->assertStringEqualsFile($testFile, $string);
    }
}

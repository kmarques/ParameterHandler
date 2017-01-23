<?php

namespace Incenteev\ParameterHandler\Tests;

use Incenteev\ParameterHandler\ScriptHandler;
use Prophecy\PhpUnit\ProphecyTestCase;

class ScriptHandlerTest extends ProphecyTestCase
{
    private $event;
    private $io;
    private $package;

    protected function setUp()
    {
        parent::setUp();

        $this->event   = $this->prophesize('Composer\Script\Event');
        $this->io      = $this->prophesize('Composer\IO\IOInterface');
        $this->package = $this->prophesize('Composer\Package\PackageInterface');
        $composer      = $this->prophesize('Composer\Composer');

        $composer->getPackage()->willReturn($this->package);
        $this->event->getComposer()->willReturn($composer);
        $this->event->getIO()->willReturn($this->io);
    }

    /**
     * @dataProvider provideInvalidConfiguration
     */
    public function testInvalidConfiguration(array $extras, $exceptionMessage)
    {
        $this->package->getExtra()->willReturn($extras);

        chdir(__DIR__);

        $this->setExpectedException('InvalidArgumentException', $exceptionMessage);

        ScriptHandler::buildParameters($this->event->reveal());
    }

    public function testConstantFile()
    {
        $this->package->getExtra()->willReturn(array(
            'incenteev-parameters' => array(
                'constant-file' => 'fixtures/scripthandler/constant.php',
            ),
        ));

        chdir(__DIR__);

        try {
            ScriptHandler::buildParameters($this->event->reveal());
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('The extra.incenteev-parameters.file setting is required to use this script handler.', $e->getMessage());
        }

        $this->assertEquals('/var/test/my_constant', APPLICATION_PATH);
    }

    public function provideInvalidConfiguration()
    {
        return array(
            'no extra'                       => array(
                array(),
                'The parameter handler needs to be configured through the extra.incenteev-parameters setting.',
            ),
            'invalid type'                   => array(
                array('incenteev-parameters' => 'not an array'),
                'The extra.incenteev-parameters setting must be an array or a configuration object.',
            ),
            'invalid type for multiple file' => array(
                array('incenteev-parameters' => array('not an array')),
                'The extra.incenteev-parameters setting must be an array of configuration objects.',
            ),
            'no file'                        => array(
                array('incenteev-parameters' => array()),
                'The extra.incenteev-parameters.file setting is required to use this script handler.',
            ),
            'constant file not exist'        => array(
                array('incenteev-parameters' => array('constant-file' => 'foo.text')),
                'The constant-file value is not a valid file.',
            ),
            'files not an array'             => array(
                array('incenteev-parameters' => array('files' => 'foo.text')),
                'The files parameter must be a valid array of conf.',
            ),
            'files not a valid array'        => array(
                array('incenteev-parameters' => array('files' => array('foo' => 'bar'))),
                'The files parameter must be a valid array of conf.',
            ),
        );
    }
}

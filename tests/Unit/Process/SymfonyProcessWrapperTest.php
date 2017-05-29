<?php
declare(strict_types=1);

namespace Tests\Unit\Process;

use Paraunit\Process\SymfonyProcessWrapper;
use Tests\BaseUnitTestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class SymfonyProcessWrapperTest
 * @package Unit\Process
 */
class SymfonyProcessWrapperTest extends BaseUnitTestCase
{
    public function testGetUniqueId()
    {
        $process = new SymfonyProcessWrapper($this->mockProcessBuilder(), 'Test.php');
        
        $this->assertEquals('Test.php', $process->getFilename());
        $this->assertEquals(md5('Test.php'), $process->getUniqueId());
    }

    public function testStart()
    {
        $envVar = array('NAME' => 'value');
        $process = $this->prophesize(Process::class);
        $process->start()
            ->shouldBeCalledTimes(1);
        $processBuilder = $this->prophesize(ProcessBuilder::class);
        $processBuilder->addEnvironmentVariables($envVar)
            ->shouldBeCalled();
        $processBuilder->getProcess()
            ->shouldBeCalled()
            ->willReturn($process->reveal());

        $processWrapper = new SymfonyProcessWrapper($processBuilder->reveal(), 'Test.php');

        $processWrapper->start($envVar);
    }

    public function testAddTestResultShouldResetExpectingFlag()
    {
        $process = new SymfonyProcessWrapper($this->mockProcessBuilder(), 'Test.php');
        $process->setWaitingForTestResult(true);
        $this->assertTrue($process->isWaitingForTestResult());

        $process->addTestResult($this->mockPrintableTestResult());

        $this->assertFalse($process->isWaitingForTestResult());
    }

    private function mockProcessBuilder(): ProcessBuilder
    {
        return $this->prophesize(ProcessBuilder::class)->reveal();
    }
}

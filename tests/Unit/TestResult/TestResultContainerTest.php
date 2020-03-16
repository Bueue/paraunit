<?php

declare(strict_types=1);

namespace Tests\Unit\TestResult;

use Paraunit\TestResult\TestResultContainer;
use Paraunit\TestResult\TestResultFormat;
use Paraunit\TestResult\TestResultWithAbnormalTermination;
use Tests\BaseUnitTestCase;
use Tests\Stub\StubbedParaunitProcess;

class TestResultContainerTest extends BaseUnitTestCase
{
    public function testAddProcessToFilenames(): void
    {
        $testResultFormat = $this->prophesize(TestResultFormat::class);
        $testResultContainer = new TestResultContainer($testResultFormat->reveal());

        $unitTestProcess = new StubbedParaunitProcess('phpunit Unit/ClassTest.php');
        $unitTestProcess->setFilename('ClassTest.php');
        $functionalTestProcess = new StubbedParaunitProcess('phpunit Functional/ClassTest.php');
        $functionalTestProcess->setFilename('ClassTest.php');

        $testResultContainer->addProcessToFilenames($unitTestProcess);
        $testResultContainer->addProcessToFilenames($functionalTestProcess);

        $this->assertCount(2, $testResultContainer->getFileNames());
    }

    public function testHandleLogItemAddsProcessOutputWhenNeeded(): void
    {
        $testResult = new TestResultWithAbnormalTermination('function name', 'fail message');
        $process = new StubbedParaunitProcess();
        $process->setOutput('test output');

        $testResultContainer = new TestResultContainer($this->mockTestFormat());
        $testResultContainer->handleTestResult($process, $testResult);

        $this->assertStringContainsString('fail message', $testResult->getFailureMessage());
        $this->assertStringContainsString('test output', $testResult->getFailureMessage());
    }

    public function testHandleLogItemAddsMessageWhenProcessOutputIsEmpty(): void
    {
        $testResult = new TestResultWithAbnormalTermination('function name', 'fail message');
        $process = new StubbedParaunitProcess();
        $process->setOutput('');

        $testResultContainer = new TestResultContainer($this->mockTestFormat());
        $testResultContainer->handleTestResult($process, $testResult);

        $this->assertStringContainsString('fail message', $testResult->getFailureMessage());
        $this->assertStringContainsString('<tag><[NO OUTPUT FOUND]></tag>', $testResult->getFailureMessage());
    }

    public function testCountTestResultsCountsOnlyResultsWhichProducesSymbols(): void
    {
        $testResult = new TestResultWithAbnormalTermination('function name', 'some message');
        $process = new StubbedParaunitProcess();
        $process->setOutput('');
        $testFormat = $this->prophesize(TestResultFormat::class);
        $testFormat->getTag()
            ->willReturn('tag');

        $testResultContainer = new TestResultContainer($testFormat->reveal());
        $testResultContainer->handleTestResult($process, $testResult);

        $this->assertSame(0, $testResultContainer->countTestResults());
    }
}

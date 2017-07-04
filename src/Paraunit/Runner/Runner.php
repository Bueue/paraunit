<?php
declare(strict_types=1);

namespace Paraunit\Runner;

use Paraunit\Filter\Filter;
use Paraunit\Lifecycle\EngineEvent;
use Paraunit\Lifecycle\ProcessEvent;
use Paraunit\Process\ProcessBuilderFactory;
use Paraunit\Process\SymfonyProcessWrapper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class Runner
 * @package Paraunit\Runner
 */
class Runner implements EventSubscriberInterface
{
    /** @var  ProcessBuilderFactory */
    private $processBuilderFactory;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var Filter */
    private $filter;

    /** @var PipelineCollection */
    private $pipelineCollection;

    /** @var \SplQueue */
    private $queuedProcesses;

    /** @var int */
    private $exitCode;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param ProcessBuilderFactory $processFactory
     * @param Filter $filter
     * @param PipelineCollection $pipelineCollection
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ProcessBuilderFactory $processFactory,
        Filter $filter,
        PipelineCollection $pipelineCollection
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->processBuilderFactory = $processFactory;
        $this->filter = $filter;
        $this->pipelineCollection = $pipelineCollection;
        $this->queuedProcesses = new \SplQueue();
        $this->exitCode = 0;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProcessEvent::PROCESS_TERMINATED => 'pushToPipeline',
            ProcessEvent::PROCESS_PARSING_COMPLETED => 'onProcessParsingCompleted',
        ];
    }

    /**
     * @return int The final exit code: 0 if no failures, 10 otherwise
     */
    public function run(): int
    {
        $this->eventDispatcher->dispatch(EngineEvent::BEFORE_START);

        $this->createProcessQueue();

        $this->eventDispatcher->dispatch(EngineEvent::START);

        do {
            $this->pushToPipeline();
            usleep(100);
            $this->pipelineCollection->triggerProcessTermination();
        } while (! $this->pipelineCollection->isEmpty() || ! $this->queuedProcesses->isEmpty());

        $this->eventDispatcher->dispatch(EngineEvent::END);

        return $this->exitCode;
    }

    /**
     * @param ProcessEvent $processEvent
     */
    public function onProcessParsingCompleted(ProcessEvent $processEvent)
    {
        $process = $processEvent->getProcess();

        if ($process->isToBeRetried()) {
            $process->reset();
            $process->increaseRetryCount();

            $this->queuedProcesses->enqueue($process);

            $this->eventDispatcher->dispatch(ProcessEvent::PROCESS_TO_BE_RETRIED, new ProcessEvent($process));
        } elseif ($process->getExitCode() !== 0) {
            $this->exitCode = 10;
        }
    }

    private function createProcessQueue()
    {
        foreach ($this->filter->filterTestFiles() as $file) {
            $processBuilder = $this->processBuilderFactory->create($file);
            $this->queuedProcesses->enqueue(new SymfonyProcessWrapper($processBuilder, $file));
        }
    }

    public function pushToPipeline()
    {
        while (! $this->queuedProcesses->isEmpty() && $this->pipelineCollection->hasEmptySlots()) {
            $this->pipelineCollection->push($this->queuedProcesses->dequeue());
        }
    }
}

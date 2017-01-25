<?php

namespace Paraunit\Configuration;

use Paraunit\File\TempDirectory;

/**
 * Class TempFilenameFactory
 * @package Tests\Unit\Parser
 */
class TempFilenameFactory
{
    /** @var  TempDirectory */
    private $tempDirectory;

    /**
     * TempFilenameFactory constructor.
     * @param TempDirectory $tempDirectory
     */
    public function __construct(TempDirectory $tempDirectory)
    {
        $this->tempDirectory = $tempDirectory;
    }

    /**
     * @return string
     */
    public function getPathForLog()
    {
        return $this->getTempFilename('logs', '', '');
    }

    /**
     * @param string $uniqueId
     * @return string
     */
    public function getFilenameForLog($uniqueId)
    {
        return $this->getTempFilename('logs', $uniqueId, '.json.log');
    }

    /**
     * @param string $uniqueId
     * @return string
     */
    public function getFilenameForCoverage($uniqueId)
    {
        return $this->getTempFilename('coverage', $uniqueId, '.php');
    }

    /**
     * @return string
     */
    public function getFilenameForConfiguration()
    {
        return $this->getTempFilename('config', 'phpunit', '.xml.dist');
    }

    /**
     * @param string $filename
     * @param string $extension
     * @return string
     */
    private function getTempFilename($subdir, $filename, $extension)
    {
        return $this->tempDirectory->getTempDirForThisExecution()
            . DIRECTORY_SEPARATOR
            . $subdir
            . DIRECTORY_SEPARATOR
            . $filename
            . $extension;
    }
}
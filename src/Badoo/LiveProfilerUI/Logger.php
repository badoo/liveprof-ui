<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI;

use Psr\Log\{LoggerInterface, LoggerTrait};

class Logger implements LoggerInterface
{
    use LoggerTrait;

    /** @var string */
    protected $logfile;

    /**
     * Logger constructor.
     */
    public function __construct(string $logfile = '')
    {
        if ($logfile) {
            $this->logfile = $logfile;
        } else {
            $this->logfile = __DIR__ . '/../../../live.profiler.ui.log';
        }
    }

    public function setLogFile($logfile)
    {
        $this->logfile = $logfile;
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = array())
    {
        $log_string = $this->getLogMsg($level, $message, $context);
        file_put_contents($this->logfile, $log_string, FILE_APPEND);
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function getLogMsg($level, $message, array $context = array())
    {
        $log_string = sprintf("%s\t%s\t%s", date('Y-m-d H:i:s'), $level, $message);

        if (!empty($context)) {
            $log_string .= "\t" . json_encode($context);
        }

        $log_string .= "\n";

        return $log_string;
    }
}

<?php
// logger.php - Simple logger implementation

class Logger {
    private $debugLog;
    private $errorLog;

    /**
     * Constructor.
     *
     * @param string $debugLog Path to the debug log file.
     * @param string $errorLog Path to the error log file.
     */
    public function __construct($debugLog, $errorLog) {
        $this->debugLog = $debugLog;
        $this->errorLog = $errorLog;
    }

    /**
     * Writes a log message to the specified file.
     *
     * @param string $file  File path to write the log.
     * @param string $level Log level (DEBUG, ERROR).
     * @param string $message The log message.
     */
    private function logMessage($file, $level, $message) {
        $timestamp = date("Y-m-d H:i:s");
        $logEntry = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($level), $message);
        file_put_contents($file, $logEntry, FILE_APPEND);
    }

    /**
     * Logs a debug message.
     *
     * @param string $message The debug message.
     */
    public function debug($message) {
        $this->logMessage($this->debugLog, "debug", $message);
    }

    /**
     * Logs an error message.
     *
     * @param string $message The error message.
     */
    public function error($message) {
        $this->logMessage($this->errorLog, "error", $message);
    }
}

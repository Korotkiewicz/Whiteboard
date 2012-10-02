<?php

/**
 * This class is normal logger
 * 
 * @author Michal Korotkiewicz
 * @copyright (c) 2012
 */
class Whiteboard_Logger {

    protected $_filePath = null;
    protected $_levelName = 'INFO';
    protected $_levelValue = 4;
    protected $_showUri = true;
    protected $_allowedLevels = array('ALL' => 5, 'DEBUG' => 4, 'INFO' => 3, 'WARNING' => 2, 'ERROR' => 1);
    /**
     *
     * @var Whiteboard_Logger
     */
    protected static $instance = null;

    protected function __construct($filePath, $level = null, $showUri = null) {
        $this->_filePath = realpath($filePath);
        if (!file_exists($filePath)) {
            $h = @fopen($filePath, 'w');
            if (!$h) {
                throw new Whiteboard_Logger_Exception('File ' . $filePath . ' not exist and can not be create');
            }
            fclose($h);
        }
        
        if (!is_null($level)) {
            $this->setLevel($level);
        }
        if (!is_null($showUri)) {
            $this->showUri = $showUri ? true : false;
        }
    }

    /**
     * At first use you must post filePath
     * @param string $filePath path to log file
     * @param bool $showUri if true then log uri of request
     * @return Whiteboard_Logger
     */
    public static function getInstance($filePath = null, $level = null, $showUri = null) {
        if (is_null(self::$instance)) {
            if (is_null($filePath)) {
                throw new Whiteboard_Logger_Exception('At first use of getInstance you must post filePath');
            }
            self::$instance = new self($filePath, $level, $showUri);
        }
        
        return self::$instance;
    }

    /**
     * Set logging level
     * @param string $level
     */
    public function setLevel($level) {
        $level = strtoupper($level);
        if (!array_key_exists($level, $this->_allowedLevels)) {
            throw new Whiteboard_Logger_Exception('Level to set: ' . $level . ' is not allowed');
        }

        $this->_levelName = $level;
        $this->_levelValue = $this->_allowedLevels[$level];
    }

    /**
     * Return allowed levels of logging
     * @return array
     */
    public function getAllowedLevels() {
        return array_keys($this->_allowedLevels);
    }

    /**
     * Allow log info on given $level
     * 
     * @param string $level level of log
     * @param string $message message to log
     * @param string $login user login, not required, put in log file in <$login>
     */
    public function log($level, $message, $login = null) {
        if (!isset($this->_allowedLevels[$level])) {
            throw new Whiteboard_Logger_Exception('Level ' . $level . ' is not allowed');
        }
        
        if ($this->_levelValue >= $this->_allowedLevels[$level]) {
            $d = date('Y-m-d H:i:s');
            if (is_null($login)) {
                $login = '';
            } else {
                $login = 'user<' . $login . '> ';
            }
            if(isset($_SERVER['QUERY_STRING'])) {
                $uri = 'uri(' . $_SERVER['QUERY_STRING'] . ') ';
            } else {
                $uri = '';
            }

            file_put_contents($this->_filePath, '[' . $d . '] '. $uri . $login . $level . ':--- ' . $message . " ---\n", FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Log on level ALL
     * @param string $message
     * @param string $login
     */
    public function all($message, $login = null) {
        $this->log('ALL', $message, $login);
    }

    /**
     * Log on level DEBUG
     * @param string $message
     * @param string $login
     */
    public function debug($message, $login = null) {
        $this->log('DEBUG', $message, $login);
    }

    /**
     * Log on level ERROR
     * @param string $message
     * @param string $login
     */
    public function error($message, $login = null) {
        $this->log('ERROR', $message, $login);
    }

    /**
     * Log on level INFO
     * @param string $message
     * @param string $login
     */
    public function info($message, $login = null) {
        $this->log('INFO', $message, $login);
    }

    /**
     * Log on level WARNING
     * @param string $message
     * @param string $login
     */
    public function warning($message, $login = null) {
        $this->log('WARNING', $message, $login);
    }
    
    public function isLevelEnabled($level) {
        $level = strtoupper($level);
        if(isset($this->_allowedLevels[$level]) and $this->_levelValue <= $this->_allowedLevels[$level]) {
            return true;
        }
        return false;
    }

    public function isAllEnabled() {
        return $this->isLevelEnabled('ALL');
    }

    public function isDebugEnabled() {
        return $this->isLevelEnabled('DEBUG');
    }

    public function isErrorEnabled() {
        return $this->isLevelEnabled('ERROR');
    }

    public function isInfoEnabled() {
        return $this->isLevelEnabled('INFO');
    }

    public function isWarningEnabled() {
        return $this->isLevelEnabled('WARNING');
    }
}

/**
 * Whiteboard_Logger throw this exception
 */
class Whiteboard_Logger_Exception extends Exception {

    public function __construct($message = "", $code = 0, $previous = null) {
        $message = 'Whiteboard_Logger error: ' . $message;
        parent::__construct($message, $code, $previous);
    }

}
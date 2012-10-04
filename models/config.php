<?php

namespace Whiteboard;

require_once G_ROOTPATH . 'www/modules/module_whiteboard/models/protect.php';

/**
 * This class CRUD whiteboard module's configs
 * 
 * @author: Michal Korotkiewicz
 * @copyright (c) 2012
 */
class Config extends Protect {

    /**
     *
     * @var string
     */
    protected $_selected = null;

    /**
     *
     * @var string r - read, w - write
     */
    protected $_readOrWrite = 'r';
    protected $_fileHandler;

    /**
     *
     * @var array
     */
    protected $_dataFromConfig = null;

    /**
     *
     * @var array
     */
    protected $_configFiles;

    /**
     * @param string $subject default 'union'
     * @param boolean $canModify default false
     */
    public function __construct($subject = null, $canModify = false) {
        if(is_null($subject)) {
            $subject = 'union';
        }
        
        $this->_configFiles = array(
            'union' => G_ROOTPATH . 'www/modules/module_whiteboard/config/union.ini',
            'group' => G_ROOTPATH . 'www/modules/module_whiteboard/config/group.ini',
        );

        $subject = strtolower($subject);

        if (!array_key_exists($subject, $this->_configFiles)) {
            throw new Exception('Config of ' . $subject . ' not defined');
        }

        if ($canModify) {
            $this->_readOrWrite = 'r+';
        }

        $this->_selected = $subject;
    }

    public function __destruct() {
        $this->closeFile();
    }

    /**
     * Get config from ini files
     * 
     * @param string $section determined key
     * @return mixed - array/string/false - array with data when not set key, string when it is set, false on error
     */
    public function getConfig($section = null) {
        if (!$this->lazyLoad()) {
            return false;
        }

        if (!is_null($section)) {
            if (array_key_exists($section, $this->_dataFromConfig)) {
                return $this->_dataFromConfig[$section];
            } else {
                $this->loadModel('Logger');
                Whiteboard_Logger::getInstance()->debug('There is no value for key ' . $section . ' in config file for ' . $this->_selected);
                return false;
            }
        }
        return $this->_dataFromConfig;
    }

    /**
     * Save data in config
     * 
     * @param array $data
     * @return boolean
     * @throws Exception
     */
    public function saveConfig($data) {
        if ($this->_readOrWrite == 'r') {
            throw new Exception('Config file opened only to read');
        }

        if (!$this->lazyOpenFile()) {
            return false;
        }

        if (!empty($data)) {
            $this->lazyLoad();
        }

        //lock file
        if (!flock($this->_fileHandler, LOCK_EX)) {
            throw new Exception('Nie można teraz pisać do pliku konfiguracyjnego. Plik jest używany.');
        }

        //save data
        if (!empty($data)) {
            ftruncate($this->_fileHandler, 0);
            fseek($this->_fileHandler, 0, SEEK_SET);

            foreach ($this->_dataFromConfig as $key => $value) {
                if (array_key_exists($key, $data)) {
                    $value = $data[$key];
                    $this->_dataFromConfig[$key] = $value;
                    unset($data[$key]);
                }
                if (!is_array($value)) {
                    fwrite($this->_fileHandler, "$key = $value\n");
                } else {
                    fwrite($this->_fileHandler, "[$key]\n");

                    $this->_writeConfigValue($value, '');
                }
            }

            foreach ($data as $key => $value) {
                if (!is_array($value)) {
                    fwrite($this->_fileHandler, "$key = $value\n");
                } else {
                    fwrite($this->_fileHandler, "[$key]\n");

                    $this->_writeConfigValue($value, '');
                }
            }
        }

        //unlock file
        if (!flock($this->_fileHandler, LOCK_UN)) {
            $this->loadModel('Logger');
            Whiteboard_Logger::getInstance()->warning('Unlocking config file: ' . $this->_configFiles[$this->_selected] . ' failed');
        }

        return true;
    }

    protected function _writeConfigValue($values, $prefix) {
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                if (!empty($prefix)) {
                    $tmpPrefix = $prefix . '.' . $key;
                } else {
                    $tmpPrefix = $key;
                }
                $this->_writeConfigValue($value, $tmpPrefix);
            }
        } else {
            fwrite($this->_fileHandler, "$prefix = $values\n");
        }
    }

    /**
     * Load data from config file
     *
     * @patern Lazy Loading
     * @return bool
     */
    protected function lazyLoad() {
        if (!is_null($this->_dataFromConfig)) {
            return true;
        }

        if (!$this->lazyOpenFile()) {
            return false;
        }

        $section = '';

        //read file
        $this->_dataFromConfig = array();
        while (($line = fgets($this->_fileHandler)) !== false) {
            if (preg_match('/\[(.*)\]/', $line, $matches)) { //new SECTION
                $section = $matches[1];
                $this->_dataFromConfig[$section] = array();
                continue;
            }

            $semiPos = strpos($line, ';');
            if ($semiPos != false) { //comments in line
                $line = substr($line, 0, $semiPos);
            }

            if (!empty($line)) {
                $line = str_replace(array("\n", "\r", "\t"), '', $line);

                list($key, $value) = explode('=', $line);
                $key = trim($key);
                $keys = explode('.', $key);
                $value = trim($value);
                if (!empty($key)) {
                    $count = sizeof($keys);

                    if ($count > 1) {
                        $array = array($keys[$count - 1] => $value);
                        for ($i = $count - 2; $i > 0; $i -= 1) {
                            $array = array(
                                $keys[$i] => $array
                            );
                        }
                    } else {
                        $array = $value;
                    }

                    if (!isset($this->_dataFromConfig[$section][$keys[0]])) {
                        $this->_dataFromConfig[$section][$keys[0]] = $array;
                    } else {
                        $this->_dataFromConfig[$section][$keys[0]] = array_merge($this->_dataFromConfig[$section][$keys[0]], $array);
                    }
                }
            }
        }

        if (empty($this->_dataFromConfig)) {
            $this->_dataFromConfig = null;

            $this->loadModel('Logger');
            Whiteboard_Logger::getInstance()->debug('Config file for ' . $this->_selected . ' not exists');
            return false;
        }

        if ($this->_readOrWrite == 'r') {
            $this->closeFile();
        }

        return true;
    }

    protected function lazyOpenFile() {
        if (!$this->_fileHandler) {
            //check if file exists
            $path = realpath($this->_configFiles[$this->_selected]);
            if (!$path or !file_exists($path)) {
                $this->loadModel('Logger');
                Whiteboard_Logger::getInstance()->warning('Config file for ' . $this->_selected . ' not exists');
                return false;
            }

            //open file
            $this->_fileHandler = fopen($path, $this->_readOrWrite);
            if (!$this->_fileHandler) {
                $this->loadModel('Logger');
                Whiteboard_Logger::getInstance()->warning('Problem with open config (for: ' . $this->_selected . ') file: ' . $this->_configFiles[$this->_selected]);
                return false;
            }

            if ($this->_readOrWrite == 'r') {
                flock($this->_fileHandler, LOCK_SH);
            }
        }

        return true;
    }

    protected function closeFile() {
        if ($this->_fileHandler) {
            fclose($this->_fileHandler);
            unset($this->_fileHandler);
        }
    }

}
<?php

namespace Whiteboard;

abstract class Protect {

    public static function protectDB($string, $htmlTag = null) {
        if($htmlTag) {
            return $string;
        }
        $stringTmp = $string;

        return str_ireplace(array('SELECT ', 'DROP ', 'ALTER ', 'DESCRIBE ', 'UPDATE ', 'DELETE ', 'INSERT ', 'WHERE ', ' JOIN ', '--'), '', $stringTmp);
    }

    public static function arrayFromDB($array) {
        if (!get_magic_quotes_gpc()) {
            if (!empty($array)) {
                foreach ($array as $key => $value) {
                    if (!is_null($value) and is_string($value))
                        $array[$key] = stripslashes($value);
                }
            }
        }

        return $array;
    }

    public static function arrayProtectDB($array) {
        $type = null;
        foreach ($array as $key => $value) {
            $type = gettype($value);
            unset($array[$key]);
            switch ($type) {
                case 'string':
                    $key = self::protectDB($key);
                    $array[$key] = self::setNullCorrectly(self::protectDB($value));
                    break;
                case 'array':
                    $key = self::protectDB($key);
                    if (isset($value['Meta_type']) and $value['Meta_type'] == 'html') {
                        $array[$key] = self::protectDB($value['value'], true);
                    } else {
                        $array[$key] = self::arrayProtectDB($value);
                    }
                    break;
                case 'integer':
                    $key = self::protectDB($key);
                    $array[$key] = $value;
                    break;
                default:
                    $key = self::protectDB($key);
                    $array[$key] = null;
                    break;
            }
        }
        return $array;
    }

    public static function setNullCorrectly($string) {
        if ($string == '')
            return null;
        return $string;
    }

    public static function createExpresion($string) {
        return new Whiteboard_DB_Expr($string);
    }

    /**
     *
     * @param string $date format yyyy-mm-dd
     * @return string format dd.mm.yyyy
     */
    public static function convertDateFromDBFormat($date) {
        if (empty($date))
            return '';
        list($birth_date_year, $birth_date_month, $birth_date_day) = explode('-', $date);

        return $birth_date_day . '.' . $birth_date_month . '.' . $birth_date_year;
    }

    /**
     *
     * @param string $date format dd.mm.yyyy
     * @return string format yyyy-mm-dd
     */
    public static function convertDateToDBFormat($date) {
        if (empty($date))
            return '';
        list($birth_date_day, $birth_date_month, $birth_date_year) = explode('.', $date);

        return $birth_date_year . '-' . $birth_date_month . '-' . $birth_date_day;
    }

    public function loadModel($path, $module = 'module_whiteboard') {
        self::staticLoadModel($path, $module);
    }

    public static function staticLoadModel($path, $module = 'module_whiteboard') {
        if (!preg_match('/\.php$/', $path)) {
            $path .= '.php';
        }
        $realPath = G_ROOTPATH . 'www/modules/'.$module.'/models/' . $path;
        if (!file_exists($realPath)) {
            throw new Exception('file not exists: ' . $path);
        }
        require_once $realPath;
    }
}

class Whiteboard_DB_Expr {
    protected $string;
    /**
     *
     * @param string $string Expresion posted to db. Ex $string = "NULL"
     */
    function  __construct($string) {
        $this->string = $string;
    }
    /**
     * This function is used by eF query db function
     * @return string 
     */
    function  __toString() {
        return $this->string;
    }
}

?>
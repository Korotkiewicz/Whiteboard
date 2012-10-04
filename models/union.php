<?php

use Whiteboard;

/**
 * Union class is an interface to remove union server
 *
 * @author Michał
 */
class Union {

    private $config;
    private $URL;
    private $_output = '';
    private $handshakeComplite = false;
    private $clientID;
    private $userID;
    private $roles = 0;
    private $logged = false;
    private $sessionID;
    private $roomID;
    private $_params = array();

    //Message types:
    const _MSG_MOVE = 'MOVE';
    const _MSG_PATH = 'PATH';
    const _MSG_TEXT = 'TEXT';
    const _MSG_UNDO = 'UNDO';
    const _MSG_CLEAR = 'CLEAR';
    const _MSG_CHAT = 'CHAT_MESSAGE';
    const _MSG_SYSTEM = 'SYSTEM_MESSAGE';

    private $messages = array('close_room' => '--- Zamykam zajęcia ---', 'open_room' => 'Otwieram zajęcia');
    protected static $salt = 'noifv239hf923f';

    /**
     * get configuration and set vars
     */
    public function __construct() {
        require_once G_ROOTPATH . 'www/modules/module_whiteboard/models/config.php';
        $config = new Whiteboard_Config('union');
        $this->config = $config->getConfig();

        $this->URL = 'http://' . $this->config['server']['ip'] . ':' . $this->config['server']['port'];
    }

    /**
     * disconnect from union server
     */
    public function __destruct() {
        $this->disconnect();
    }

    /**
     * Create password for spec user (login) and 2 optional params
     * 
     * @param string $login
     * @param string $param1
     * @param string $param2
     * @return string
     */
    public static function createPassword($login, $param1 = null, $param2 = null) {
        if (is_null($param1))
            $param1 = '';
        if (is_null($param2))
            $param2 = '';

        return substr(md5($login . $param1 . $param2 . self::$salt), 1, 10);
    }

    /**
     *  Get Room ID by room name
     * 
     * @param string $roomName
     * @return string
     */
    public static function getRoomID($roomName) {
        return 'pl.edu.libratus.room.' . $roomName . '.' . substr(md5($roomName . self::$salt), 1, 5);
    }

    /**
     * Try connect to union server. If correct then return true
     * 
     * @return boolean
     */
    public function connect() {
        try {
            $xml = $this->send('d', $this->makeMsgContent('u65', array('Orbiter', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:14.0) Gecko/20100101 Firefox/14.0.1;2.0.0 (Build 768)', '1.10.1')));
            $result = $this->parseResponse($xml);

            if ($result[0]['protocolCompatible']) {
                $xml = $this->send('c', '', $result[0]['sessionID'], 1);

                $result1 = $this->parseResponse($xml);
                if ($this->isConnected()) {
                    $this->sessionID = $result[0]['sessionID'];
                    foreach ($result1 as $data) {
                        if ($data['clientID']) {
                            $this->clientID = $data['clientID'];
                        }
                        if ($data['roomID']) {
                            $this->roomID = $data['roomID'];
                        }
                    }

                    return true;
                } else {
                    return false;
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }

        return false;
    }

    /**
     * Disconnect from union server
     */
    public function disconnect() {
        if ($this->isConnected()) {
            $xml = $this->send('d', $this->makeMsgContent('u86', array($this->userID)));

            $this->parseResponse($xml);
        }
    }

    /**
     * Check if connect() has properly connected to union server
     * 
     * @return boolean
     */
    public function isConnected() {
        return $this->handshakeComplite;
    }

    /**
     * 
     * @return mixed return false if connection faild or array with connection info (sessionID, clientID, roomID, params)
     */
    public function getConnectionInfo() {
        if (!$this->isConnected()) {
            return false;
        }

        return array(
            'sessionID' => $this->sessionID,
            'clientID' => $this->clientID,
            'roomID' => $this->roomID,
            'params' => $this->_params
        );
    }

    /**
     *  Try login user to union server with spec login and password
     * 
     * @param string $login
     * @param string $password
     * @return boolean true = user login correctly
     */
    public function login($login, $password) {
        if (!$this->isConnected()) {
            return false;
        }

        //attempt to create account
        $xml = $this->send('d', $this->makeMsgContent('u11', array($login, $password)));
        $result = $this->parseResponse($xml);

        //if account created or allready exists then login
        if ($result[0]['status'] == 'SUCCESS' || $result[0]['status'] == 'ACCOUNT_EXISTS') {
            $xml = $this->send('d', $this->makeMsgContent('u14', array($login, $password)));
            $result = $this->parseResponse($xml);
        }

        $xml = $this->send('c', '');
        $result = $this->parseResponse($xml);

        return $this->isLogged();
    }

    /**
     *  Check if user has been logged
     * 
     * @return boolean
     */
    public function isLogged() {
        return $this->logged;
    }

    /**
     * This method start observe the room and then send there a msg
     * 
     * @param string $roomID
     * @param string $msgType example: CHAT_MESSAGE
     * @param string $msgText
     * @return bool
     */
    public function sendMsgToRoom($roomID, $msgType, $msgText) {
        if (!$this->isLogged()) {
            return false;
        }

        if ($this->observeRoom($roomID)) {
            $this->send('s', $this->makeMsgContent('u1', array($msgType, $roomID, 'false', '', $msgText)));

            return true;
        }

        return false;
    }

    /**
     * If you want send something to room you need to observed it before
     * 
     * @param string $roomID
     * @return boolean
     */
    public function observeRoom($roomID) {
        if (!$this->isLogged()) {
            return false;
        }

        $xml = $this->send('d', $this->makeMsgContent('u58', array($roomID, '')));
        $responses = $this->parseResponse($xml);

        $xml = $this->send('c', '');
        $responses = $this->parseResponse($xml);

        $result = false;
        foreach ($responses as $response) {
            if ($response['messageID'] == 'u77') {
                if ($response['status'] == 'SUCCESS') {
                    $result = true;
                }
            }
        }

        if ($result) {
            $this->roomID = $roomID;
        }

        return $result;
    }

    /**
     * Create room. Return true if room exists (for two reason: was created before or has been created now)
     * 
     * @param string $roomID
     * @return boolean
     */
    public function createRoom($roomID) {
        $roomExist = false;

        //try to create room:
        $xml = $this->send('d', $this->makeMsgContent('u24', array($roomID, '_DIE_ON_EMPTY|false', '', '')));
        $responses = $this->parseResponse($xml);

        foreach ($responses as $response) {
            if ($response['messageID'] == 'u32') {
                if ($response['status'] == 'SUCCESS' || $response['status'] == 'ROOM_EXISTS') {
                    $roomExist = true;
                }
                break;
            }
        }

        return $roomExist;
    }

    /**
     * Close room it meen that nobody can enter the room. It can throw 
     * Exception if you don't have allowence.
     * 
     * @param string $roomID
     * @return boolean
     * @throws Exception
     */
    public function closeRoom($roomID) {
        if (!$this->isLogged()) {
            return false;
        }

        if ($this->roles < 1) {
            throw new Exception('Brak uprawnień do zamykania grupy');
            return false;
        }

        if ($this->createRoom($roomID)) {
            $this->sendMsgToRoom($roomID, Union::_MSG_CHAT, $this->messages['close_room']);

            $isClosed = $this->updateRoomAttribute($roomID, '_ROOM_IS_OPEN', 'false');
        }

        return $isClosed;
    }

    /**
     * Open closed room. It can throw Exception if you don't have allowence
     * 
     * @param string $roomID
     * @return boolean
     * @throws Exception
     */
    public function openRoom($roomID) {
        if (!$this->isLogged()) {
            return false;
        }

        if ($this->roles < 1) {
            throw new Exception('Brak uprawnień do otwierania grupy');
            return false;
        }

        $isOpen = false;
        if ($this->createRoom($roomID) && $this->observeRoom($roomID)) {
            $isOpen = $this->updateRoomAttribute($roomID, '_ROOM_IS_OPEN', 'true');
        }

        return $isOpen;
    }

    /**
     * 
     * @param type $roomID
     * @param type $attrName
     * @param type $attrValue
     * @return boolean
     */
    public function updateRoomAttribute($roomID, $attrName, $attrValue) {
        $xml = $this->send('d', $this->makeMsgContent('u5', array($roomID, $attrName, $attrValue, 12))); //FLAG_SHARED | FLAG_PERSISTENT
        $responses = $this->parseResponse($xml);

        for ($i = 0; $i < count($responses); ++$i) {
            $response = $responses[$i];
            if ($response['messageID'] == 'u74') {
                if ($response['status'] == 'SUCCESS') {
                    if ($response['attrName'] == $attrName) {
                        $this->_params['room'][$this->roomID][$attrName] = $attrValue;
                        return true;
                    }
                }
            } elseif ($response['messageID'] == 'u9') {
                $xml = $this->send('c', '');
                $responses = $this->parseResponse($xml);
                if (!empty($responses))
                    $i = -1;
            }
        }

        return false;
    }

    /**
     * Prepare message to send to union server
     * 
     * @param string $messageID
     * @param string $values
     * @return string
     */
    public function makeMsgContent($messageID, $values = null) {
        $string = '<U>';
        $string .= "<M>$messageID</M>";
        if (is_array($values) && count($values) > 0) {
            $string .= '<L>';

            foreach ($values as $value) {
                $string .= "<A>$value</A>";
            }

            $string .= '</L>';
        }


        $string .= '</U>';

        return $string;
    }

    /**
     * @param DOMDocument $xml
     * @return array
     */
    public function parseResponse($xml) {
        if (!$xml instanceof DOMDocument) {
            return false;
        }

        $uElements = $xml->getElementsByTagName('U');
        $result = array();

        foreach ($uElements as $u) {
            $result[] = $this->parseU($u);
        }

        return $result;
    }

    /**
     *
     * @param DOMElement $xml
     * @return array
     */
    protected function parseU($xml) {
        $result = array(
            'text' => $xml->textContent,
            'messageID' => $xml->firstChild->nodeValue
        );


        switch ($result['messageID']) {
            case 'u8'://CLIENT_ATTR_UPDATE
                $result['roomID'] = $xml->childNodes->item(1)->childNodes->item(0)->nodeValue;
                $result['clientID'] = $xml->childNodes->item(1)->childNodes->item(1)->nodeValue;
                $result['userID'] = $xml->childNodes->item(1)->childNodes->item(2)->nodeValue;
                $result['attrName'] = $xml->childNodes->item(1)->childNodes->item(3)->nodeValue;
                $result['attrVal'] = $xml->childNodes->item(1)->childNodes->item(4)->nodeValue;
                $result['attrOptions'] = $xml->childNodes->item(1)->childNodes->item(5)->nodeValue;

                $this->_params[$result['attrName']] = $result['attrVal'];
                break;
            case 'u29'://CLIENT_METADATA
                $result['clientID'] = $xml->childNodes->item(1)->firstChild->nodeValue;
                break;
            case 'u32'://CREATE_ROOM_RESULT
                $result['roomID'] = $xml->childNodes->item(1)->childNodes->item(0)->nodeValue;
                $result['status'] = $xml->childNodes->item(1)->childNodes->item(1)->nodeValue;
                break;
            case 'u47'://CREATE_ACCOUNT_RESULT
                $result['userID'] = $xml->childNodes->item(1)->firstChild->nodeValue;
                $result['status'] = $xml->childNodes->item(1)->childNodes->item(1)->nodeValue;
                break;
            case 'u49'://LOGIN_RESULT
                $result['userID'] = $xml->childNodes->item(1)->firstChild->nodeValue;
                $result['status'] = $xml->childNodes->item(1)->childNodes->item(1)->nodeValue;

                if ($result['status'] == 'SUCCESS' || $result['status'] == 'ALREADY_LOGGED_IN') {
                    $this->logged = true;
                    $this->userID = $result['userID'];
                }
                break;
            case 'u54'://ROOM_SNAPSHOT
                $result['requestID'] = $xml->childNodes->item(1)->firstChild->nodeValue;
                $result['roomID'] = $xml->childNodes->item(1)->childNodes->item(1)->nodeValue;
                $result['occupantCount'] = $xml->childNodes->item(1)->childNodes->item(2)->nodeValue;
                $result['observerCount'] = $xml->childNodes->item(1)->childNodes->item(3)->nodeValue;
                $params = $xml->childNodes->item(1)->childNodes->item(4)->nodeValue;
                $params = explode('|', $params);
                $result['params'] = array();
                for ($i = 0; $i < count($params); $i += 2) {
                    $result['params'][$params[$i]] = $params[$i + 1];
                }

                break;
            case 'u9':
                $result['roomID'] = $xml->childNodes->item(1)->firstChild->nodeValue;
                $result['clientID'] = $xml->childNodes->item(1)->childNodes->item(1)->nodeValue;
                $result['attrName'] = $xml->childNodes->item(1)->childNodes->item(2)->nodeValue;
                $result['attrValue'] = $xml->childNodes->item(1)->childNodes->item(3)->nodeValue;

                $this->_params['room'][$result['roomID']][$result['attrName']] = $result['attrValue'];
                break;
            case 'u63'://CLIENT_READY
                $this->handshakeComplite = true;
                break;
            case 'u66'://SERVER_HELLO
                $result['sessionID'] = $xml->childNodes->item(1)->childNodes->item(1)->nodeValue;
                $result['upcVersion'] = $xml->childNodes->item(1)->childNodes->item(2)->nodeValue;
                $result['protocolCompatible'] = (boolean) $xml->childNodes->item(1)->childNodes->item(3)->nodeValue;
                break;
            case 'u74'://SET_ROOM_ATTR_RESULT
                $result['roomID'] = $xml->childNodes->item(1)->childNodes->item(0)->nodeValue;
                $result['attrName'] = $xml->childNodes->item(1)->childNodes->item(1)->nodeValue;
                $result['status'] = $xml->childNodes->item(1)->childNodes->item(2)->nodeValue;
                break;
            case 'u77'://OBSERVE_ROOM_RESULT
                $result['roomID'] = $xml->childNodes->item(1)->childNodes->item(0)->nodeValue;
                $result['status'] = $xml->childNodes->item(1)->childNodes->item(1)->nodeValue;
                break;
            case 'u84'://SESSION_TERMINATED
                $this->handshakeComplite = false;
                break;
            case 'u89'://LOGGED_OFF
                $this->handshakeComplite = false;
                $this->logged = false;
                break;
            case 'u88'://LOGGED_IN
                $result['clientID'] = $xml->childNodes->item(1)->firstChild->nodeValue;
                $result['userID'] = $xml->childNodes->item(1)->childNodes->item(1)->nodeValue;
                $params = $xml->childNodes->item(1)->childNodes->item(2)->nodeValue;
                list($paramName, $paramValue) = explode('|', $params);
                $result[$paramName] = $paramValue;

                if ($paramName == '_ROLES') {
                    $this->roles = intval($paramValue);
                }

                $this->logged = true;
                $this->userID = $result['userID'];
                break;
            default:
                //echo $this->_output;
                break;
        }
        
        return $result;
    }

    /**
     * Send message to union server
     * 
     * @param string $mode UPC send msg mode ('d','c','s')
     * @param string $data Content of msg
     * @param string $sid After correct connect() it doesn't have to be set
     * @param string $rid After correct connect() it doesn't have to be set
     * @return DOMDocument 
     */
    public function send($mode, $data, $sid = null, $rid = null) {
        $post = array(
            'mode' => $mode,
            'data' => $data//urlencode($data)
        );

        if ($sid) {
            $post['sid'] = $sid;
        } elseif ($this->sessionID) {
            $post['sid'] = $this->sessionID;
        }

        if ($rid) {
            $post['rid'] = $rid;
        } elseif ($this->sessionID) {
            $post['rid'] = 1;
        }

        $ch = curl_init($this->URL);

        $encoded = '';
        foreach ($post as $name => $value) {
            $encoded .= urlencode($name) . '=' . urlencode($value) . '&';
        }

        // chop off last ampersand
        $encoded = substr($encoded, 0, strlen($encoded) - 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        //return the transfer as a string 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);
        curl_close($ch);

        if ($output) {
            $this->_output = $output;

            try {
                $dom = new DOMDocument('1.0', 'utf-8');
                $dom->loadXML('<RES>' . $output . '</RES>');

                return $dom; //new SimpleXMLElement($output);
            } catch (Exception $e) {
                return false;
            }
        } else {
            $this->_output = '';

            return false;
        }
    }

}

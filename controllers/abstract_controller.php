<?php

namespace Whiteboard;

class AbstractController {

    /**
     *
     * @var \module_whiteboard
     */
    var $module = false;
    var $smarty = false;
    var $baseUrl = '';
    var $baseDir = '';
    var $baseLink = '';
    var $userLogin = '';
    var $userType = '';
    var $template = 'main';
    var $view = '';
    var $_message = '';
    protected $_varPrefix = 'T_WHITEBOARD_';

    public function __construct(\EfrontModule $module) {

        $this->module = $module;
        $this->smarty = $this->module->getSmartyVar();
        
        if(!$this->smarty) {
            throw new \Exception('Error with smarty');
        }

        $this->baseUrl = $this->module->moduleBaseUrl;
        $this->baseDir = $this->module->moduleBaseDir;
        $this->baseLink = $this->module->moduleBaseLink;

        $user = $this->module->getCurrentUser();
        $this->userLogin = $user->login;
        $this->userType = $user->user['user_type'];
        
        if($_SERVER['HTTPS'] == 'on') {
            $this->assign('BASELINK', $this->baseLink);
            $this->assign('BASEURL', $this->baseUrl);
        } else {
            $this->assign('BASELINK', str_replace('https', 'http', $this->baseLink));
            $this->assign('BASEURL', str_replace('https', 'http', G_SERVERNAME) . $this->baseUrl);
        }
        $this->assign('RELATIVELINK', substr($this->baseLink, strpos($this->baseLink, 'www/') + 4));

        $this->init();
    }

    public function init() {
        if (isset($_REQUEST['popup']) && $_REQUEST['popup'] = 1)
            $this->smarty->assign("T_POPUP", 1);
        else
            $this->smarty->assign("T_POPUP", 0);
    }

    public function postDispatch() {
        $this->smarty->assign("MESSAGE", $this->_message);
    }

    public function getId() {
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))
            return intval($_REQUEST['id']);
        else
            return false;
    }

    public function getLogin() {
        if (isset($_REQUEST['login']) && $_REQUEST['login'] != '')
            return $_REQUEST['login'];
        else
            return false;
    }

    protected function assign($name, $value) {
        $this->smarty->assign($this->_varPrefix . $name, $value);
    }

    protected function getLinkToView($controller, $a_action = null, $b_action = null) {
        $return = $this->baseUrl . '&c=' . $controller;
        if ($a_action) {
            return $return . '&a=' . $a_action;
        }

        if ($b_action) {
            return $return . '&b=' . $b_action;
        }

        return $return;
    }

    public function loadClass($path, $module = 'module_whiteboard') {
        if (!preg_match('/\.php$/', $path)) {
            $path .= '.php';
        }

        $dir = $this->baseDir;
        if ($module != 'module_whiteboard') {
            $dir = str_replace('module_whiteboard', $module, $dir);
        }

        $realPath = realpath($dir . $path);
        if (!$realPath) {
            throw new Exception('file not exists: ' . $path);
        }
        require_once $realPath;
    }

    public function getParam($name, $defaultValue = false) {
        if (!isset($_GET[$name])) {
            return $defaultValue;
        }

        $this->loadClass('models/protect');
        $param = Protect::protectDB($_GET[$name]);

        if (empty($param)) {
            return $defaultValue;
        }

        switch ($param) {
            case 'true':
                return true;
            case 'false':
                return false;
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
                if (strlen($param) == 1) {
                    return intval($param);
                }
            default:
                return $param;
                break;
        }
    }

    public function message($message) {
        if ($this->_message != '') {
            $this->_message .= "<br/>\n";
        }
        $this->_message .= $message;
    }

    public function filter($data) {

        isset($_GET['limit']) ? $limit = $_GET['limit'] : $limit = 20;

        if (isset($_GET['sort'])) {
            isset($_GET['order']) ? $order = $_GET['order'] : $order = 'asc';
            $data = eF_multiSort($data, $_GET['sort'], $order);
        }

        if (isset($_GET['filter'])) {
            $data = eF_filterData($data, $_GET['filter']);
        }

        $this->assign('DATA_SIZE', count($data));

        if (isset($_GET['limit']) && eF_checkParameter($_GET['limit'], 'int')) {
            isset($_GET['offset']) && eF_checkParameter($_GET['offset'], 'int') ? $offset = $_GET['offset'] : $offset = 0;
            $data = array_slice($data, $offset, $_GET['limit']);
        }

        return $data;
    }

    public function render($template = false) {

        $file = $this->baseDir;
        $file .= 'views/';
        $file .= $this->module->controller_name;
        $file .= '/';
        $file .= $this->view;
        $file .= '.tpl';

        if ($template) {
            $this->smarty->assign("T_VIEW", $file);
            $this->smarty->assign("T_VIEWDIR", $this->baseDir . 'views/' . $this->controller_name . '/');
            $this->smarty->display($this->baseDir . 'templates/' . $this->template . '.tpl');
            die;
        } else {
            $this->smarty->display($file);
            die;
        }
    }

    protected function _contextMenuInit() {
        $featuresBaseDir = str_replace('module_whiteboard', 'module_features', $this->baseDir);
        $contextFile = $featuresBaseDir . '/views/contextmenu_' . $this->userType . '.tpl';

        if (file_exists($contextFile)) {
            $this->smarty->assign('T_SHOW_CONTEXTMENU', 1);
            $this->smarty->assign('T_FEATURES_BASEURL', str_replace('module_whiteboard', 'module_features', $this->baseUrl));
            $this->smarty->assign('T_FEATURES_BASELINK', str_replace('module_whiteboard', 'module_features', $this->baseLink));
            //$this->smarty->assign('T_EXAM_BASELINK', $this->baseLink);
            //$this->smarty->assign('T_EXAM_BASEURL', $this->baseUrl);
            $this->smarty->assign('T_CRM_BASEURL', str_replace('module_whiteboard', 'module_crm', $this->baseUrl));
            $this->smarty->assign('T_APPLYSCHOOL_BASEURL', str_replace('module_whiteboard', 'module_applyschool', $this->baseUrl));
            $this->smarty->assign('T_CONTEXT_FILE', $contextFile);

            return true;
        } else {
            $this->smarty->assign('T_SHOW_CONTEXTMENU', 0);

            return false;
        }
    }

}
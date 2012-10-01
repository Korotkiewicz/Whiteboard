<?php

//This file cannot be called directly, only included.
if (str_replace(DIRECTORY_SEPARATOR, "/", __FILE__) == $_SERVER['SCRIPT_FILENAME']) {
    exit;
}

/*
 * Class defining the new module
 * The name must match the one provided in the module.xml file
 */

class module_whiteboard extends EfrontModule {

    /**
     *
     * @var Whiteboard_Logger
     */
    protected $errorLog = null;
    var $controller = false;
    var $controller_name = '';
    var $method = '';
    var $userType = '';
    var $template = 'main';
    var $view = '';
    private $helpAction = null;
    private static $_cache = array();
    var $showAvailability = true;

    public function __construct($defined_moduleBaseUrl, $defined_moduleFolder) {
        parent::__construct($defined_moduleBaseUrl, $defined_moduleFolder);

        if (isset($_REQUEST['op']) && $_REQUEST['op'] == 'module_whiteboard' && !$this->checkIfHelpWindow()) {
            require_once $this->moduleBaseDir . 'models/logger.php';
            $this->errorLog = Whiteboard_Logger::getInstance($this->moduleBaseDir . 'logs/error.log');

            $this->controller_name = $_GET['c'] ? $_GET['c'] : 'index';
            $this->userType = $this->getCurrentUser()->user['user_type'];

            if (isset($_GET['a']) && $_GET['a'] != '') {
                $this->method = $_GET['a'] . '_' . $this->userType;
            } else if (isset($_GET['b']) && $_GET['b'] != '') {
                $this->method = $_GET['b'];
            } else if (isset($_GET['x']) && $_GET['x'] != '') {
                $this->template = 'ajax';
                $this->method = 'ajax_' . $_GET['x'] . '_' . $this->userType;
            } else {
                $this->method = 'index' . '_' . $this->userType;
            }

            $this->view = $this->method;

            require_once 'controllers/abstract_controller.php';
            require_once 'controllers/' . $this->controller_name . '_controller.php';
            $controller = 'Whiteboard_' . ucfirst($this->controller_name) . 'Controller';

            if (class_exists($controller)) {
                $this->controller = new $controller($this);//(&$this);

                if (!method_exists($this->controller, $this->method)) {
                    $this->controller = false;
                    echo 'Undefined method ' . $controller . '::' . $this->method . '()';
                } else {
                    $this->controller->userType = $this->userType;
                    $this->controller->template = $this->template;
                    $this->controller->view = $this->view;

                    try {
                        $this->controller->{$this->method}();

                        if (method_exists($this->controller, 'postDispatch')) {
                            $this->controller->postDispatch();
                        }
                    } catch (Exception $e) {
                        if ($_SERVER['HTTP_HOST'] == 'localhost' || $this->userType == 'administrator' || $this->getCurrentUser()->login == 'uMichalKorotkiewicz') {
                            echo $e->getMessage();
                            echo $e->getTraceAsString();
                            exit;
                        }
                    }

                    $this->template = $this->controller->template;
                    $this->view = $this->controller->view;

                    $smarty = $this->getSmartyVar();
                    $smarty->assign("T_VIEW", $this->moduleBaseDir . 'views/' . $this->controller_name . '/' . $this->view . '.tpl');
                    $smarty->assign("T_VIEWDIR", $this->moduleBaseDir . 'views/' . $this->controller_name . '/');
                }
            } else {
                echo 'Undefined controller ' . $controller;
            }
        }
    }

    /**
     * Get the module name, for example "Demo module"
     *
     * @see libraries/EfrontModule#getName()
     */
    public function getName() {
        //This is a language tag, defined in the file lang-<your language>.php
        return _MODULE_WHITEBOARD;
    }

    /**
     * Return the array of roles that will have access to this module
     * You can return any combination of 'administrator', 'student' or 'professor'
     *
     * @see libraries/EfrontModule#getPermittedRoles()
     */
    public function getPermittedRoles() {
        return array('administrator', 'professor', 'student');  //This module will be available to administrators
    }

    /**
     * (non-PHPdoc)
     * @see libraries/EfrontModule#getCenterLinkInfo()
     */
    public function getCenterLinkInfo() {
        return array('title' => $this->getName(),
            'image' => $this->moduleBaseLink . 'img/logo.png',
            'link' => $this->moduleBaseUrl);
    }

    public function getSidebarLinkInfo() {
        if (!$this->userType) {
            $this->userType = $this->getCurrentUser()->user['user_type'];
        } elseif ($this->userType != 'administrator') {//if is not admin and is not added to any group then cannot see this module in left menu
            require_once G_ROOTPATH . 'www/modules/module_whiteboard/models/group.php';
            $userGroups = Whiteboard_Group::getUsersGroupList($this->getCurrentUser()->login);

            if (!$userGroups) {
                return array();
            }
        }

        $title = _MODULE_WHITEBOARD;

        $links = array(
            array(
                'id' => 'whiteboard_lesson_dojo',
                'title' => 'Plac zabaw',
                'image' => $this->moduleBaseLink . 'img/dojo_16x16.png',
                'link' => str_replace('https', 'http', G_SERVERNAME) . $this->moduleBaseUrl . '&c=lesson&b=dojo'
            )
        );

        if ($this->userType != 'student') {
            if ($this->userType == 'professor') {
                $links[] = array(
                    'id' => 'whiteboard_group_index_' . $this->userType,
                    'title' => 'Moje grupy',
                    'image' => $this->moduleBaseLink . 'img/class_16x16.png',
                    'link' => $this->moduleBaseUrl . '&c=group&a=index'
                );
            }

            $links[] = array(
                'id' => 'whiteboard_faq_index',
                'title' => 'Pytania i odpowiedzi',
                'image' => $this->moduleBaseLink . 'img/question_16x16.png',
                'link' => str_replace('https', 'http', G_SERVERNAME) . $this->moduleBaseUrl . '&c=faq&b=index'
            );

            $links[] = array(
                'id' => 'whiteboard_group_config_' . $this->userType,
                'title' => 'Zarządzanie grupami',
                'image' => $this->moduleBaseLink . 'img/tools_16x16.png',
                'link' => $this->moduleBaseUrl . '&c=group&a=config'
            );

            if ($this->userType == 'administrator') {
                $links[] = array(
                    'id' => 'whiteboard_config_powers_' . $this->userType,
                    'title' => 'Zarządzanie uprawnieniami',
                    'image' => $this->moduleBaseLink . 'img/users.png',
                    'link' => $this->moduleBaseUrl . '&c=config&a=powers'
                );

                $links[] = array(
                    'id' => 'whiteboard_config_admin_' . $this->userType,
                    'title' => 'Administracja serwera Union',
                    'image' => $this->moduleBaseLink . 'img/generic_16x16.png',
                    'link' => $this->moduleBaseLink . 'other/UnionAdmin.swf'
                );
            }
        } else {//student
            $links[] = array(
                'id' => 'whiteboard_lesson_index',
                'title' => 'Zajęcia',
                'image' => $this->moduleBaseLink . 'img/class_16x16.png',
                'link' => str_replace('https', 'http', G_SERVERNAME) . $this->moduleBaseUrl . '&c=lesson&b=index'
            );

            $links[] = array(
                'id' => 'whiteboard_faq_index',
                'title' => 'Zgłaszanie pytań',
                'image' => $this->moduleBaseLink . 'img/question_16x16.png',
                'link' => str_replace('https', 'http', G_SERVERNAME) . $this->moduleBaseUrl . '&c=faq&b=index'
            );
        }

        $menu = array(
            'other' => array(
                'menuTitle' => $title,
                'links' => $links
            )
        );


        return $menu;
    }

    /**
     * The main functionality
     *
     * (non-PHPdoc)
     * @see libraries/EfrontModule#getModule()
     */
    public function getModule() {
        $GLOBALS['configuration']['help_url'] = $this->moduleBaseUrl . '&';

        $smarty = $this->getSmartyVar();
        $smarty->assign("T_MODULE_BASEDIR", $this->moduleBaseDir);
        $smarty->assign("T_MODULE_BASELINK", $this->moduleBaseLink);
        $smarty->assign("T_MODULE_BASEURL", $this->moduleBaseUrl);

        return true;
    }

    public function getSmartyTpl() {
        if ($this->checkIfHelpWindow()) {
            return $this->moduleBaseDir . '/views/help/' . $this->helpAction . '.tpl';
        }

        if ($this->controller)
            return $this->moduleBaseDir . 'templates/' . $this->template . '.tpl';
        else
            return false;
    }

    /**
     * (non-PHPdoc)
     * @see libraries/EfrontModule#getNavigationLinks()
     */
    public function getNavigationLinks() {
        return array(array('title' => _HOME, 'link' => $_SERVER['PHP_SELF']),
            array('title' => $this->getName(), 'link' => $this->moduleBaseUrl));
    }

    public function getLinkToHighlight() {
        $id = 'whiteboard_' . $this->controller_name . '_' . $this->method;

        if ($this->method == 'room' && $this->controller_name == 'lesson') {
            $id = 'whiteboard_' . $this->controller_name . '_index';
        }

        if (isset($_GET['type'])) {
            $id .= '_' . $_GET['type'];
        }

        return $id;
    }

    /**
     *
     * @return boolean
     */
    protected function checkIfHelpWindow() {
        if (!isset($this->helpAction)) {
            if (!isset($_GET['c'])) {
                foreach ($_GET as $key => $value) {
                    if (preg_match('/^[\/]help[a-zA-Z0-9]*[?]simple$/', $key)) {
                        $this->helpAction = 'help_' . strtolower(substr($key, 5, strpos($key, '?') - 5));

                        if (!file_exists($this->moduleBaseDir . 'views/help/' . $this->helpAction . '.tpl')) {
                            $this->helpAction = 'help_todo';
                        }
                        break;
                    }
                }
            }
        }

        if ($this->helpAction) {
            return true;
        }
        $this->helpAction = false;

        return false;
        //return (isset($_GET['?title']) and preg_match('/^help[a-zA-Z]*$/', $_GET['?title']));
    }

    public function getModuleCSS() {
        if ($_GET['op'] == 'module_whiteboard' && !$_GET['x']) {
            return $this->moduleBaseDir . 'css/main_v1.26.css';
        } else {
            return false;
        }
    }

    public function getModuleJS() {
        if ($_GET['op'] == 'module_whiteboard' && !$_GET['x']) {
            return $this->moduleBaseDir . 'js/main_v1.27.js';
        } else {
            return false;
        }
    }

}
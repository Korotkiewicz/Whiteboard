<?php

namespace Whiteboard;

class LessonController extends AbstractController {

    public function index() {
        $this->payment();

        $correctBrowser = $this->_checkBrowser();
        $this->smarty->assign('T_CORRECT_BROWSER', $correctBrowser);

        if ($correctBrowser) {
            $this->loadClass('models/group');

            $groups = Group::getUsersGroupInfo($this->userLogin);

            $this->assign('GROUPS', $groups);
        }
    }

    public function dojo() {
        $this->assign('TITLE', 'Plac zabaw');
        $this->assign('DOJO', 1);

        $this->loadClass('models/logger');
        Logger::getInstance()->info('User enter dojo', $this->userLogin);

        $correctBrowser = $this->_checkBrowser();
        $this->smarty->assign('T_CORRECT_BROWSER', $correctBrowser);

        if ($correctBrowser) {
            $this->loadClass('models/union');

            $this->assign('LOGIN', $this->userLogin);
            $this->assign('PASSWORD', \Union::createPassword($this->userLogin, $this->module->getCurrentUser()->user['id']));
            $this->assign('ROOM_ID', 'pl.edu.libratus.room.dojo'); //'examples.uniondraw');//

            $this->getUnionConfig();
        }

        $this->assign('ROOM_TPL', $this->baseDir . 'views/lesson/room.tpl');
    }

    public function room() {
        $this->payment();

        $gkey = $this->getParam('gkey');

        if (!$gkey) {
            header('location: ' . $this->getLinkToView('lesson', null, 'index') . '&window=' . $_GET['window']);
            exit;
        }

        $this->loadClass('models/group');
        $group = new Group($gkey);
//        if (!$group->enterRoom()) {
//            header('location: ' . $this->getLinkToView('lesson', null, 'index') . '&window=' . $_GET['window']);
//            exit;
//        }

        $this->loadClass('models/logger');
        Logger::getInstance()->info('User enter room: ' . $gkey, $this->userLogin);

        $correctBrowser = $this->_checkBrowser();
        $this->smarty->assign('T_CORRECT_BROWSER', $correctBrowser);

        if ($correctBrowser) {
            $this->loadClass('controllers/group_controller');
            $groupController = new GroupController($this->module);
            $groupController->occupantsView($gkey);

            $data = $group->getData();
            $this->assign('TITLE', 'Zajęcia z przedmotu: ' . $data['course_name']);

            $this->loadClass('models/union');
            $this->assign('LOGIN', $this->userLogin);
            $this->assign('PASSWORD', \Union::createPassword($this->userLogin, $this->module->getCurrentUser()->user['id']));
            $this->assign('ROOM_ID', \Union::getRoomID($gkey));
            
            if($group->isOpen()) {
                $text = 'Zakończ';
                $method = 'closeLessonInRoom';
                $color = 'green';
            } else {
                $text = 'Rozpocznij';
                $method = 'openLessonInRoom';
                $color = 'red';
            }

            $lessonOptions = array();
            $lessonOptions[] = array(
                'id' => 'closeLesson',
                'href' => "",
                'text' => "$text zajęcia",
                'onclick' => "return $method(this, '{$this->baseUrl}&c=group&x=openclose&gkey=$gkey');",
                'image' => "images/16x16/trafficlight_$color.png",
            );
            $this->smarty->assign('T_LESSON_OPTIONS', $lessonOptions);

            $this->getUnionConfig();
        }
    }

    protected function getUnionConfig($test = false) {
        $this->loadClass('models/config');

        $config = new Config();
        $config = $config->getConfig();
        if ($config) {
            if ($test && $config['testserver']) {
                $config['server'] = $config['testserver'];
            }

            $this->assign('CONFIG', $config);
        }
    }

    /**
     * @ajax
     */
    public function getOpenRoom_student() {
        $_SESSION['previousMainUrl'] = $this->getLinkToView('lesson') . '&b=index';

        $this->loadClass('models/group');
        $groups = Group::getUsersGroupInfo($this->userLogin);

        $result = array();
        if ($groups) {
            foreach ($groups as $group) {
                if ($group['state'] == 'open') {
                    $result[] = $group['gkey'];
                }
            }
        }

        echo json_encode($result);
        exit;
    }

    protected function _checkBrowser() {
        $browserInfo = strtolower($_SERVER['HTTP_USER_AGENT']);
        $this->loadClass('models/logger');

        $correctBrowser = true;
        if ($browserInfo) {
            $rchrome = '/(chrome)[ \/]([\w.]+)/';
            $ropera = '/(opera)(?:.*version)?[ \/]([\w.]+)/';
            $rsafari = '/version[ \/]([\w.]+)[ ](safari)[ \/][\w.]+/';
            $rwebkit = '/(webkit)[ \/]([\w.]+)/';
            $msie = '/(msie) ([\w.]+)/';
            $rfirefox = '/(firefox)[ \/]([\w.]+)/';

            $find = preg_match($rchrome, $browserInfo, $match) ||
                    preg_match($ropera, $browserInfo, $match) ||
                    preg_match($rsafari, $browserInfo, $match) ||
                    preg_match($rwebkit, $browserInfo, $match) ||
                    preg_match($msie, $browserInfo, $match) ||
                    (!strpos($browserInfo, 'compatible') && preg_match($rfirefox, $browserInfo, $match)) ||
                    false;

            if (!$find) {
                Logger::getInstance()->info('Browser does\'t match', $this->userLogin);
            } else {
                if ($match[2] == 'safari') {
                    $tmp = $match[2];
                    $match[2] = $match[1];
                    $match[1] = $tmp;
                }
                $browserName = $match[1];
                $dotPos = strpos($match[2], '.');
                if ($dotPos) {
                    $browserVersion = intval(substr($match[2], 0, strpos($match[2], '.')));
                } else {
                    $browserVersion = intval($match[2]);
                }

                switch ($browserName) {
//                        case 'webkit': //?
////                            if($browserVersion < 4) {
////                                $correctBrowser = false;
////                            }
//                            break;
                    case 'safari': //safari
                        if ($browserVersion < 4) {
                            $correctBrowser = false;
                        }
                        break;
                    case 'chrome': //chrome
                        if ($browserVersion < 9) {
                            $correctBrowser = false;
                        }
                        break;
                    case 'firefox': //firefox
                        if ($browserVersion < 10) {
                            $correctBrowser = false;
                        }
                        break;
                    case 'opera': //opera
                        if ($browserVersion < 10) {
                            $correctBrowser = false;
                        }
                        break;
                    case 'msie': //IE
                        //$correctBrowser = false;
                        if ($browserVersion < 9) {
                            $correctBrowser = false;
                        }
                        break;
                    default:
                        Logger::getInstance()->warning('Browser not detected (name:' . $browserName . ',version:' . $browserVersion . '): ' . $browserInfo, $this->userLogin);
                        break;
                }
            }
            if ($correctBrowser) {
                Logger::getInstance()->info('Ok browser(name:' . $browserName . ',version:' . $browserVersion . '): ' . $browserInfo, $this->userLogin);
            } else {
                Logger::getInstance()->info('Wrong browser(name:' . $browserName . ',version:' . $browserVersion . '): ' . $browserInfo, $this->userLogin);
            }
        } else {
            Logger::getInstance()->warning('No browserInfo set', $this->userLogin);
        }

        return $correctBrowser;
    }

    protected function payment() {
        $paidForThisMonth = true;
        try {
            $this->loadClass('models/summarylesson', 'module_payments');
            $model = new \ModulePayments_SummaryLesson($this->userLogin);

            $paidForThisMonth = $model->checkIfPaidForThisMonth();
        } catch (Exception $e) {
            
        }
//        if (!$paidForThisMonth) {
//            header('location: ' . str_replace('module_whiteboard', 'module_payments', $this->baseUrl) . '&a=summarylesson');
//            exit;
//        }
    }

}

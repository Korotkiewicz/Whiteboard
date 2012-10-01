<?php

class Whiteboard_GroupController extends Whiteboard_AbstractController {

    public function occupantsView($gkey = null) {
        if (!$gkey)
            $gkey = $this->getParam('gkey');

        if ($gkey) {
            $this->loadClass('models/group');
            $group = new Whiteboard_Group($gkey);

            $users = $group->getOccupantList();

            foreach ($users as $i => $user) {
                if ($user['login'] == $this->userLogin) {
                    unset($users[$i]);
                }
            }

            $this->assign('SHOW_PHONE', in_array($this->userType, array('administrator', 'professor')));
            $this->assign('OCCUPANTS', $users);
            $this->assign('OCCUPANTS_TPL', $this->baseDir . '/views/group/occupants.tpl');

            $this->assign('TITLE', 'Kontakt do uczestników grupy `' . $gkey . '`');

            return true;
        } else {
            return false;
        }
    }

    public function uinfo() {
        if (!$this->occupantsView()) {
            header('location: ' . $_SERVER['HTTP_REFERER']);
        }
    }

    public function index_administrator() {
//        $this->loadClass('models/union');
//        $union = new Union();
//
//        $gkey = 'p1ew_g2';
//
//        if ($union->connect()) {
//            if ($union->login($this->userLogin, Union::createPassword($this->userLogin, $this->module->getCurrentUser()->user['id']))) {
//                $result = $union->closeRoom(Union::getRoomID($gkey));
//            }
//        }
//
//        var_dump($result);
    }

    public function index_professor() {
        $this->smarty->assign("T_MODULE_BASEURL", $this->baseUrl);
        $this->assign('TPL', $this->baseDir . 'views/group/ajax_index_professor.tpl');

        if ($this->getParam('ajax')) {
            $this->assign('AJAX', 1);

            $this->loadClass('models/group');
            $data = Whiteboard_Group::getList(false, $this->userLogin, true, false, true);

            $this->assign('HTTP_URL', str_replace('https', 'http', G_SERVERNAME) . $this->baseUrl);

            $this->assign('DATA', $this->filter($data));
            $this->render(false);
        }
    }

    public function schedule_administrator() {
        $this->loadClass('models/group');
        $this->loadClass('lib/time', 'module_applyschool');

        $courses = Whiteboard_Group::getCourses();
        $availableCourses = array();

        $courseID = $this->getParam('course');
        if ($courseID == 'all') {
            $courseID = null;
        }

        $this->loadClass('models/user');
        $teachers = Whiteboard_User::getTeachers();

        $this->assign('TEACHERS', $teachers);
        $teacher = $this->getParam('teacher');

        $data = Whiteboard_Group::getList(false, $this->userType == 'professor' ? $this->userLogin : null, false, false, true);

        $weeksToShow = 8;
        $secondsInOneDay = 86400; //60s * 60m * 24h
        $secondsInOneWeek = 604800; //60s * 60m * 24h * 7d
        $secondsInOneWeek = 604800; //60s * 60m * 24h * 7d
        $dayOfWeek = ApplySchool_getDateInWarsaw('w');
        if ($dayOfWeek > 0) {
            --$dayOfWeek;
        } else {
            $dayOfWeek = 6; //sunday;
        }
        $monday = ApplySchool_strtotimeInWarsaw(ApplySchool_getDateInWarsaw('Y-m-d')) - $dayOfWeek * $secondsInOneDay;

        $weeks = array();
        for ($i = 0; $i <= $weeksToShow; ++$i) {
            $weeks[$i] = $monday + $i * $secondsInOneWeek;
        }

        $minTime = $weeks[0];
        $maxTime = $weeks[$weeksToShow];
        $daysNames = array(1 => 'Pn', 2 => 'Wt', 3 => 'Śr', 4 => 'Czw', 5 => 'Pt', 6 => 'Sob', 7 => 'Nd');

        $weeksData = array();
        $flipedData = array();

        for ($time = $minTime; $time < $maxTime; $time += $secondsInOneWeek) {
            $begin = ApplySchool_getDateInWarsaw('Y-m-d', $time);
            $end = ApplySchool_getDateInWarsaw('Y-m-d', $time + $secondsInOneWeek - 1);
            list($bYear, $bMonth, $bDay) = explode('-', $begin);
            list($eYear, $eMonth, $eDay) = explode('-', $end);

            $formatedDate = '';
            if ($bYear == $eYear) {
                if ($bMonth == $eMonth) {
                    $formatedDate = "$bDay-$eDay.$eMonth.$bYear";
                } else {
                    $formatedDate = "$bDay.$bMonth-$eDay.$eMonth.$bYear";
                }
            } else {
                $formatedDate = "$bDay.$bMonth.$bYear - $eDay.$eMonth.$eYear";
            }

            for ($i = 1; $i <= 7; ++$i) {
                $flipedData[$i][$formatedDate . 'r'] = array();
            }

            $weeksData[$formatedDate . 'r'] = array();
        }

        foreach ($data as $row) {
            $availableCourses[$row['course_id']] = $courses[$row['course_id']];

            if ($courseID && $row['course_id'] != $courseID) {
                continue;
            }

            if ($teacher && $teachers[$teacher]) {
                if (!in_array($row['gkey'], $teachers[$teacher])) {
                    continue;
                }
            }

            $time = ApplySchool_strtotimeInWarsaw($row['next_lesson']);

            $weekTime = null;
            for ($i = 0; $i < $weeksToShow; ++$i) {
                if ($time >= $weeks[$i] && $time < $weeks[$i + 1]) {
                    $weekTime = $weeks[$i];
                    break;
                }
            }

            if ($weekTime) {
                for (; $time < $maxTime; $time += $secondsInOneWeek * $row['frequency'], $weekTime += $secondsInOneWeek * $row['frequency']) {
                    $begin = ApplySchool_getDateInWarsaw('Y-m-d', $weekTime);
                    $end = ApplySchool_getDateInWarsaw('Y-m-d', $weekTime + $secondsInOneWeek - 1);
                    list($bYear, $bMonth, $bDay) = explode('-', $begin);
                    list($eYear, $eMonth, $eDay) = explode('-', $end);

                    $formatedDate = '';
                    if ($bYear == $eYear) {
                        if ($bMonth == $eMonth) {
                            $formatedDate = "$bDay-$eDay.$eMonth.$bYear";
                        } else {
                            $formatedDate = "$bDay.$bMonth-$eDay.$eMonth.$bYear";
                        }
                    } else {
                        $formatedDate = "$bDay.$bMonth.$bYear - $eDay.$eMonth.$eYear";
                    }

                        $row['date'] = ApplySchool_getDateInWarsaw('d.m.Y', $time);
                    $weeksData[$formatedDate . 'r'][$time][] = $row;
                }
            }
        }

        foreach ($weeksData as $formatedDate => $weekData) {
            ksort($weekData);
            foreach ($weekData as $t => $d) {
                $weeksData[$formatedDate][$t] = $d;

                if ($d) {
                    foreach ($d as $row) {
                        $flipedData[$row['day_of_week']][$formatedDate][] = $row;
                    }
                }
            }
        }

        ksort($flipedData);

        $this->assign('WEEKS', $weeksData);
        $this->assign('DATA', $flipedData);
        $this->assign('COURSES', $availableCourses);
    }

    public function schedule_professor() {
        if (!$_GET['teacher'])
            $_GET['teacher'] = $this->userLogin;

        $this->schedule_administrator();
        $this->assign('TPL', $this->baseDir . 'views/group/schedule_administrator.tpl');
    }

    public function config_administrator() {
        $this->smarty->assign("T_MODULE_BASEURL", $this->baseUrl);
        $this->assign('TPL', $this->baseDir . 'views/group/ajax_config_administrator.tpl');

        if ($this->getParam('ajax')) {
            $this->assign('AJAX', 1);
            $this->assign('IS_ADMIN', $this->userType == 'administrator');

            $this->loadClass('models/group');
            $data = Whiteboard_Group::getList(false, $this->userType == 'professor' ? $this->userLogin : null, $this->userType == 'professor', $this->userType == 'administrator', true);

            $data = $this->filter($data);
            $this->assign('DATA', $data);
            $this->render(false);
        }
    }

    public function config_professor() {
        $this->assign('ADMIN_TPL', $this->baseDir . 'views/group/config_administrator.tpl');
        $this->config_administrator();
    }

    public function ajax_openclose_administrator() {
        $gkey = $this->getParam('gkey');


        $this->loadClass('models/logger');
        $this->loadClass('models/group');

        Whiteboard_Logger::getInstance()->debug('User try to open/close group: ' . $gkey, $this->userLogin);
        try {
            $group = new Whiteboard_Group($gkey);

            if ($this->userType == 'professor') {
                if (!$group->isOccupant($this->userLogin)) {
                    throw new Exception('not allowed');
                }
            }


            $this->loadClass('models/union');
            $union = new Union();
            if ($union->connect()) {
                if (!$union->login($this->userLogin, Union::createPassword($this->userLogin, $this->module->getCurrentUser()->user['id']))) {
                    throw new Exception('problem with login to union server');
                }
            } else {
                throw new Exception('problem with connection to union server');
            }
        } catch (Exception $e) {
            echo 'false';
            exit;
        }

        try {
            $open = $group->isOpen();
            if ($open) {
                $result = true; //$union->closeRoom(Union::getRoomID($gkey));
                $union->sendMsgToRoom(Union::getRoomID($gkey), Union::_MSG_SYSTEM, 'Zajęcia zakończyły się');

                if ($result) {
                    $result = $group->close();
                }
            } else {
                $result = $union->openRoom(Union::getRoomID($gkey));

                if ($result) {
                    $result = $group->open();
                }
            }
        } catch (Exception $e) {
            echo '<span style="color: red;">' . $e->getMessage() . '</span>';
            exit;
        }

        if ($result) {
            echo $open ? '0' : '1'; //switch state
        } else {
            echo 'false';
        }
        exit;
    }

    public function ajax_openclose_professor() {
        $this->ajax_openclose_administrator();
    }

    public function modify_administrator() {
        $gkey = $this->getParam('gkey', null);
        if (!$gkey) {
            $this->assign('TITLE', 'Dodaj grupę');
        } else {
            $this->assign('IS_MODIFY', 1);
            $this->smarty->assign('gkey', $gkey);
            $this->assign('TITLE', "Edycja grupy '<i>$gkey</i>'");
        }
        $this->assign('IS_ADMIN', $this->userType == 'administrator');

        $this->loadClass('models/config');
        $config = new Whiteboard_Config('group');
        $config = $config->getConfig('default');
        $this->loadClass('lib/time', 'module_applyschool');
        $startTime = ApplySchool_strtotimeInWarsaw($config['start_date']);

        $this->assign('START_TIME', $startTime);

        $this->loadClass('models/group.php');
        $group = new Whiteboard_Group($gkey);

        $pupils = $group->getPupils();
        $this->assign('PUPILS', $pupils);

        $professors = $group->getProfessors();
        $this->assign('PROFESSORS', $professors);

        if ($this->userType == 'professor' && $gkey) {
            $check = $group->isOccupant($this->userLogin);

            if (!$check) {
                echo '<span style="color: red">Brak uprawnień do modyfikowania grupy!</span>';
                header('refresh: 1; url=' . $this->getLinkToView('group', $this->getParam('go_back_to_schedule') ? 'schedule' : 'config'));
                exit;
            }
        }


        //form
        $this->loadClass('forms/group.php');
        $form = new Whiteboard_GroupForm($group->getCourses());

        //submit
        if ($form->isSubmitted()) {
            if ($form->validate()) {
                $saveData = $form->getSubmitValues();

                $group->setCourse($saveData['course_id']);
                $group->setNewGKey($saveData['gkey']);
                $group->setOptions($saveData);

                $result = $group->persist();

                if ($result) {
                    if (!$gkey && $this->userType == 'professor') {//if professor add new group then add him to group
                        $group->addRemoveUser($this->userLogin);
                    }

                    $this->assign('FORM_SUCCESS', true);
                } else {
                    $this->assign('FORM_ERROR', true);
                }
                header('refresh: 1; url= ' . $this->getLinkToView('group', $this->getParam('go_back_to_schedule') ? 'schedule' : 'config'));
            } else {
                //var_dump($form->_errors);
            }
        } else {
            if ($gkey) {
                $data = $group->getData();

                if ($data) {
                    $form->setDefaults($data);
                    $this->assign('DATA', $data);
                }
            }
        }

        $renderer = $form->setDefaultRenderer($this->smarty);
        $this->assign('FORM', $renderer->toArray());
    }

    public function modify_professor() {
        $this->assign('ADMIN_TPL', $this->baseDir . 'views/group/modify_administrator.tpl');
        $this->modify_administrator();
    }

    public function delete_administrator() {
        $gkey = $this->getParam('gkey', null);

        $this->loadClass('models/group');
        $group = new Whiteboard_Group($gkey);
        $group->delete();

        header('location:' . $_SERVER['HTTP_REFERER']);
    }

    public function undelete_administrator() {
        $gkey = $this->getParam('gkey', null);

        $this->loadClass('models/group');
        $group = new Whiteboard_Group($gkey);
        $group->undelete();

        header('location:' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    public function users_administrator() {
        $gkey = $this->getParam('gkey');
        if (!$gkey) {
            header('location:' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $this->smarty->assign('gkey', $gkey);

        $this->loadClass('models/group');
        $group = new Whiteboard_Group($gkey);

        $login = $this->getParam('login');
        if ($login) {
            if ($this->userType != 'administrator') {
                $user = EfrontUserFactory::factory($login);
                if ($user->user['user_type'] != 'student') {
                    echo '<span style="color: red;">brak uprawnień</span>';
                    exit;
                }
            }

            $result = $group->addRemoveUser($login);

            if ($this->getParam('ajax')) {
                echo $result === 1 ? 'false' : ($result === 0 ? 'true' : 'error');
                exit;
            }

            header('location:' . $_SERVER['HTTP_REFERER']);
            exit;
        } else {
            if ($this->userType == 'administrator') {
                $type = $this->getParam('type', 'student');
            } else {
                $type = 'student';
            }
            $this->smarty->assign('type', $type);

            $typeName = $type == 'student' ? 'uczestników' : 'profesorów';
            $this->assign('TITLE', "Wybierz $typeName grupy: $gkey");
            $this->assign('TPL', $this->baseDir . 'views/group/ajax_users_administrator.tpl');


            $this->_contextMenuInit();
        }
    }

    public function ajax_users_administrator() {
        $gkey = $this->getParam('gkey');
        if (!$gkey) {
            exit;
        }
        $this->smarty->assign('gkey', $gkey);

        if ($this->userType == 'administrator') {
            $type = $this->getParam('type', 'student');
        } else {
            $type = 'student';
        }

        $this->smarty->assign("T_MODULE_BASEURL", $this->baseUrl);
        $this->smarty->assign("T_APPLYSCHOOL_BASEURL", str_replace('module_whiteboard', 'module_applyschool', $this->baseUrl));

        $this->loadClass('models/user');
        if ($type == 'student')
            $data = Whiteboard_User::getStudentList($gkey, $this->userType != 'administrator' ? $this->userLogin : null);
        else
            $data = Whiteboard_User::getProfessorList(false, $gkey);

        $this->assign('DATA', $this->filter($data));
        $this->render(false);
    }

    public function users_professor() {
        $this->assign('ADMIN_TPL', $this->baseDir . 'views/group/users_administrator.tpl');
        $this->users_administrator();
    }

    public function ajax_users_professor() {
        $this->assign('ADMIN_TPL', $this->baseDir . 'views/group/ajax_users_administrator.tpl');
        $this->ajax_users_administrator();
    }

    public function history_administrator() {
        $gkey = $this->getParam('gkey');
        if (!$gkey) {
            header('location:' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $this->smarty->assign('gkey', $gkey);
        $this->assign('TITLE', 'Historia grupy: ' . $gkey);
        $this->assign('TPL', $this->baseDir . 'views/group/ajax_history_administrator.tpl');
    }

    public function ajax_history_administrator() {
        $gkey = $this->getParam('gkey');
        if (!$gkey) {
            exit;
        }
        $this->smarty->assign('gkey', $gkey);

        $this->smarty->assign("T_MODULE_BASEURL", $this->baseUrl);

        $this->loadClass('models/group');
        $group = new Whiteboard_Group($gkey);

        $data = $group->getHistory();

        $this->assign('DATA', $this->filter($data));
        $this->render(false);
    }

    public function ajax_changedate_administrator() {
        $gkey = $this->getParam('gkey');

        if (!$gkey) {
            echo 'błąd';
            exit;
        }
        if (!$_POST['date']) {
            echo 'brak danych';
            exit;
        }

        $this->loadClass('models/group');
        $group = new Whiteboard_Group($gkey);
        $result = $group->setNextMeeting($_POST['date'], true);

        if ($result === true) {
            echo '1';
        } elseif ($result === false) {
            echo '0';
        } else {
            echo $result;
        }
        exit;
    }

    public function ajax_changedate_professor() {
        $this->ajax_changedate_administrator();
    }

}


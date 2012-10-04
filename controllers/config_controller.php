<?php

namespace Whiteboard;

class Whiteboard_ConfigController extends Whiteboard_AbstractController {

    public function powers_administrator() {
        $this->assign('TPL', $this->baseDir . 'views/config/ajax_powers_administrator.tpl');
    }

    public function ajax_powers_administrator() {
        $this->smarty->assign("T_MODULE_BASEURL", $this->baseUrl);

        $this->loadClass('models/user');
        $data = User::getProfessorList(true);

        $this->assign('DATA', $this->filter($data));
        $this->render(false);
    }

    public function selectgroups_administrator() {
        $login = $this->getLogin();
        if (!$login) {
            header('location: ' . $this->getLinkToView('config', 'powers'));
            exit;
        }

        if ($gkey = $this->getParam('gkey')) {
            $this->loadClass('models/group');
            $group = new Group($gkey);
            $result = $group->addRemoveUser($login);
            
            if ($result !== false) {
                echo ''. $result;
            }
            exit;
        }

        $this->assign('LOGIN', $login);

        $this->assign('TPL', $this->baseDir . 'views/config/ajax_selectgroups_administrator.tpl');
        $this->assign('TITLE', 'Wybór grup dla użytkownika: ' . $login);
    }

    public function ajax_selectgroups_administrator() {
        $login = $this->getLogin();
        if (!$login) {
            header('location: ' . $this->getLinkToView('config', 'powers'));
            exit;
        }
        $this->assign('LOGIN', $login);

        $this->smarty->assign("T_MODULE_BASEURL", $this->baseUrl);

        $this->loadClass('models/group');
        $tmpData = Group::getList();
        $userGroups = Group::getUsersGroupList($login);
        
        $data = array();
        foreach ($tmpData as $row) {
            $data[] = array(
                'key' => $row['gkey'],
                'name' => $row['name'],
                'selected' => in_array($row['gkey'], $userGroups)
            );
        }

        $this->assign('DATA', $this->filter($data));
        $this->render(false);
    }

}


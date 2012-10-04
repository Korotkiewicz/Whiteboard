<?php
session_start();

/**
 * This cron update union room attribute (close/open room)
 * 
 * @website www.libratus.edu.pl/efront
 * @author Michał Korotkiewicz
 */
define('G_ROOTPATH',  '/libratus/efront/');
define('APPLICATION_PATH', G_ROOTPATH . 'www/modules/module_whiteboard/');
define("G_LESSONSPATH", G_ROOTPATH . 'www/content/lessons/');
define("G_UPLOADPATH", G_ROOTPATH . 'upload/');

define('G_DBTYPE', 'mysql');
/** The database Host */
define('G_DBHOST', 'fero.home.pl');
/** The database user */
define('G_DBUSER', 'fero6');
/** The database user password */
define('G_DBPASSWD', 'qwerty654321LIBRA');
/** The database name */
define('G_DBNAME', 'fero6');
/** The database tables prefix */
define('G_DBPREFIX', '');

$_SESSION['s_login'] = $userLogin = 'admin';
$userId = 1;

require_once(G_ROOTPATH . 'libraries/events.class.php');
require_once(G_ROOTPATH . 'libraries/tree.class.php');
require_once(G_ROOTPATH . 'libraries/content.class.php');
require_once(G_ROOTPATH . 'libraries/lesson.class.php');
require_once(G_ROOTPATH . 'libraries/user.class.php');
require_once(G_ROOTPATH . 'libraries/tools.php');
require_once(G_ROOTPATH . 'libraries/statistics.class.php');
require_once(G_ROOTPATH . 'libraries/database.php');

require_once(APPLICATION_PATH . 'models/group.php');
require_once(APPLICATION_PATH . 'models/union.php');

try {
    $groups = \Whiteboard\Group::getList(false, null, null, false, true);
    $history = eF_getTableData('(SELECT gkey, value, date FROM module_whiteboard_history WHERE what = \'state\' ORDER BY gkey, date DESC) AS h', 'gkey, date, value', null, null, 'gkey');

    $toOpen = array();
    $toClose = array();
    $fifteenMin = 900;  //60s * 15m
    $halfAnHour = 1800; //60s * 30m
    $sixHour = 21600; //60s * 60m * 6h

    $now = time();
    foreach ($groups as $group) {
        if ($group['closed']) {//check if should be open (should be open half an hour before lesson's scheduled date
            $time = strtotime($group['next_lesson']);

            if ($time - $halfAnHour <= $now) {
                $toOpen[] = $group['gkey'];
            }
        }
    }

    $now = time();
    foreach ($history as $group) {
        if ($group['value'] == 'closed') {//check if lesson was closed 6 hours ago
            $time = strtotime($group['date']);

            //echo $group['gkey'] . ': ' . $group['date'] . '<br/>';

            if ($now - $sixHour - $fifteenMin <= $time && $now - $sixHour + $fifteenMin > $time) {
                $toClose[] = $group['gkey'];
            }
        }
    }

    $result = 0;
    $union = new Union();
    if ($union->connect()) {
        echo 'UNION: connected<br/>';
        if ($union->login($userLogin, Union::createPassword($userLogin, $userId))) {
            echo 'UNION logged in<br/>';

            foreach ($toOpen as $gkey) {
                if ($union->openRoom(Union::getRoomID($gkey))) {
                    $result += 1;

                    $groupModel = new \Whiteboard\Group($gkey);
                    $groupModel->open();
                }
            }
            echo 'opened: ', $result, '<br/>';

            $result = 0;
            foreach ($toClose as $gkey) {
                if ($union->closeRoom(Union::getRoomID($gkey))) {
                    $result += 1;

                    $groupModel = new \Whiteboard\Group($gkey);
                    $groupModel->close();
                }
            }
            echo 'closed: ', $result, '<br/>';
        } else {
            echo 'problem with login<br/>';
        }
    } else {
        echo 'problem with connect';
    }

    echo 'end';
    exit;
} catch (Exception $e) {
    die('ERROR:' . $e->getMessage());
}
?>
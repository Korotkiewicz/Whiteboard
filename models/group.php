<?php

namespace Whiteboard;

//depend on:
require_once G_ROOTPATH . 'www/modules/module_applyschool/lib/time.php';
require_once G_ROOTPATH . 'www/modules/module_whiteboard/models/protect.php';

/**
 * require module_applyschool/lib/time.php
 * 
 * Model Whiteboard_Group CRUD group.
 * @copyright (c) 2012
 */
class Group extends Protect {

    protected static $table = 'module_whiteboard_group';
    protected $gkey;
    protected $newGKey;
    protected $courseId;
    protected $state;
    protected $options = array('course_id' => null, 'day_of_week' => null, 'time' => null, 'frequency' => null, 'shift_week' => null);

    const CHANGE_STATE = 'state';
    const CHANGE_USER = 'user';
    const USER_ENTER_INTO_ROOM = 'room';
    const CHANGE_DATA = 'data';

    //set in prepareStatic func
    protected static $prepared = false;
    protected static $startTime = false;
    protected static $now = false;
    protected static $nowDayOfWeek = false;
    protected static $nowHour = false;
    protected static $weeksFromStart = false;
    protected static $oneDaySeconds = 86400; //60 * 60 * 24

    /**
     * 
     * @param string $gkey this is group unique key
     */

    public function __construct($gkey) {
        $gkey = self::protectDB($gkey);

        $this->gkey = $gkey;
    }

    /**
     * Return information about all groups
     * 
     * @param boolean $pure
     * @return mixed return false or array
     */
    public function getData($pure = false) {
        if (!$this->gkey) {
            return false;
        }

        $table = self::$table . ' g';
        $columns = 'g.*';
        if (!$pure) {
            $table .= ' LEFT JOIN courses ON g.course_id = courses.id';
            $columns .= ', courses.name AS course_name';

            $table .= ' LEFT JOIN (SELECT * FROM module_whiteboard_group_next_meeting WHERE is_done = 0 GROUP BY gkey) AS nm ON g.gkey = nm.gkey';
            $columns .= ', nm.date AS next_lesson';
        }

        $data = eF_getTableData($table, $columns, "g.gkey = '{$this->gkey}'", null, null, 1);

        if ($data) {
            return $data[0];
        } else {
            return false;
        }
    }

    /**
     * Set courseId to object of this class.
     * 
     * @param string $courseId
     * @return boolean
     */
    public function setCourse($courseId) {
        $courseId = self::protectDB($courseId);
        if ($courseId) {
            $this->courseId = $courseId;
            return true;
        }

        return false;
    }

    /**
     * Set options of specified group
     * 
     * @param string $options
     * @return boolean
     */
    public function setOptions($options) {
        $course_id = null;
        $time = null;
        $day_of_week = null;
        $frequency = null;
        $shift_week = null;

        $options = self::arrayFromDB($options);
        extract($options, EXTR_IF_EXISTS);

        foreach ($this->options as $key => $value) {
            if (isset($$key)) {
                $this->options[$key] = $$key;
            }
        }

        return true;
    }

    /**
     * Set new gKey couse that method presis will try change gkey.
     * 
     * @param string $gkey
     * @return boolean
     */
    public function setNewGKey($gkey) {
        $gkey = self::protectDB($gkey);
        if ($gkey) {
            $this->newGKey = $gkey;
            return true;
        }

        return false;
    }

    /**
     * Insert or update group with set gkey, name, and state
     * If setNewGKey was invoked and new gkey is occupied then return false and
     * not persist other changes.
     * 
     * @return boolean
     */
    public function persist() {
        $data = $this->getData(true);

        if (!$data) {
            if (!$this->courseId) {
                throw new Exception('course must be set');
            }
            if (!$this->newGKey) {
                throw new Exception('gkey must be set');
            }

            $data = array(
                'gkey' => $this->newGKey,
                'course_id' => $this->courseId,
                'state' => 'closed'
            );

            $data = array_merge($data, $this->options);
            $changed = true;
        } else {
            $changed = false;
            if ($this->newGKey) {
                if ($this->newGKey != $this->gkey)
                    $changed = true;

                $data['gkey'] = $this->newGKey;
            }
            if ($this->courseId) {
                if ($data['course_id'] != $this->courseId)
                    $changed = true;

                $data['course_id'] = $this->courseId;
            }
            if ($this->state) {
                if ($data['state'] != $this->state)
                    $changed = true;

                $data['state'] = $this->state;
            }

            //check if is there difference in additional options
            $diff = array_diff_assoc($this->options, $data);
            unset($diff['gkey'], $diff['state'], $diff['course_id']);
            //if true then update DB
            if (!empty($diff)) {
                $changed = true;
                $data = array_merge($data, $this->options);
            }
        }

        if (!$changed) {
            return true;
        } else {
            $result = false;
            try {
//================ begin Transaction ===========================================
                $GLOBALS['db']->BeginTrans();
                eF_insertOrupdateTableData(self::$table, $data, "gkey = '$this->gkey'");


                if ($GLOBALS['db']->Affected_Rows() >= 0 or $GLOBALS['db']->Insert_ID()) {
                    if ($data['gkey']) {
                        if ($this->gkey && $this->gkey != $data['gkey']) {//update gkey in all related tables:
                            eF_updateTableData('module_whiteboard_user_to_groups', array('gkey' => $data['gkey']), "gkey = '$this->gkey'");
                            eF_updateTableData('module_whiteboard_group_next_meeting', array('gkey' => $data['gkey']), "gkey = '$this->gkey'");
                        }

                        $this->gkey = $data['gkey'];
                    }

                    $result = true;
                    $this->history(Group::CHANGE_DATA, serialize($data));
                } else {
                    $result = false;
                }
            } catch (Exception $e) {//exception may occure when try to duplicate gkey in DB
                $GLOBALS['db']->RollbackTrans();
                return false;
            }

            if ($result) {
//================ commit Transaction ===========================================
                $GLOBALS['db']->CommitTrans();
                return true;
            } else {
                $GLOBALS['db']->RollbackTrans();
                return false;
            }
        }

        return false; //err
    }

    /**
     * Return group history
     * 
     * @return array
     */
    public function getHistory() {
        $data = eF_getTableData('module_whiteboard_history', '*', "gkey = '{$this->gkey}'", 'date');

        return $data;
    }

    /**
     * Insert new history log
     * 
     * @param string $what
     * @param string $value
     * @return boolean
     */
    protected function history($what, $value) {
        $data = array(
            'gkey' => $this->gkey,
            'what' => self::protectDB($what),
            'value' => self::protectDB($value),
            'login' => $_SESSION['s_login'] //current user login
        );

        $id = eF_insertTableData('module_whiteboard_history', $data);

        if ($id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * This method should be use when user try to enter into room
     * 
     * @return boolean
     */
    public function enterRoom() {
        if (!$this->isOpen()) {
            return false;
        }

        $value = $_SERVER['HTTP_USER_AGENT'];
        $this->history(Group::USER_ENTER_INTO_ROOM, $value);

        return true;
    }

    /**
     * Check if group is deleted, set state in internal variable
     * 
     * @return boolean
     */
    public function isDeleted($forceCheck = false) {
        if (!$this->state || $forceCheck) {
            $data = $this->getData();

            if (!$data) {
                throw new Exception('Group not exists');
            }

            $this->state = $data['state'];
        }

        return $this->state == 'deleted';
    }

    /**
     * Delete group (set group state to deleted)
     * 
     * @return boolean
     */
    public function delete() {
        if (!$this->gkey) {
            return false;
        }

        eF_updateTableData(self::$table, array('state' => 'deleted'), "gkey = '{$this->gkey}'");
        if ($GLOBALS['db']->Affected_Rows() >= 0) {
            $this->state = 'deleted';

            $this->history(Group::CHANGE_STATE, $this->state);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Undelete group
     * 
     * @return boolean
     */
    public function undelete() {
        if (!$this->gkey) {
            return false;
        }

        eF_updateTableData(self::$table, array('state' => 'closed'), "gkey = '{$this->gkey}'");
        if ($GLOBALS['db']->Affected_Rows() >= 0) {
            $this->state = 'closed';

            $this->history(Group::CHANGE_STATE, $this->state);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if group is open, set state in internal variable which is also modified by method: open() and close()
     * 
     * @return boolean
     */
    public function isOpen($forceCheck = false) {
        if (!$this->state || $forceCheck) {
            $data = $this->getData();

            if (!$data) {
                throw new Exception('Group not exists');
            }

            $this->state = $data['state'];
        }

        return $this->state == 'open';
    }

    /**
     * Try to open group and set variable $this->state to 'open'
     * 
     * @return boolean
     */
    public function open() {
        if ($this->isOpen()) {
            return true;
        }

        eF_updateTableData(self::$table, array('state' => 'open'), "gkey = '{$this->gkey}'");
        if ($GLOBALS['db']->Affected_Rows() >= 0) {
            $this->state = 'open';

            $this->history(Group::CHANGE_STATE, $this->state);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Try to close group and set variable $this->state to 'closed'
     * 
     * @return boolean
     */
    public function close() {
        if (!$this->isOpen()) {
            return true;
        }

        eF_updateTableData(self::$table, array('state' => 'closed'), "gkey = '{$this->gkey}'");
        if ($GLOBALS['db']->Affected_Rows() >= 0) {
            $this->state = 'closed';

            eF_updateTableData('module_whiteboard_group_next_meeting', array('is_done' => 1), "gkey = '{$this->gkey}'");
            $this->history(Group::CHANGE_STATE, $this->state);
            return true;
        } else {
            return false;
        }
    }

    /**
     * This method add or remove user from group (occupant) (depend on previous state (add if not added, remove if added))
     * Method return 0 if after operations user is removed and 1 if user is added, return false if error
     * 
     * @param string $login
     * @return int
     */
    public function addRemoveUser($login) {
        $login = self::protectDB($login);
        if (!$login) {
            return false;
        }

        if ($this->isOccupant($login)) {
            if (eF_deleteTableData('module_whiteboard_user_to_groups', "login = '$login' AND gkey = '{$this->gkey}'")) {

                $this->history(Group::CHANGE_USER, 'remove: ' . $login);
                return 0;
            } else {
                return false;
            }
        } else {
            $fields = array(
                'login' => $login,
                'gkey' => $this->gkey
            );

            if (eF_insertTableData('module_whiteboard_user_to_groups', $fields)) {

                $this->history(Group::CHANGE_USER, 'add: ' . $login);
                return 1;
            } else {
                return false;
            }
        }
    }

    /**
     * Check if user is occupant of this group (is added to group
     * 
     * @param srting $login
     * @return boolean
     */
    public function isOccupant($login) {
        $login = self::protectDB($login);
        if (!$login) {
            return false;
        }

        $where = "login = '$login' AND gkey = '{$this->gkey}'";
        $data = eF_getTableData('module_whiteboard_user_to_groups', '*', $where, null, null, 1);

        return ($data ? true : false);
    }

    public function setNextMeeting($date, $returnWarnings = false) {
        $date = str_replace('.', ':', $date);

        if (!preg_match('/^\d{4}[-]\d{2}[-]\d{2} \d{2}([:]\d{2}([:]\d{2})?)?$/', $date, $matches)) {
            if ($returnWarnings) {
                return 'zły format: ' . $date;
            } else {
                return false;
            }
        }

        if (!$matches[1]) {
            $date .= ':00:00';
        } elseif (!$matches[2]) {
            $date .= ':00';
        }

        $originalDate = $this->getMeetingDateInWeek(null, true);

        $where = "gkey = '{$this->gkey}' AND is_done = 0";

        if ($originalDate == $date) {
            eF_deleteTableData('module_whiteboard_group_next_meeting', $where);
            if ($returnWarnings) {
                return 'original';
            } else {
                return true;
            }
        }

        $fields = array(
            'gkey' => $this->gkey,
            'date' => $date,
            'is_done' => 0
        );

        try {
            eF_insertOrupdateTableData('module_whiteboard_group_next_meeting', $fields, $where);
        } catch (Exception $e) {
            if ($e->getCode() == 1062) {
                eF_updateTableData('module_whiteboard_group_next_meeting', array('is_done' => 0), "gkey = '{$this->gkey}' AND date = '$date'");
            }
        }

        return true;
    }

    /**
     * Return array with occupant list
     * 
     * @return array
     */
    public function getOccupantList() {
        $data = eF_getTableData('module_whiteboard_user_to_groups utog 
                                JOIN users ON utog.login = users.login
                                LEFT JOIN module_applyschool_data AS data ON users.login = data.users_login
                                LEFT JOIN module_applyschool_countries AS country ON data.address_country_code = country.code', 'users.login, users.user_type, users.avatar, users.timezone, IF(data.student_name, data.student_name, users.name) AS name, IF(data.student_surname, data.student_surname, users.surname) AS surname, country.code AS country_code, country.name AS country_name, users.email, users.Skype, if(data.cellphone_number1, data.cellphone_number1, users.Telefon) AS Telefon', "gkey = '{$this->gkey}'");

        $defaultTimezone = date('e');
        for ($i = 0, $count = count($data); $i < $count; ++$i) {
            date_default_timezone_set($data[$i]['timezone']);
            $data[$i]['timezoneOffset'] = date('P');
        }
        date_default_timezone_set($defaultTimezone);

        return $data;
    }

    /**
     * This method return users (type == 'student') who are added to particular group
     * 
     * @return array
     */
    public function getPupils() {
        if (!$this->gkey) {
            return false;
        }

        $data = eF_getTableData('module_whiteboard_user_to_groups utog JOIN users ON utog.login = users.login AND users.user_type = \'student\'', 'users.login, users.name, users.surname', "utog.gkey = '{$this->gkey}'", 'users.surname, users.name');

        return $data;
    }

    /**
     * This method return users (type == 'professor') who are added to particular group
     * 
     * @return array
     */
    public function getProfessors() {
        if (!$this->gkey) {
            return false;
        }

        $data = eF_getTableData('module_whiteboard_user_to_groups utog JOIN users ON utog.login = users.login AND users.user_type = \'professor\'', 'users.login, users.name, users.surname', "utog.gkey = '{$this->gkey}'", 'users.surname, users.name');

        return $data;
    }

    /**
     * 
     * 
     * @param string $weekNo
     * @param boolean $returnOnlyOriginal if true then return only scheduled date (not set manualy by teacher (if one is set))
     * @return mixed return false if you try to get information about past week (weekNo). Normally return date string (Y-m-d H:i:s) of next meeting
     */
    public function getMeetingDateInWeek($weekNo = null, $returnOnlyOriginal = false) {
        $this->loadModel('week', 'module_features');
        $weekModel = new ModuleFeatures_WeekModel();
        $actualWeekNo = $weekModel->getActualWeekNo();

        if (is_null($weekNo)) {
            $weekNo = $actualWeekNo;
        }

        if ($weekNo < $actualWeekNo) {
            return false;
        }

        $data = $this->getData();

        if ($returnOnlyOriginal) {
            unset($data['next_lesson']);
        }

        $nextLessonInPostedWeek = $this->getNextLessonDate($data['day_of_week'], $data['time'], $data['frequency'], $data['shift_week'], $weekNo - $actualWeekNo);
        if (!$data['next_lesson']) {
            return $nextLessonInPostedWeek;
        } else {
            $nextLesson = $this->getNextLessonDate($data['day_of_week'], $data['time'], $data['frequency'], $data['shift_week']);

            //if next_lesson from now is the same as next lesson in posted weekNo then return custom next_lesson set in $data:
            if ($nextLesson == $nextLessonInPostedWeek) {
                return $data['next_lesson'];
            }

            return $nextLessonInPostedWeek;
        }
    }

    /**
     * This method is used to prepare static vars on load
     * 
     * @param bool $force
     * @return boolean
     */
    public static function prepareStatic($force = false) {
        if ($force || !self::$prepared) {
            self::staticLoadModel('config');
            $config = new Config('group');
            $config = $config->getConfig('default');

            self::staticLoadModel('../lib/time', 'module_applyschool');
            self::$startTime = ApplySchool_strtotimeInWarsaw($config['start_date']);
            // self::$startTime = strtotime($config['start_date']);
            self::$now = ApplySchool_getTimeInWarsaw();
            //self::$now = time();
            self::$nowDayOfWeek = intval(ApplySchool_getDateInWarsaw('w'));
            self::$nowHour = intval(ApplySchool_getDateInWarsaw('H'));

            self::$weeksFromStart = intval((self::$now - self::$startTime) / self::$oneDaySeconds / 7); //7 days

            self::$prepared = true;
        }

        return true;
    }

    /**
     * Static function getList 
     * 
     * if $mapped == true then return array(group key => group name, ...)
     * else return array((0 => (gkey, name)...)
     * @param boolean mapped
     * @return array
     */
    public static function getList($mapped = false, $occupantLogin = null, $onlyOccupant = false, $showDeleted = false, $addNextMeetingInfo = false) {
        $table = self::$table . ' g LEFT JOIN courses ON g.course_id = courses.id';
        $columns = 'g.gkey, g.course_id, g.state, g.day_of_week, g.time, g.frequency, g.shift_week, g.course_id, courses.name AS course_name';
        $where = null;
        if (!$showDeleted) {
            $where = "g.state != 'deleted'";
        }

        $occupantLogin = self::protectDB($occupantLogin);
        if ($occupantLogin) {
            if (!$onlyOccupant) {
                $table .= ' LEFT';
            }
            $table .= ' JOIN module_whiteboard_user_to_groups AS utog ON g.gkey = utog.gkey AND utog.login = \'' . $occupantLogin . "'";
            $columns .= ', IF(utog.login IS NULL, 1, 0) AS is_not_allowed';
        }

        if ($addNextMeetingInfo) {
            $table .= ' LEFT JOIN (SELECT * FROM module_whiteboard_group_next_meeting WHERE is_done = 0 GROUP BY gkey) AS nm ON g.gkey = nm.gkey';
            $columns .= ', nm.date AS next_lesson';
        }

        $table .= ' LEFT JOIN (SELECT gkey, COUNT(utog_tmp1.login) AS count FROM module_whiteboard_user_to_groups AS utog_tmp1 JOIN users ON utog_tmp1.login = users.login AND users.user_type = \'student\' GROUP BY gkey) AS pupils ON g.gkey = pupils.gkey';
        $columns .= ', IF(pupils.count IS NOT NULL, pupils.count, 0) AS pupils_count';

        $table .= ' LEFT JOIN (SELECT gkey, COUNT(utog_tmp2.login) AS count FROM module_whiteboard_user_to_groups AS utog_tmp2 JOIN users ON utog_tmp2.login = users.login AND users.user_type = \'professor\' GROUP BY gkey) AS professor ON g.gkey = professor.gkey';
        $columns .= ', IF(professor.count IS NOT NULL, professor.count, 0) AS professor_count';

        $table .= ' LEFT JOIN (SELECT history_tmp.gkey, history_tmp.date, history_tmp.what FROM (SELECT gkey, date, what FROM module_whiteboard_history ORDER BY date DESC) AS history_tmp GROUP BY history_tmp.gkey ) AS history ON g.gkey = history.gkey';
        $columns .= ', history.date AS last_history_date, history.what AS last_history_what';

        $groups = eF_getTableData($table, $columns, $where); //, "g.state != 'deleted'");

        if (!$mapped) {
            $data = $groups;

            if ($addNextMeetingInfo) {
                foreach ($data as $i => $row) {
                    if (!$row['next_lesson']) {
                        $data[$i]['next_lesson'] = self::getNextLessonDate($row['day_of_week'], $row['time'], $row['frequency'], $row['shift_week']);
                        $data[$i]['next_meeting_date_changed'] = 0;
                    } else {
                        $data[$i]['next_meeting_date_changed'] = 1;
                    }
                }
            }

            return $data;
        } else {
            $data = array();

            foreach ($groups as $group) {
                $data[$group['gkey']] = $group['course_id'];
            }
        }

        return $data;
    }

    /**
     * Prepare next date of meeting depending on given params
     * 
     * @param int $day_of_week
     * @param int $time
     * @param int $frequency
     * @param int $shift_week
     * @param int $fromWeeksInFuture
     * @return mixed return false if it is not resolved
     */
    public static function getNextLessonDate($day_of_week, $time, $frequency, $shift_week, $fromWeeksInFuture = 0) {
        self::prepareStatic();

        $now = self::$now + $fromWeeksInFuture * self::$oneDaySeconds * 7;

        if ($now > self::$startTime) {
            $day_of_week = intval($day_of_week);
            $frequency = intval($frequency);
            $frequency = $frequency ? $frequency : 1;
            $shift_week = intval($shift_week);

            $tmpWeeksFromStart = self::$weeksFromStart + $fromWeeksInFuture;
            $nextGroupTime = 0;

            if ($tmpWeeksFromStart >= $shift_week) {
                $tmpWeeksFromStart -= $shift_week;
            } else {
                $nextGroupTime += ($shift_week - $tmpWeeksFromStart) * self::$oneDaySeconds * 7;
                $tmpWeeksFromStart = 0;
            }

            $weeks = $tmpWeeksFromStart % $frequency;

            if ($weeks == 0) {
                if ($day_of_week - self::$nowDayOfWeek <= 0) {
                    if ($day_of_week - self::$nowDayOfWeek < 0 || self::$nowHour >= intval(substr($time, 0, 2))) {
                        $weeks = 1;
                    }
                }
            } else {
                $weeks = $frequency - $weeks;
            }


            $nextGroupTime += $now + $weeks * self::$oneDaySeconds * 7 + ($day_of_week - self::$nowDayOfWeek) * self::$oneDaySeconds;

            return date('Y-m-d', $nextGroupTime) . ' ' . $time;
        } else {
            return false;
        }
    }

    /**
     * Return user's group info
     * 
     * @param string $login
     * @return boolean
     */
    public static function getUsersGroupList($login) {
        $login = self::protectDB($login);
        if (!$login) {
            return false;
        }

        $groups = eF_getTableDataFlat('module_whiteboard_user_to_groups', 'gkey', "login = '$login'");

        return $groups['gkey'];
    }

    /**
     * 
     * @return array
     */
    public static function getAllGroupInfo() {
        $groups = eF_getTableData(self::$table . ' g LEFT JOIN courses ON g.course_id = courses.id', 'g.gkey, g.course_id, g.state, IF(courses.name IS NOT NULL, courses.name, g.gkey) AS course_name', "state != 'deleted'");
        return $groups;
    }

    /**
     * 
     * @param type $login
     * @return mixed return false if login not set correctly
     */
    public static function getUsersGroupInfo($login) {
        $login = self::protectDB($login);
        if (!$login) {
            return false;
        }

        $groups = eF_getTableData('module_whiteboard_user_to_groups utog JOIN ' . self::$table . ' g ON utog.gkey = g.gkey LEFT JOIN courses ON g.course_id = courses.id', 'g.gkey, g.course_id, g.state, IF(courses.name IS NOT NULL, courses.name, g.gkey) AS course_name', "login = '$login' AND state != 'deleted'");

        for ($i = 0, $count = count($groups); $i < $count; ++$i) {
            $group = new self($gkey);
            $groups[$i]['next_lesson'] = $group->getMeetingDateInWeek();
        }

        return $groups;
    }

    /**
     * 
     * @return mixed return false if course not exists. Normally return assoc array(courseId => name,...)
     */
    public static function getCourses() {
        $where = 'active = 1 AND archive = 0';

        $data = eF_getTableData('courses', 'id, name', $where, 'name');
        if (!$data) {
            return false;
        } else {
            $return = array();

            foreach ($data as $row) {
                $return[$row['id']] = $row['name'];
            }

            return $return;
        }
    }

}
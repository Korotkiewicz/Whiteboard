<?php

require_once G_ROOTPATH . 'www/modules/module_whiteboard/models/protect.php';

class Whiteboard_Faq extends Whiteboard_Protect {

    protected static $questionTable = 'module_whiteboard_faq_question';
    protected static $answerTable = 'module_whiteboard_faq_answer';
    protected $login = null;
    protected $gkey = null;
    protected $view = 'student';
    protected static $posibleView = array('student' => true, 'professor' => true, 'administrator' => true);

    /**
     *
     * @param string $gkey group key
     * @param string $login user login
     * @param string $view view of question: student, professor, administrator
     */
    public function __construct($gkey, $login, $view = null) {
        $gkey = self::protectDB($gkey);
        $login = self::protectDB($login);

        $this->gkey = $gkey;
        $this->login = $login;

        if (!is_null($view) && self::$posibleView[$view]) {
            $this->view = $view;
        }
    }

//============================= QUESTION =======================================
    /**
     * 
     * @return array if gkey has not been set return false
     */
    public function getQuestions($fromWeek = null) {
        return $this->getGroupsQuestions($this->gkey, $fromWeek);
    }

    public function getQuestion($id) {
        $id = intval($id);

        if (!$id) {
            return false;
        }

        $columns = 'q.*, IF(a.id IS NOT NULL, 1, 0) AS has_answer';
        $where = "q.gkey = '{$this->gkey}' AND q.id = $id";
        if ($this->view == 'student') {
            $where .= " AND q.login = '{$this->login}'";
            $columns .= ', 1 AS is_author';
        } else {
            $columns .= ", IF(q.login = '{$this->login}', 1, 0) AS is_author";
        }

        $data = eF_getTableData(self::$questionTable . ' AS q LEFT JOIN ' . self::$answerTable . ' AS a ON q.id = a.question_id', $columns, $where);

        return $data ? $data[0] : false;
    }

    public function saveQuestion($newData, $id = null) {
        $newData = self::arrayFromDB($newData);
        $id = intval($id);

        if ($id) {
            $oldData = $this->getQuestion($id);

            if (!$oldData) {
                return false;
            }
        }

        $saveData = array(
            'week' => intval($newData['week']),
            'question' => $newData['question'],
            'modified' => new Whiteboard_DB_Expr('NOW()'),
            'login' => $this->login,
            'gkey' => $this->gkey
        );

        if ($id) {
            $where = "gkey = '{$this->gkey}' AND id = $id";
            if ($this->view == 'student')
                $where .= " AND login = '{$this->login}'";

            eF_updateTableData(self::$questionTable, $saveData, $where);

            if ($GLOBALS['db']->Affected_Rows() >= 0 or $GLOBALS['db']->Insert_ID()) {
                return true;
            } else {
                return false;
            }
        } else {
            $id = eF_insertTableData(self::$questionTable, $saveData);

            if ($id) {
                return true;
            } else {
                return false;
            }
        }

        return false; //err
    }

    public function removeQuestion($id) {
        $id = intval($id);
        if (!$id) {
            return false;
        }
        $oldData = $this->getQuestion($id);

        if (!$oldData) {
            return false;
        }

        return eF_deleteTableData(self::$questionTable, "id = $id") ? true : false;
    }

    /**
     * 
     * @return array if gkey is not set return false
     */
    public function getGroupsQuestions($gkey, $fromWeek = null) {
        $gkey = self::protectDB($gkey);
        if (!$gkey) {
            return false;
        }

        $columns = 'q.*, IF(a.id IS NOT NULL, 1, 0) AS has_answer, IF(a.id IS NOT NULL, a.version, \'no_answer\') AS state';
        $where = "q.gkey = '{$gkey}'";
        if ($this->view == 'student') {
            $where .= " AND q.login = '{$this->login}'";
            $columns .= ', 1 AS is_author';
        } else {
            $columns .= ", IF(q.login = '{$this->login}', 1, 0) AS is_author";
        }

        if ($fromWeek) {
            $where .= ' AND q.week >= ' . $fromWeek;
        }

        $data = eF_getTableData(self::$questionTable . ' AS q LEFT JOIN ' . self::$answerTable . ' AS a ON q.id = a.question_id', $columns, $where, 'q.week ASC');

        return $data;
    }

    public function getWeeksForWhichUserCanAskQuestion() {
        $this->loadModel('week', 'module_features');
        $weekModel = new ModuleFeatures_WeekModel();
        $weekNo = $weekModel->getActualWeekNo();
        $maxWeekNo = $weekModel->getMaxWeekNo();
        
        $this->loadModel('group');
        $groupModel = new Whiteboard_Group($this->gkey);

        $return = array();
        $oldDate = '';
        $oldNo = '';
        for ($no = $weekNo; $no <= $maxWeekNo && $no <= $weekNo + 8; ++$no) {
            $date = $groupModel->getMeetingDateInWeek($no);
            $return[$no] = 'Tydzień ' . $no;

            if ($date != $oldDate && $oldNo) {
                $return[$oldNo] .= ' - spotkanie: ' . $oldDate;
            }

            $oldNo = $no;
            $oldDate = $date;
        }

        $return[$oldNo] .= ' - spotkanie: ' . $oldDate;
        //todo serve issue when meeting for last week has not happend yet

        return $return;
    }

    //============================== ANSWER =======================================   

    public function getAnswer($id) {
        $id = intval($id);

        if (!$id) {
            return false;
        }

        $columns = '*';
        $where = "gkey = '{$this->gkey}' AND id = $id";

        $data = eF_getTableData(self::$answerTable, $columns, $where);

        return $data ? $data[0] : false;
    }

    public function getAnswerByQuestionId($questionId) {
        $questionId = intval($questionId);

        if (!$questionId) {
            return false;
        }

        $columns = '*';
        $where = "gkey = '{$this->gkey}' AND question_id = $questionId";

        $data = eF_getTableData(self::$answerTable, $columns, $where);

        return $data ? $data[0] : false;
    }

    public function saveAnswer($newData, $id = null) {
        $newData = self::arrayFromDB($newData);
        $id = intval($id);

        if ($id) {
            $oldData = $this->getAnswer($id);

            if (!$oldData) {
                return false;
            }

            $this->archiveAnswer($oldData);
        }

        $saveData = array(
            'answer' => $newData['answer'],
            'gkey' => $this->gkey,
            'login' => $this->login,
            'question_id' => $newData['question_id'],
            'question' => $newData['question'],
            'week' => intval($newData['week']),
            'modified' => new Whiteboard_DB_Expr('NOW()')
        );

        if ($newData['version']) {
            if ($newData['version'] == 'public') {
                $saveData['version'] = 'public';

//================ if public version then add answer to module_faq =============
                $this->loadModel('group');
                $group = new Whiteboard_Group($this->gkey);
                $gData = $group->getData();

                if ($oldData) {//try to get lessons_ID
                    $faqId = $oldData['module_faq_id'];

                    if ($faqId) {
                        $faqData = eF_getTableData('module_faq', '*', "id = $faqId", null, null, 1);

                        if ($faqData) {
                            $lessonId = $faqData[0]['lessons_ID'];
                        }
                    }
                }

                if (!$lessonId) {//if not set then proceding lessons_ID by course id
                    $lessonId = $this->getLessonId($gData['course_id'], $saveData['week']);
                }

                $fields = array(
                    'lessons_ID' => $lessonId,
                    'unit_ID' => 0,
                    'question' => $saveData['question'],
                    'answer' => $saveData['answer']
                );


                if ($faqId) {
                    eF_updateTableData("module_faq", $fields, "id = $faqId");

                    if ($GLOBALS['db']->Affected_Rows() >= 0)
                        $saveData['module_faq_id'] = $faqId;
                } else {
                    $faqId = eF_insertTableData("module_faq", $fields);

                    if ($faqId)
                        $saveData['module_faq_id'] = $faqId;
                }
//================== end add answer to module_faq fields =======================
            } else {
                $saveData['version'] = 'draft';

//============= if draft version then remove answer from module_faq ============
                if ($oldData && $oldData['version'] == 'public') {
                    $faqId = $oldData['module_faq_id'];

                    if ($faqId) {
                        if(eF_deleteTableData('module_faq', "id = $faqId")) {
                            $saveData['module_faq_id'] = new Whiteboard_DB_Expr('NULL');
                        }
                    }
                }
//=================== end remove answer from module_faq ========================
            }
        }

        if ($id) {
            $where = "gkey = '{$this->gkey}' AND id = $id";

            eF_updateTableData(self::$answerTable, $saveData, $where);
            if ($GLOBALS['db']->Affected_Rows() >= 0 or $GLOBALS['db']->Insert_ID()) {
                return true;
            } else {
                return false;
            }
        } else {
            $id = eF_insertTableData(self::$answerTable, $saveData);

            if ($id) {
                return true;
            } else {
                return false;
            }
        }

        return false; //err
    }

    public function archiveAnswer($dataToArchive) {
        $id = @eF_insertTableData(self::$answerTable . '_archive', $dataToArchive);

        if ($id) {
            return true;
        } else {
            return false;
        }
    }

    public function getLessonId($course_id, $week) {
        $week = intval($week);
        $course_id = intval($course_id);

        if (!$week || !$course_id) {
            return false;
        }

        if ($week < 10) {
            $week = 'Tydzień 0' . $week;
        } else {
            $week = 'Tydzień ' . $week;
        }


        $data = eF_getTableData('lessons_to_courses JOIN lessons ON lessons_to_courses.lessons_ID = lessons.id', 'lessons.id', "lessons_to_courses.courses_ID = $course_id AND lessons.name LIKE '%$week%'", null, null, 1);

        if ($data) {
            return $data[0]['id'];
        } else {
            return false;
        }
    }

}
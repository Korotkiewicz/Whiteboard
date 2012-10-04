<?php

namespace Whiteboard;

require_once G_ROOTPATH . 'www/modules/module_whiteboard/models/protect.php';
/**
 * This class has method to retrive information about user from DB:
 * 
 * + getProfessorList
 * + getStudentList
 * + getTeachers
 * 
 * @author Michal Korotkiewicz
 * @copyright (c) 2012
 */
class User extends Protect {

    /**
     * 
     * @param bool $getGroupInfo
     * @param string $gkey
     * @return array
     */
    public static function getProfessorList($getGroupInfo = false, $gkey = null) {
        $table = 'users u';
        $columns = 'u.*';
        
        if ($gkey) {
            $table .= ' LEFT JOIN module_whiteboard_user_to_groups utog ON u.login = utog.login AND utog.gkey = \'' . $gkey . "'";
            $columns .= ', IF(utog.login IS NOT NULL, 1, 0) AS is_added';
        }
        
        $data = eF_getTableData($table, $columns, "u.user_type = 'professor' AND u.active", 'name ASC');

        if ($getGroupInfo) {
            $groups = eF_getTableData('module_whiteboard_user_to_groups utog JOIN module_whiteboard_group g ON utog.gkey = g.gkey', 'utog.login, g.gkey');
            $userToGroup = array();

            foreach ($groups as $row) {
                $userToGroup[$row['login']][$row['gkey']] = $row['gkey'];
            }

            foreach ($data as $key => $row) {
                $data[$key]['groups'] = $userToGroup[$row['login']];
                $data[$key]['countgroups'] = $data[$key]['groups'] ? count($data[$key]['groups']) : 0;
            }
        }
        return $data;
    }

    /**
     * 
     * @param string $gkey
     * @param string $professorLogin
     * @return array
     */
    public static function getStudentList($gkey = null, $professorLogin = null) {
        $table = 'users u JOIN module_applyschool_data AS data ON u.login = data.users_login';
        $columns = 'u.*, data.*';
        $where = "u.user_type = 'student' AND u.active";

        $gkey = self::protectDB($gkey);
        if ($gkey) {
            $table .= ' LEFT JOIN module_whiteboard_user_to_groups utog ON u.login = utog.login AND utog.gkey = \'' . $gkey . "'";
            $columns .= ', IF(utog.login IS NOT NULL, 1, 0) AS is_added';
        }
        
        $professorLogin = self::protectDB($professorLogin);
        if($professorLogin) {
            static::staticLoadModel('subject', 'module_features');
            //$table .= ' LEFT JOIN ';
            
            $subjects = ModuleFeatures_SubjectModel::getSubjects($professorLogin, false);
            
            $lessons = array();
            $courses = array();
            $classes = array();
            foreach($subjects as $subject) {
                $subject_id = $subject['subject_id'];
                $subjectModel = new ModuleFeatures_SubjectModel($subject_id);
                $subjectData = $subjectModel->getData();
                
                if($subjectData['is_course'] == '1') {
                    $courses[] = $subjectData['course_or_lesson_id'];
                } else {
                    $lessons[] = $subjectData['course_or_lesson_id'];
                }
                
                $classes[] = ($subject['type'] == 'PRIMARY' ? 'p' : 'g') . $subject['class'];
            }
            
            if($lessons) {
                $table .= ' LEFT JOIN (SELECT users_LOGIN FROM users_to_lessons WHERE lessons_ID IN (' . implode(',', $lessons) . ') GROUP BY users_LOGIN) AS utol ON utol.users_LOGIN = u.login';
                if($courses) {
                    $where .= ' AND (utol.users_LOGIN IS NOT NULL OR utoc.users_LOGIN IS NOT NULL)';
                } else {
                    $where .= ' AND utol.users_LOGIN IS NOT NULL';
                }
            }
            if($courses) {
                $table .= ' LEFT JOIN (SELECT users_LOGIN FROM users_to_courses WHERE courses_ID IN (' . implode(',', $courses) . ') GROUP BY users_LOGIN) AS utoc ON utoc.users_LOGIN = u.login';
                if(!$lessons) {
                    $where .= ' AND utoc.users_LOGIN IS NOT NULL';
                }
            }
            
            if($classes) {
                $table .= ' JOIN module_applyschool_data_school AS ds ON u.login = ds.users_login AND ds.class IN (\'' . implode("','", $classes) . "')";
            }
        }

        $data = eF_getTableData($table, $columns, $where, 'student_surname ASC');

        return $data;
    }

    /**
     * 
     * @return array
     */
    public static function getTeachers() {
        $data = eF_getTableData('module_whiteboard_user_to_groups AS g JOIN users ON g.login = users.login AND users.user_type = \'professor\'');
        
        $return = array();
        
        foreach($data as $row) {
            $return[$row['login']][] = $row['gkey'];
        }
        
        return $return;
    }
}
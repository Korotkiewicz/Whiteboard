<?php

namespace Whiteboard;

class FaqController extends AbstractController {

    public function index() {
        if ($this->userType == 'student') {
            $this->assign('TITLE', 'Zgłaszanie pytań');
        } else {
            $this->assign('TITLE', 'Pytania i odpowiedzi');
        }

        $this->assign('TPL', $this->baseDir . 'views/faq/ajax_questionlist.tpl');
    }

    public function ajax_questionlist_student() {
        $this->assign('TPL', $this->baseDir . 'views/faq/ajax_questionlist.tpl');
        $this->ajax_questionlist();
    }

    public function ajax_questionlist_professor() {
        $this->assign('TPL', $this->baseDir . 'views/faq/ajax_questionlist.tpl');
        $this->ajax_questionlist(true);
    }

    public function ajax_questionlist_administrator() {
        $this->assign('TPL', $this->baseDir . 'views/faq/ajax_questionlist.tpl');
        $this->ajax_questionlist(true);
    }

    public function ajax_questionlist($isAdmin = false) {
        $this->smarty->assign('T_MODULE_BASEURL', $this->baseUrl);

        $data = array();

        $gkey = $this->getParam('questionlistDataTableSubSection_source');
        if (!$gkey) {
            $this->loadClass('models/group');
            if ($this->userType == 'administrator') {
                $data = Group::getAllGroupInfo();
            } else {
                $data = Group::getUsersGroupInfo($this->userLogin);
            }
        } else {
            $this->loadClass('models/faq');
            $model = new Faq($gkey, $this->userLogin, $this->userType);

            $this->loadClass('models/week', 'module_features');
            $weekModel = new ModuleFeatures_WeekModel();
            $actualWeekNo = $weekModel->getActualWeekNo();

            //todo serve issue when lesson hadn't happend
            $data = $model->getQuestions($actualWeekNo);
        }

        $this->assign('USER_TYPE', $this->userType);
        $this->assign('DATA', $this->filter($data));
        $this->render(false);
        exit;
    }

    public function delete_question() {
        $gkey = $this->getParam('gkey');
        $qid = $this->getParam('qid');

        $this->loadClass('models/group');
        $gmodel = new Group($gkey);
        if (!$gmodel->isOccupant($this->userLogin)) {
            echo 'false';
            exit;
        }

        $this->loadClass('models/faq');
        $model = new Faq($gkey, $this->userLogin);

        if ($model->removeQuestion($qid)) {
            echo '1';
        } else {
            echo '0';
        }
        exit;
    }

    public function ask() {
        $gkey = $this->getParam('gkey');
        $qid = $this->getParam('qid');

        $this->loadClass('models/group');
        $gmodel = new Group($gkey);
        if (!$gmodel->isOccupant($this->userLogin)) {
            header('location: ' . $this->getLinkToView('faq', null, 'index'));
            exit;
        }


        $this->loadClass('models/faq');
        $model = new Faq($gkey, $this->userLogin);
        $weeks = $model->getWeeksForWhichUserCanAskQuestion();

        //form
        $this->loadClass('forms/question.php');
        $form = new QuestionForm($weeks);

        //submit
        if ($form->isSubmitted()) {
            if ($form->validate()) {
                $saveData = $form->getSubmitValues();

                $result = $model->saveQuestion($saveData, $qid);

                if ($result) {
                    $this->assign('FORM_SUCCESS', true);
                } else {
                    $this->assign('FORM_ERROR', true);
                }
                header('refresh: 1; url= ' . $this->getLinkToView('faq', null, 'index'));
            } else {
                //var_dump($form->_errors);
            }
        } else {
            if ($qid) {
                $data = $model->getQuestion($qid);

                if ($data) {
                    $form->setDefaults($data);
                    $this->assign('DATA', $data);
                }
            } else {
                $week = $this->getParam('week');

                if (isset($weeks[$week])) {
                    $form->setDefaults(array('week' => $week));
                }
            }
        }

        $renderer = $form->setDefaultRenderer($this->smarty);
        $this->assign('FORM', $renderer->toArray());
    }

    public function answer_professor($isAdmin = false) {
        global $load_editor;
        $load_editor = true;

        $gkey = $this->getParam('gkey');
        $qid = $this->getParam('qid');

        if ($isAdmin && $this->userType != 'administrator') {
            $this->loadClass('models/group');
            $gmodel = new Group($gkey);
            if (!$qid || !$gmodel->isOccupant($this->userLogin)) {
                header('location: ' . $this->getLinkToView('faq', null, 'index'));
                exit;
            }
        }

        //question
        $this->loadClass('models/faq');
        $model = new Faq($gkey, $this->userLogin, $this->userType);
        $question = $model->getQuestion($qid);

        if (!$question) {//there is no searches question
            header('location: ' . $this->getLinkToView('faq', null, 'index'));
            exit;
        }

        //answer
        $aid = null;
        $answer = $model->getAnswerByQuestionId($qid);
        if ($answer) {
            $aid = $answer['id'];
            $old = false;
            if ($question['question'] != $answer['question']) {
                $question['old_question'] = $answer['question'];
                $old = true;
            }
            if ($question['week'] != $answer['week']) {
                $question['old_week'] = $answer['week'];
                $old = true;
            }
            if ($question['gkey'] != $answer['gkey']) {
                $question['old_gkey'] = $answer['gkey'];
                $old = true;
            }
            if ($old) {
                $question['modified'] = $answer['modified'];
            }
        }

        $this->assign('QUESTION', $question);


        //form
        $this->loadClass('forms/answer.php');
        $form = new AnswerForm($isAdmin);

        //submit
        if ($form->isSubmitted()) {
            if ($form->validate()) {
                $saveData = $form->getSubmitValues();

                $saveData['question_id'] = $question['id'];
                if ($question['old_question'] && $saveData['use_old_question'] == 'true') {
                    $saveData['question'] = $question['old_question'];
                    if ($question['old_week']) {
                        $saveData['week'] = $question['old_week'];
                    } else {
                        $saveData['week'] = $question['week'];
                    }
                } else {
                    $saveData['question'] = $question['question'];
                    $saveData['week'] = $question['week'];
                }

                if (!$isAdmin) {
                    $saveData['version'] = 'draft';
                }

                $result = $model->saveAnswer($saveData, $aid);

                if ($result) {
                    $this->assign('FORM_SUCCESS', true);
                } else {
                    $this->assign('FORM_ERROR', true);
                }
                header('refresh: 1; url= ' . $this->getLinkToView('faq', null, 'index'));
            } else {
                //var_dump($form->_errors);
            }
        } else {

            if ($answer) {
                $form->setDefaults($answer);
                $this->assign('DATA', $answer);
            }
        }

        $renderer = $form->setDefaultRenderer($this->smarty);
        $this->assign('FORM', $renderer->toArray());
    }

    public function answer_administrator() {
        $this->assign('TPL', $this->baseDir . 'views/faq/answer_professor.tpl');
        $this->answer_professor(true);
    }

}


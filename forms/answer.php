<?php

require_once G_ROOTPATH . 'www/modules/module_whiteboard/forms/abstract.php';

/**
 * @author Michał Korotkiewicz
 */
class Whiteboard_AnswerForm extends Whiteboard_AbstractForm {

    public function __construct($isAdmin = false) {
        parent::__construct();
        $this->addElement('hidden', 'use_old_question', null, array('id' => 'use_old_question'));
        $this->addElement('textarea', 'answer', _CONTENT, 'class = "inputContentTextarea mceEditor" style = "width:100%;height:300px;"');
        
        if($isAdmin) {
            $this->addElement('select', 'version', 'Wersja', array('draft' => 'Robocza', 'public' => 'Publiczna'));
        }

        $this->addElement('submit', 'submitBtn', _SUBMIT, 'class = "flatButton"');
        
        $this->setDefaults(array('use_old_question' => 'true'));
    }

}

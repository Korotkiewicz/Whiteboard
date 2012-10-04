<?php
namespace Whiteboard;

require_once G_ROOTPATH . 'www/modules/module_whiteboard/forms/abstract.php';

/**
 * @author Michał Korotkiewicz
 */
class QuestionForm extends AbstractForm {
    public function  __construct($weeks) {
        if(!$weeks || !is_array($weeks)) {
            $weeks = array();
        }
        
        parent::__construct();
        
        $this->addElement('select', 'week', 'Tydzień nauki', $weeks);
        $this->addRule('week', 'Pole wymagane', 'required');
        
        $this->addElement('textarea', 'question', 'Treść pytania', array('cols' => 55, 'rows' => 5));
        $this->addRule('question', 'Pole wymagane', 'required');

        $this->addElement('submit', 'submit', 'Zapisz', 'class = "flatButton"');
    }
}

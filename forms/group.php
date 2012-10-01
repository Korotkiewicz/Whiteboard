<?php
require_once G_ROOTPATH . 'www/modules/module_whiteboard/forms/abstract.php';

/**
 * @author Michał Korotkiewicz
 */
class Whiteboard_GroupForm extends Whiteboard_AbstractForm {
    public function  __construct($courses) {
        if(!$courses || !is_array($courses)) {
            $courses = array();
        }
        
        parent::__construct();
//        $this->addElement('text', 'name', 'Nazwa grupy', array('size' => 255, 'maxlength' => 255, 'style' => 'width: 350px;'));
//        $this->addRule('name', 'Pole wymagane', 'required');
        
        $this->addElement('text', 'gkey', 'Kod grupy', array('size' => 50, 'maxlength' => 50));
        $this->addRule('gkey', 'Pole wymagane', 'required');
        
        $this->addElement('select', 'course_id', 'Przedmiot', $courses);
        $this->addRule('course_id', 'Pole wymagane', 'required');
        
        $this->addElement('text', 'time', 'Godzina', array('id' => 'time', 'size' => 5, 'maxlength' => 5, 'style' => 'width: 40px;', 'onchange' => 'setNextGroupDate()'));
        $this->addRule('time', 'Zły format (poprawnie GG:MM)', 'regex','/^[012]?\d{1}[:][012345]\d$/');
        $this->addRule('time', 'Pole wymagane', 'required');
        
        $this->addElement('select', 'day_of_week', 'Dzień tygodnia', array('1' => 'Poniedziałek', '2' => 'Wtorek', '3' => 'Środa', '4' => 'Czwartek', '5' => 'Piątek', '6' => 'Sobota', '7' => 'Niedziela'), array('onchange' => 'setNextGroupDate()', 'id' => 'day_of_week'));
        $this->addRule('day_of_week', 'Pole wymagane', 'required');
        
        $this->addElement('select', 'frequency', 'Częstotliwość (raz na ile tygodni)', array('1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8'), array('onchange' => 'setNextGroupDate()', 'id' => 'frequency'));
        $this->addRule('frequency', 'Pole wymagane', 'required');
        
        $this->addElement('select', 'shift_week', 'Opóźnienie względem pierwszego tygodnia', array('0' => '0 tygodni', '1' => '1 tydzień', '2' => '2 tygodnie', '3' => '3 tygodnie', '4' => '4 tygodnie', '5' => '5 tygodni', '6' => '6 tygodni', '7' => '7 tygodni'), array('onchange' => 'setNextGroupDate()', 'id' => 'shift_week'));
        $this->addRule('shift_week', 'Pole wymagane', 'required');

        $this->addElement('submit', 'submit', 'Zapisz', 'class = "flatButton"');
       
        $this->setDefaults(array('day_of_week' => 6));
    }
    
    public function setDefaults($defaultValues = null, $filter = null) {
        if($defaultValues['time']) {
            $defaultValues['time'] = substr($defaultValues['time'], 0, 5);
        }
        parent::setDefaults($defaultValues, $filter);
    }
}

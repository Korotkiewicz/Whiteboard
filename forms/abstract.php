<?php

/**
 * @author Michał Korotkiewicz
 */
abstract class Whiteboard_AbstractForm extends HTML_QuickForm {

    private $_myDefaultRenderer;

    public function __construct($formName = null, $method = null ,$action = null, $target='') {
        if (is_null($formName)) {
            $formName = get_class($this);
        }
        if(is_null($method)) {
            $method = 'post';
        }
        if(is_null($action)) {
            $action = $_SERVER['REQUEST_URI'];
        }
        parent::__construct($formName, $method, $action, $target, null, true);
    }

    /**
     *
     * @param string/array $elementsName or $elementName
     */
    public function removeRulers($elementName) {
        if (is_array($elementName)) {
            foreach ($elementName as $element) {
                $this->_removeRules($element);
            }
        } else {
            $this->_removeRules($elementName);
        }
    }

    protected function _removeRules($elementName) {
        $el = $this->_elements[$this->_elementIndex[$elementName]];
        $this->_required = array_diff($this->_required, array($elementName));
        unset($this->_rules[$elementName], $this->_errors[$elementName]);

        if (is_null($el)) {
            throw new Exception($elementName . ' can not has remove rules');
        }
        if ('group' == $el->getType()) {
            foreach (array_keys($el->getElements()) as $key) {
                unset($this->_rules[$el->getElementName($key)]);
            }
        }
    }

    /**
     * set renderer to form and return it
     *
     * @param <type> $smarty
     * @return HTML_QuickForm_Renderer_ArraySmarty
     */
    public function setDefaultRenderer($smarty) {
        $this->_myDefaultRenderer = new HTML_QuickForm_Renderer_ArraySmarty($smarty);

        $this->_myDefaultRenderer->setRequiredTemplate(
                '{if $required}
            <span style="color:red">*</span>
        {/if}{$label}'
        );

        $this->_myDefaultRenderer->setErrorTemplate(
                '<span class="errorspan">{$html}</span>{if $error}<label class="error" generated="true">{$error}</label>{/if}'
        );
        $this->accept($this->_myDefaultRenderer);
        return $this->_myDefaultRenderer;
    }

    public function getSubmitValues($mergeFiles = false) {
        $submitValues = parent::getSubmitValues($mergeFiles);

        foreach ($this->_elementIndex as $id => $int) {
            if ($int > 0) {
                if (!isset($submitValues[$id]) and ($this->_elements[$int] instanceof HTML_QuickForm_checkbox)) {
                    $submitValues[$id] = 0;
                    unset($this->_submitValues[$id]);
                }
            }
        }

        return $submitValues;
    }

}

?>
<?php

class Admin_Form_Department extends App_Form
{
	 private $elementDecorators = array(
        'ViewHelper',
        array(array('data' => 'HtmlTag'), array('tag' => 'span')),
        'Label',
        array(array('data' => 'HtmlTag'), array('tag' => 'div', 'class' => 'form-group')),
    );
 
    private $buttonDecorators = array(
        'ViewHelper',
        array(array('data' => 'HtmlTag'), array('tag' => 'span')),
        
    );
    


    public function init()
    {
		parent::init();
   	
    	
    	$this->setMethod('post');
    	$this->setAttrib('class', 'form-inline');
 		$this->setAttrib('id', 'form-add-department');
 		
        $this->addElement(
            'text', 'name', array(
		        'decorators' => $this->elementDecorators,
                'label' => 'Department/Program:',
        		'class' => 'form-control',
                'required' => true,
                'filters'    => array('StringTrim', 'HTMLPurifier'),
            ));
 
 
        $this->addElement('button', 'button', array(
        	'decorators' => $this->buttonDecorators,
            'class' => 'btn btn-default',
        	'type' => 'submit',
        	'ignore'   => true,
            'label'    => 'Submit',
            ));
    
   
    
    
    }


	    
    
    
}


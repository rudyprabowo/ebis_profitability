<?php
namespace Core\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;;

/**
 * This form is used to collect user's login, password and 'Remember Me' flag.
 */
class CsrfForm extends Form
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        // Define form name
        parent::__construct('csrf-form');
        $me = $this;

        // Set POST method for this form
        $me->setAttribute('method', 'post');

        $me->addElements();
        $me->addInputFilter();
    }

    /**
     * This method adds elements to form (input fields and submit button).
     */
    protected function addElements()
    {
        $me = $this;
        // Add the CSRF field
        $me->add([
            'type' => 'csrf',
            'name' => 'csrf',
            'attributes'=>[
                'id' => 'csrf',
            ],
            'options' => [
                'csrf_options' => [
                    'timeout' => 600
                ]
            ],
        ]);
    }

    /**
     * This method creates input filter (used for form filtering/validation).
     */
    private function addInputFilter()
    {
        $me = $this;
        // Create main input filter
        $inputFilter = new InputFilter();
        $me->setInputFilter($inputFilter);
        $inputFilter->add([
            'name'     => 'csrf',
            'required' => true
        ]);
    }
}

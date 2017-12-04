<?php

/**
 *
 *
 *
 *
 *
 * 
 */

    class home extends Controller 
    {
        public $wrapper = 'page';
        
        function main() 
        {
        
            
            if ($this->POST) 
            {
                $f = new Form('main');
                $arr = Array();
                
                switch($f->evaluate($arr)) 
                {
                    case FORM_OK:
                        /* write your code here when form is ok **/
                        break;
                }
                
            }
            
            $this->view('HEADER','header',Array('banner'=>'homeban19'));
            
            $this->view(
            
                'BODY',         /* This is View */
                
                'home',    /* This is template pushed to view */
                
                /* This is data being put in template */
                    Array('content' => "welcome to avardz")
                /*Array(
                    'name' => 'Ali',
                    'age'  => 1000,
                    'exc'  => 5.5,
                    'wht'  => 0,
                    'oth'  => Array('nationality' => 'Pakistani' ),
               )*/
            );
            
             $this->view('FOOTER','footer');
        }
    }
    
    
    


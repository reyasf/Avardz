<?php

/**
 *
 *
 */

    class index extends Controller
    {
        public $wrapper = 'page';
        
        function main()
        {

            Application::deal_unauthorized();

            $tpl_head   = 'header';
            $tpl        = 'index';
            $tpl_footer = 'footer';
            $error          = '';
            $CALENDAR = $GLOBALS['System']->SESSION->get('calendar');
            $LANG = System::get_language();

            if (!Application::confirm_user())
            {
                $tpl = "message";
                $error = "you are not authorized";
                System::redirect_to_controller("misc","login");
            }
            else
            {
                $current_user = Application::confirm_user();
                $u = new User("id", $current_user->id);
                $title = $u->gender == 'M' ? $LANG[288] : $LANG[292];
                if($CALENDAR=="gregorian" || $CALENDAR==NULL)
                    $user_name = $title." ".$u->fname." ".$u->lname;
                else
                    $user_name = $title." ".$u->fname_tr." ".$u->lname_tr;
            }
            $this->view('HEADER', $tpl_head,Array('banner'=>'homeban19'));
            $this->view('BODY',   $tpl, Array('error' => $error, 'user'=> $user_name));
            $this->view('FOOTER', $tpl_footer);
        }
    }





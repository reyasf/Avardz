<?php
/* 
 * Store evalauation points history
 *
 */

class str_evaluation extends Controller
    {
        public $wrapper = 'home';
        function main()
        {
            Application::deal_unauthorized();
            Application::check_page_rights("The page you are trying to view is not available for you",  get_class($this));

            $tpl_head   = 'header';
            $tpl        = 'str_evaluation';
            $tpl_footer = 'footer';
            $tpl_ads    = 'ads';
            $error          = '';
            $banner  = Application::createBanner();

            if (!Application::confirm_user())
            {
                $tpl = "message";
                $error = "you are not authorized";
                System::redirect_to_controller("misc","login");
            }
            else
            {
                $current_user = Application::confirm_user();
                $se = new Point("user_id",$current_user->id,"and","type","S");
            }
            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('error' => $error));
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);
        }
    }


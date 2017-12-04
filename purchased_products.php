<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class purchased_products extends Controller
    {
        public $wrapper = 'home';
        function main()
        {
            Application::deal_unauthorized();
            Application::check_page_rights("The page you are trying to view is not available for you",  get_class($this));

            $tpl_head   = 'header';
            $tpl        = 'purchased_products';
            $tpl_footer = 'footer';
            $tpl_ads = 'ads';
            $banner  = Application::createBanner();

            $error          = '';

            if (!Application::confirm_user())
            {
                $tpl = "message";
                $error = "you are not authorized";
                System::redirect_to_controller("misc","login");
            }
            else
            {
                $current_user = Application::confirm_user();
                $su = new Serials_user("user_id",$current_user->id);
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

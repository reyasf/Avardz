<?php
/* 
 * list of products
 * 
 */


class product_list extends Controller
    {
        public $wrapper = 'home';
        function main()
        {
            Application::deal_unauthorized();

            $tpl_head   = 'header';
            $tpl        = 'product_list';
            $tpl_footer = 'footer';
            $tpl_ads = 'ads';
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
                $u = new User("id",$current_user->id);
                $pu = new Products_group("group_id",$current_user->group_id);                
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


<?php
/* 
 * Delete Serial
 *
 */

class delete_serial extends Controller
    {
        public $wrapper = 'home';
        function main()
        {
            Application::deal_unauthorized();

            $tpl_head   = 'header';
            $tpl        = 'delete_serial';
            $tpl_footer = 'footer';
            $tpl_ads    = 'ads';
            $banner     = Application::createBanner();
            $error          = '';

            if($this->POST)
            {
                if (Application::confirm_user())
                {
                    $flag = true;

                    echo $this->POST["serialid"];

                    $data = @base64_decode($this->POST["serialid"]);
                    if (!$data) $flag=false;

                    @list($id, $code) = explode('|', $data);

                    $id=base64_decode($id);

                    if (trim($code) == '') $flag=false;
                    if (intval($id) == 0) $flag=false;

                    if($flag)
                    {
                        echo $id;
                    }
                    else
                        System::redirect_to_controller("delete_serial");
                }
            }

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
                $su = new Serials_user("user_id",$current_user->id);
            }
            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('error' => $error,'user'=>$u));
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);
        }
    }


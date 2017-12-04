<?php
/* 
 * User Inbox
 *
 */

class userinbox extends Controller
{
        public $wrapper = 'home';
        function main()
        {
            Application::deal_unauthorized();

            $tpl_head   = 'header';
            $tpl        = 'userinbox';
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
                $ui = new Inbox("user_id",$current_user->id);

                $unread_count = Application::get_inbox_count($current_user->id);
                $unread_count ? $display_uc = "display:inline;" : $display_uc = "display:none;";

                $unread_private_count = Application::get_inbox_count($current_user->id,'private');
                $unread_private_count ? $display_pc = "display:inline;" : $display_pc = "display:none;";

                $unread_general_count = Application::get_inbox_count($current_user->id,'general');
                $unread_general_count ? $display_gc = "display:inline;" : $display_gc = "display:none;";
            }
            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('error' => $error,'uc'=>$unread_count,'pc'=>$unread_private_count,'gc'=>$unread_general_count,'display_uc'=>$display_uc,'display_pc'=>$display_pc,'display_gc'=>$display_gc));
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);
        }

        function showmessage()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            
            $flag=true;
            $tpl_head   = 'header';
            $tpl        = 'userinbox';
            $tpl_footer = 'footer';
            $tpl_ads    = 'ads';
            $banner  = Application::createBanner();

            $data = @base64_decode($this->GET["msgid"]);
            if (!$data) $flag=false;

            @list($id, $message) = explode('|', $data);
            
            $id=base64_decode($id);
            
            if (trim($message) == '') $flag=false;
            if (intval($id) == 0) $flag=false;

            if($flag)
            {
                $inbox = new Inbox("id",intval($id),"and","user_id",$current_user->id);
                if ($inbox->count)
                {
                    $this->view("BODY","inbox_detail",Array("inbox"=>$inbox));
                    $inbox->status = 1;
                    $inbox->save();
                }
                else
                    $this->view("BODY","message",Array("message"=>"You are not Authorized"));
                $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
                $adverts = new Advertisement("id",">","0");
                foreach($adverts as $ad)
                {
                    $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
                }
                $this->view('FOOTER', $tpl_footer);
            }
            else
                System::redirect_to_controller("userinbox");
        }

        function deletemessages()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $inbox = new Inbox("user_id",$current_user->id);
            if ($inbox->count)
            {
                foreach($inbox as $i)
                {
                    $i->rel_id=589;
                    $i->save();
                }
            }
            System::redirect_to_controller("userinbox");
        }

        function deleteselected()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            
            $data = @$this->GET["data"];

            $messages = explode("|",$data);

            foreach($messages as $message)
            {
                if($message)
                {
                    $decoded = @base64_decode($message);
                    @list($id, $dec_message) = explode('|', $decoded);
                    $id = base64_decode($id);

                    echo $id;

                    $inbox = new Inbox("id",intval($id),"and","user_id",$current_user->id);
                    if ($inbox->count)
                    {
                        $inbox->rel_id=589;
                        $inbox->save();
                    }
                }
            }
            
            System::redirect_to_controller("userinbox");
        }
}

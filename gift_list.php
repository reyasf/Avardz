<?php
/* 
 * Gift List
 */

class gift_list extends Controller
    {
        public $wrapper = 'home';
        function main()
        {
            Application::deal_unauthorized();

            $tpl_head   = 'header';
            $tpl        = 'gift_list';
            $tpl_footer = 'footer';
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
                $u = new User("id",$current_user->id);
                $gu = new Gifts_group("group_id",$current_user->group_id);

                $total_records = $gu->count;

                $limit = 7;
                $total_pages = ceil($total_records / $limit);
                if(isset($GLOBALS["System"]->GET["page"]))
                    $page=$GLOBALS["System"]->GET["page"];
                else
                    $page=0;
            }

            $this->view('HEADER', $tpl_head);
            $this->view('BODY',   $tpl, Array('error' => $error,'page' => $page,'total' => $total_records,'limit' => $limit));
            $this->view('FOOTER', $tpl_footer);
        }
    }


?>

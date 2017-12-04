<?php
class userticket extends Controller
{
        public $wrapper = 'home';
        function main()
        {
            Application::deal_unauthorized();

            $tpl_head   = 'header';
            $tpl        = 'userticket';
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
                $ut = new Ticket("user_id",$current_user->id);
                $total_records = $ut->count;
            }

            if($this->POST)
            {
                if (Application::confirm_user())
                {
                    $f = new Form('userticket');
                    switch ($f->evaluate())
                    {
                        default:
                            $error =  Application::formatText($this->LANG[2139]);
                            Application::display_message($error,ROOT_PATH."?controller=userticket");
                            break;

                        case FORM_OK :




                            /*
                             * captcha verification
                             */

                            if(isset($this->POST['verify_code']))
                            {

                                $result_verification = $GLOBALS['System']->SESSION->get('captcha_code_one') + $GLOBALS['System']->SESSION->get('captcha_code_two');

                                if($result_verification>9)
                                {
                                    $codes = explode("|",$this->POST['verify_code']);
                                    $final_code = "";
                                    foreach($codes as $c)
                                    {
                                        for($i=0;$i<=$GLOBALS['System']->SESSION->get('captcha_code_rand');$i++)
                                        {
                                            $decode_code = base64_decode($c);
                                            $c = $decode_code;
                                        }
                                        $stored_array = $GLOBALS['System']->SESSION->get('captcha_array');
                                        $final_code .= array_search($decode_code, $stored_array);
                                    }
                                }
                                else
                                {
                                    $verified_code = $this->POST['verify_code'];
                                    for($i=0;$i<=$GLOBALS['System']->SESSION->get('captcha_code_rand');$i++)
                                    {
                                        $decode_code = base64_decode($verified_code);
                                        $verified_code = $decode_code;
                                    }

                                    $stored_array = $GLOBALS['System']->SESSION->get('captcha_array');
                                    $final_code = array_search($decode_code, $stored_array);
                                }

                                if(!($result_verification == intval($final_code)))
                                {
                                    $error = Application::formatText($this->LANG[3161]);
                                }
                            }
                            else
                                $error = Application::formatText($this->LANG[3163]);

                            /*
                             * End verification
                             */
                            
                            if ($error == '')
                            {
                                $t = new Ticket();
                                $t->subject = Security::secure_input(Application::secureInput($f->subject));
                                $t->message = Security::secure_input(Application::secureInput($f->message));
                                $t->user_id = (int)$current_user->id;
                                $t->status = 'P';
                                $t->date = time();
                                $t->save();
                                $error =  Application::formatText($this->LANG[2135]);
                                Application::display_message($error,ROOT_PATH."?controller=userticket");
                            }
                    }
                }
            }


            /*
             * captcha processing
             */

            $verify_code_first = rand(0,9);
            $verify_code_second = rand(0,9);
            $captcha_array = Application::create_captcha_array();
            $rand = rand(1,6);
            $captcha = Utility::create_captcha($captcha_array,$rand);

            $GLOBALS['System']->SESSION->set('captcha_code_one',$verify_code_first);
            $GLOBALS['System']->SESSION->set('captcha_code_two',$verify_code_second);
            $GLOBALS['System']->SESSION->set('captcha_code_rand',$rand);
            $GLOBALS['System']->SESSION->set('captcha_array',$captcha_array);

            /*
             * end of captcha process
             */

            if($error=='')
                $display = ":none";
            else
                $display = ":block";

            $first  = substr(md5(base64_encode($verify_code_first)),0,3);
            $second = substr(md5(base64_encode($verify_code_second)),0,3);

      
            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('error' => $error, 'display' => $display,"first" => $first, 'second' => $second,'captcha' => $captcha));
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);
        }

        function showticket()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();

            $flag=true;
            $tpl_head   = 'header';
            $tpl        = 'ticket_detail';
            $tpl_footer = 'footer';
            $banner  = Application::createBanner();
            $tpl_ads = 'ads';

            if($this->POST)
            {
                $f = new Form('userticket');
                $data = @base64_decode($f->ticketid);
                @list($ticketid, $message) = explode('|', $data);
                $ticketid=base64_decode($ticketid);

                switch ($f->evaluate())
                {
                    default:
                        $error = "Message is not valid length should be from 10 to 200";
                        Application::display_message($error,"http://localhost/projects/avardz/public_html?controller=userticket");
                        break;

                    case FORM_OK :
                        $t = new Ticket("id",$ticketid);
                        if($t->status=='O')
                        {
                            $t = new Ticket();
                            $t->message = Security::secure_input(Application::secureInput($f->message));
                            $t->user_id = (int)$current_user->id;
                            $t->status = 'O';
                            $t->followup_id = $ticketid;
                            $t->date = time();
                            $t->save();

                            $ut = new Ticket('id',$ticketid);
                            $ut->open_to = 'A';
                            $ut -> save();
                        }
                }
            }

            if(isset($this->GET["ticketid"]))
            {
                $data = @base64_decode($this->GET["ticketid"]);
                if (!$data) $flag=false;

                @list($id, $message) = explode('|', $data);

                $id=base64_decode($id);

                if (trim($message) == '') $flag=false;
                if (intval($id) == 0) $flag=false;

                if($flag)
                {
                    $ticket = new Ticket("id",intval($id),"and","user_id",$current_user->id);
                    if ($ticket->count)
                        $this->view("BODY",$tpl,Array("ticket"=>$ticket,"ticketid"=>$this->GET["ticketid"]));
                    else
                    {
                        $tpl="message";
                        $this->view("BODY",$tpl,Array("message"=>"You are not Authorized"));
                    }
                    $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
                    $adverts = new Advertisement("id",">","0");
                    foreach($adverts as $ad)
                    {
                        $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
                    }
                    $this->view('FOOTER', $tpl_footer);
                }
                else
                    System::redirect_to_controller("userticket");
            }

        }

}

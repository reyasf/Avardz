<?php

    /**
        Validation     
        Login
        Logout
        Reset password
        etc
    **/


    class misc extends Controller 
    {
        public $wrapper = 'page';
        
        public function main()
        {
            System::redirect(ROOT_PATH, TRUE);
        }
        
        public function login()
        {
            $tpl_head   = 'header';
            $tpl        = 'login';
            $tpl_footer = 'footer';
            $error      = $this->LANG[2725];
            $message    = "";
            $banner  = Application::createBanner();

            

            if($this->authorized('user'))
                System::redirect(ROOT_PATH, TRUE);

            $this->CACHE->flush();

            if ($this->POST) 
            {            
                $f = new Form('login');
                
                switch ($f->evaluate())
                {
                    default:
                        //We have errors
                        $field = array_keys($f->errors);
                        $field = $field[0];
                        $error_val = $f->errors[$field];
                        switch ($error_val)
                        {
                            case FORM_FIELD_INVALID_EMAIL:
                                $error = $this->LANG[487];
                                break;
                        }
                        
                        break;
                
                    case FORM_OK :
                        $u = new User('email', $f->email);
                        $lt_locked = false; //email for retries has been sent
                        
                        if($u->count) 
                        {
                            
                            if ($u->status == Application::STATUS_AC)
                            {
                                $tries = intval($this->COOKIE->get('lt'));
                                
                                if($tries == Application::get_max_login_tries()-1)
                                {
                                    //Lock the user account for timebeing
                                    $u->status = Application::STATUS_LC;
                                    $u->save();

                                    $this->COOKIE->remove('lt');
                                    
                                    Application::create_event(
                                            $u,
                                            Application::EVENT_TYPE_USER,
                                            Application::EVENT_LOGIN,
                                            "acc-locked",
                                            Application::STATUS_LC
                                    );

                                    $lt_locked = true;
                                    $error = $this->LANG[560];
                                }
                                else
                                {
                                    $tries++;
                                    $this->COOKIE->set('lt', $tries);
                                }

                                if ($u->pass != Encryption::encrypt_password($f->pass))
                                    $error = $this->LANG[245]; //Invalid password supplied!
                                else
                                {
                                    $this->COOKIE->remove('lt');
                                    Application::authenticate_user($u);

                                    Application::create_event(
                                        $u,
                                        Application::EVENT_TYPE_USER,
                                        Application::EVENT_LOGIN
                                    );

                                    System::redirect(ROOT_PATH."/index.php?controller=index", true);
                                }
                            }
                            else
                            {
                                switch ($u->status)
                                {
                                    case Application::STATUS_MS:
                                        $tpl = "message";
                                        $message = Application::get_message_parts($this->LANG[1481],$u); //Account needs validation
                                        break;

                                    case Application::STATUS_VA:
                                        $tpl = "message";
                                        $message = Application::get_message_parts($this->LANG[1483],$u); //Admin did not verify your account through phone, yet.
                                        break;

                                    case Application::STATUS_PV:
                                        $tpl = "message";
                                        $message = Application::get_message_parts($this->LANG[1485],$u); //Your account is verified but still under review.
                                        break;

                                    case Application::STATUS_VF:
                                        $tpl = "message";
                                        $message = Application::get_message_parts($this->LANG[1487],$u); //Phone verification was failed, try contacting adminstrative staff.
                                        break;


                                    case Application::STATUS_LC:
                                        $tpl = "message";
                                        $message = Application::get_message_parts($this->LANG[560],$u); //many tries on password, account locked
                                        $lt_locked=true;
                                        break;

                                    case Application::STATUS_SP:
                                        $tpl = "message";
                                        $message = Application::get_message_parts($this->LANG[559],$u); //This account has permenently been blocked.
                                        break;

                                    case Application::STATUS_DS:
                                        $tpl = "message";
                                        $message = Application::get_message_parts($this->LANG[1248],$u); //permenently blocked
                                        break;
                                }
                            }

                            if($lt_locked)
                            {
                                $e = new Event(
                                    'rel_id', $u->id,
                                    'and',
                                    'date', '>', time()-(3600*24), /* send sngle email within 24 hour */
                                    'and',
                                    'about', Application::EVENT_TYPE_USER,
                                    'and',
                                    'type',  Application::EVENT_LOGIN,
                                    'and',
                                    'extra', Application::STATUS_LC
                                );

                                if($e->count)
                                {
                                    $url = ROOT_PATH."/index.php?controller=misc&method=retreive_password";
                                    $mail  = Application::get_mail_parts($this->LANG[1549], $u, $url);
                                    $mail['body'] = Application::formatText3($mail['body']);
                                    Email::push(
                                        Array(
                                            "to"        =>  $u->email,
                                            "subject"   =>  $mail['subject'],
                                            "body"      =>  $mail['body']
                                        )
                                    );
                                    $e->deleted = 1;
                                    $e->save();
                                }
                            }
                        } 
                        else 
                            $error = $this->LANG[1633];
                            
                        break;
                }
            } 

            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY', $tpl, Array('error' => $error,'message' => $message));
            $this->view('FOOTER', $tpl_footer);
        }

        public function logout()
        {
            Application::logout_user();
            System::redirect(ROOT_PATH, true);
        }
        
        public function validate()
        {
            $message = "You followed an expired url!";

            $u=Application::confirm_vcode($this->GET['vc']);

            if ($u)
            {
                if ($u->status == Application::STATUS_MS)
                {
                    $u->status = Application::STATUS_VA;
                    $u->save();

                    $message = Application::formatText($this->LANG[1479]);
                    $message = str_replace
                    (
                        Array('{region_phone1}', '{region_ext}', '{region_email}'),
                        Array(
                            $u->company->country->phone1,
                            $u->company->country->phone1_ext,
                            $u->company->country->admin_email,
                        ),
                        $message
                    );

                    Application::display_message( $message ); //validation successful
                } 
                else
                    Application::display_message($this->LANG[1569]); //followed expried link


                /*
                 * Create an Event after Activation
                 */
            } else
                    Application::display_message($this->LANG[487]); //Acount not found

            Application::display_message($message, ROOT_PATH);
        }

        public function message() 
        {
            $tpl_head   = 'header';
            $tpl        = 'message';
            $tpl_footer = 'footer';
            $error          = '';
            $banner  = Application::createBanner();

            $cached_message = $this->SESSION->get('cached_message');
            $banner  = Application::createBanner();
            $redirect_to    = $this->SESSION->get('redirect_to');

            if (!$cached_message)
            {
               $cached_message= "You are now being redirected to home page..";
               $redirect_to = ROOT_PATH;
            }
           
            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('error' => $error,'message' => $cached_message));
            $this->view('FOOTER', $tpl_footer);
            
            if ($redirect_to)
                System::redirect($redirect_to);
        }

        public function receipt()
        {
            $cached_message = $this->SESSION->get('cached_message');
            $cached_table = $this->SESSION->get('cached_table');
            $cached_username = $this->SESSION->get('cached_username');
            $cached_ref_no = $this->SESSION->get('cached_ref_no');
            $redirect_to =0;
            $banner  = Application::createBanner();

            if (!$cached_message)
            {
               $cached_message= "You are now being redirected to home page..";
               $redirect_to = ROOT_PATH;
            }

            $tpl_head   = 'header';
            $tpl        = 'redemption_receipt';
            $tpl_footer = 'footer';

             $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('message' => $cached_message, 'table' => $cached_table, 'username' => $cached_username, 'refno' => $cached_ref_no));
            $this->view('FOOTER', $tpl_footer);

            $this->SESSION->remove('cached_message');
            $this->SESSION->remove('cached_table');
            $this->SESSION->remove('cached_username');
            $this->SESSION->remove('cached_ref_no');

            if ($redirect_to)
                System::redirect($redirect_to);
        }

        public function change_password()
        {
            Application::deal_unauthorized();

            $tpl_head   = 'header';
            $tpl        = 'change_password';
            $tpl_footer = 'footer';
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
                if ($this->POST)
                {
                    $f = new Form('change_password');
                    switch ($f->evaluate())
                    {
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

                            if($error == '')
                            {
                                $oldpassword = Encryption::encrypt_password($f->oldpassword);
                                $user = new User("id",$current_user->id,"and","pass",$oldpassword);
                                if($user->count)
                                {
                                    if($f->newpassword == $f->confpassword)
                                    {
                                        $tpl = 'message';
                                        $newpass = Encryption::encrypt_password($f->newpassword);
                                        $user->pass = $newpass;
                                        $user->save();
                                        Application::display_message(Application::formatText($this->LANG[392]));
                                    }
                                    else
                                        $error = Application::formatText($this->LANG[245]);
                                }
                                else
                                {
                                    $error = Application::formatText($this->LANG[572]);
                                }
                            }
                            break;
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

            $first  = substr(md5(base64_encode($verify_code_first)),0,3);
            $second = substr(md5(base64_encode($verify_code_second)),0,3);

            /*
             * end of captcha process
             */



            if($error !='')
                $display=":block;";
            else
                $display=":none;";

            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('error' => $error,'display' => $display,"first" => $first, 'second' => $second,'captcha' => $captcha));
            $this->view('FOOTER', $tpl_footer);
        }

        public function aboutus()
        {
            $tpl_head   = 'header';
            $tpl        = 'aboutus';
            $tpl_footer = 'footer';
            $error          = '';
            $banner  = 'homeban01';

            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl);
            $this->view('FOOTER', $tpl_footer);
        }

        public function retreive_password()
        {
            $tpl_head   = 'header';
            $tpl        = 'retreive_password';
            $tpl_footer = 'footer';
            $error          = '';
            $banner  = Application::createBanner();

            if ($this->POST)
            {
                $f = new Form('retreive_password');
                switch ($f->evaluate())
                {
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

                        if($error =='')
                        {
                            $email = Security::secure_input($f->email);
                            $user = new User("email",$email);
                            if($user->count)
                            {
                                $tpl = "message";
                                $new_pass = Encryption::random_str();
                                $user->pass = Encryption::encrypt_password($new_pass);
                                $user->save();

                                $mail  = Application::get_mail_parts($this->LANG[3175], $user,'',$new_pass);

                                Email::push(
                                        Array(
                                            "to"        =>  $email,
                                            "subject"   =>  $mail['subject'],
                                            "body"      =>  $mail['body']
                                        )
                                    );
                                Application::display_message(Application::formatText($this->LANG[51]));
                            }
                            else
                            {
                                $error = Application::formatText($this->LANG[1633]);
                            }
                        }
                    break;
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

            $first  = substr(md5(base64_encode($verify_code_first)),0,3);
            $second = substr(md5(base64_encode($verify_code_second)),0,3);

            /*
             * end of captcha process
             */

            if($error != '')
                $display = ":block;";
            else
                $display = ":none;";

            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('error' => $error,'display' => $display,"first" => $first, 'second' => $second,'captcha' => $captcha));
            $this->view('FOOTER', $tpl_footer);
        }

        public function join_avardz()
        {
            //$tpl_head   = 'header';
            $tpl        = 'joinavardz';
            //$tpl_footer = 'footer';
            //$error          = '';
            //$banner  = 'homeban01';

            //$this->view('HEADER', '');
            $this->view('BODY',   $tpl);
            //$this->view('FOOTER', '');
        }

        public function terms_conditions()
        {
            $tpl_head   = 'header';
            $tpl        = 'termsandcond';
            $tpl_footer = 'footer';
            $error          = '';
            $banner  = 'homeban01';

            $this->view('BODY',   $tpl);
        }

   }

    
   

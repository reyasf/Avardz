<?php

    /**
     * 
     * Controller for sales registrants, either normal or Mobile sales person
     * once info is corrent, it is going ot redirect user to a page where he provides 
     * his details, and kept unapproved.
     * 
     */
    
    class registration_sales extends Controller 
    {
        public $wrapper = 'page';
        
        
        function check() {
            $prev = $this->SESSION->get('registration1');
            
            if($prev == NULL)
                System::redirect(ROOT_PATH.'/?controller=registration');

            
            return $prev;
        }

        function checkData() {
            $prev = $this->SESSION->get('registration_sales1');
            if($prev == NULL)
                System::redirect(ROOT_PATH.'/?controller=registration',true);

            return $prev;
        }
            
        function main() 
        {
            $this->check();        
        
            $error = "";
            $form = 0;
                        
            $tpl_head   = 'header';
            $tpl = 'registration_sales1';
            $tpl_footer = 'footer';

            /* To pass to the registration form */
            $first_form = @$this->SESSION->get('registration1');
            $reg_type_form =  @intval($first_form['group_id']);
           
            if ($this->POST) 
            {            
                $f = new Form('registration_sales1');
                
                switch ($f->evaluate())
                {
                    default:
                        //print_r($f->errors);
                        break;
                
                    case FORM_OK :

                       $first = $this->SESSION->get('registration1');
                       $reg_type =  intval($first['group_id']);
                       
                       switch($reg_type)
                       {
                            case 1:
                            
                            $invitaion = new Invitation('email_invited', $this->POST['email']);
                            if($invitaion->count) 
                            {
                                $u = new User('id', $invitaion->invitor_id);
                            
                                if ((int)$u->parent_id > 0)
                                $error = $this->LANG[2723];
                                else 
                                {   
                                    $this->SESSION->set('registration_sales1', $this->POST);   
                                    //$first = $this->SESSION->get('registration1');
                                    $tpl = 'message';
                                    System::redirect_to_controller( $this->name, 'sales' );
                                }
                            }
                            else 
                            {
                                /**  
                                    Owner not found in database, send invitation 
                                    DO IT LATER AFTER BASIC FUNCTIONALITY IS DONE 
                                **/
                            
                               System::redirect_to_controller( $this->name, 'invite_owner' );
                                                        
                                /*Email::push(
                                    $this->POST['email'],
                                    'Invitation to join Avardz',
                                    'Invitation to join AvardzInvitation to join AvardzInvitation to join Avardz'
                                );*/
                            
                            }
                            break;
                        
                            case 2:
                                $this->SESSION->set('registration_sales1', $this->POST);   
                                System::redirect_to_controller( $this->name, 'mobile_sales' );
                            break;
                       }
                }
            }

            $lang = $GLOBALS['System']->COOKIE->get('lang');
            if($lang == NULL)
                $lang = "en";
            else
                $lang = ($lang == "en") ? "en" : "pr";

            $this->view('HEADER', $tpl_head,Array('banner'=>'homeban03'));
            $this->view('BODY', $tpl, Array('error' => $error,'reg_type'=>$reg_type_form,"lang" => $lang));
            $this->view('FOOTER', $tpl_footer);
            
        }
        
        function mobile_sales() 
        {
             //$first = $this->check();

             $first = $this->SESSION->get('registration1');
             $sales_data = $this->SESSION->get('registration_sales1');   
             $invitation = new Invitation('email_invited', $sales_data['email']);
             $referer = new User('id', $invitation->invitor_id);
                         
             $error = "";
             
             $tpl_head   = 'header';
             $tpl   = 'registration_sales_mobile';
             $tpl_footer = 'footer';
             
            if ($this->POST) 
            {            
                $f = new Form('registration_sales_mobile');

                $e = $f->evaluate();

                switch ($e)
                {
                    default:
                        //print_r($f->errors);
                        break;
                
                    case FORM_OK :
                        $t = new User('email', $f->email);


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

                        if($t->count) {
                            /** Already a registered user **/
                            $error = Application::formatText($this->LANG[2723]);
                            
                        } else {
                            /** create new account **/
                            if($error == '')
                            {
                                $new_pass = Encryption::random_str();

                                $u = new User;
                                $reg_date = time();

                                $u->email                   = $f->email;
                                $u->fname                   = $f->fname;
                                $u->lname                   = $f->lname;
                                $u->gender                  = $f->gender;
                                $u->zipcode                 = $f->zipcode;
                                $u->pass                    = '';

                                $calendar = $GLOBALS['System']->SESSION->get('calendar');
                                switch($calendar)
                                {
                                    case ENGLISH_CALENDAR:
                                        $u->date_birth              = mktime(0,0,0,  $f->dob_month, $f->dob_day, $f->dob_year );
                                        break;

                                    case PERSIAN_CALENDAR:
                                        $calendar = new Calendar();
                                        list($en_year,$en_month,$en_date) = $calendar->jalali_to_gregorian($f->dob_year, $f->dob_month, $f->dob_day);
                                        $u->date_birth              = mktime(0,0,0,  $en_month, $en_date, $en_year );
                                        break;

                                    default:
                                        $u->date_birth              = mktime(0,0,0,  $f->dob_month, $f->dob_day, $f->dob_year );

                                }

                                $u->private_city_id         = $f->private_city_id;
                                $u->private_state_id        = $f->private_state_id;
                                $u->national_id             = $f->national_id;

                                $company                    = new Company("country_id",$f->country_id);

                                $u->company_id              = $company->id;
                                $u->private_address         = $f->private_address;
                                $u->mobile_number           = $f->mobile_number;
                                $u->private_phone           = $f->private_phone_code.$f->private_phone;
                                $u->private_fax             = $f->private_fax;
                                $u->private_zipcode         = $f->private_zipcode;
                                $u->pass                    = Encryption::encrypt_password($new_pass);
                                $u->group_id                = 4;
                                $u->date_reg                = $reg_date;
                                $u->status                  = 0;

                                $u->save();

                                $ur = new User("email",$f->email);
                                $invitation = new Invitation("email_invited",$ur->email);

                                if(!($invitation->count))
                                {
                                    $referer                    = new Referer();
                                    $referer->fname             = $f->ref_fname;
                                    $referer->lname             = $f->ref_lname;
                                    $referer->email             = $f->ref_email;
                                    $referer->mobile_number     = $f->ref_mobile;
                                    $referer->state_id          = $f->referer_state_id;
                                    $referer->city_id           = $f->referer_city_id;
                                    $referer->save();

                                    $up_ref = new Referer("email",$f->ref_email);
                                    $up_ref->rel_id = $ur->id;
                                    $up_ref->save();
                                }

                                $tpl = 'message';

                                $vcode = Application::make_vcode($f->email, $reg_date);

                                $url = ROOT_PATH."/index.php?controller=misc&method=validate&vc=".$vcode;
                                $mail  = Application::get_mail_parts($this->LANG[1513], $ur, $url);
                                $mail['body'] = Application::formatText3($mail['body']);

                                    Email::push(
                                        Array(
                                            "to"        =>  $f->email,
                                            "subject"   =>  $mail['subject'],
                                            "body"      =>  $mail['body']
                                        )
                                    );
                                    $message = Application::formatText($this->LANG[1477]);
                                    Application::display_message($message);
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

            /*
             * end of captcha process
             */
            
             if($error=='')
                $display = ":none";
            else
                $display = ":block";

            $lang = $GLOBALS['System']->COOKIE->get('lang');
            if($lang == NULL)
                $lang = "en";
            else
                $lang = ($lang == "en") ? "en" : "pr";

            $first_code  = substr(md5(base64_encode($verify_code_first)),0,3);
            $second_code = substr(md5(base64_encode($verify_code_second)),0,3);

            $this->view('HEADER', $tpl_head,Array('banner'=>'homeban03'));
            $this->view('BODY', $tpl,  Array( 'error' => $error, 'data' => $first, 'referer' => $referer, 'display' => $display, 'first'=>$sales_data,"lang" => $lang, 'first_code' =>  $first_code,'second_code' => $second_code,'captcha' => $captcha ) );
            $this->view('FOOTER', $tpl_footer);
            
        }
        
        function sales() 
        {
            $this->checkData();
            $error = "";
            $first = $this->SESSION->get('registration1');
            $tpl_head   = 'header';
            $tpl = 'registration_sales';
            $tpl_footer = 'footer';
            
            $sales_data     = $this->SESSION->get('registration_sales1');
            $invitation     = new Invitation('email_invited', $sales_data['email']);
            $owner          = new User('id', $invitation->invitor_id);
            $company        = new Company('id', $owner->company_id);
            $company->num_employees = 12;
            $company->save();

            if($owner->group_id==1)
            {
                $owner->group_id=2;
                $owner->save();
            }

            if($owner->group_id==5)
                $error="Owner belongs to avajang";
            else
            if ($this->POST) 
            {            
                
                $f = new Form('registration_sales');

                switch ($f->evaluate())
                {
                    case FORM_OK :
                    
                        $t = new User('email', $this->POST['email']);

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

                        if($t->count)
                        {
                            /** Already a registered user **/
                            $error = Application::formatText($this->LANG[2723]);
                            
                        } 
                        else
                        {
                            /** create new account **/

                            if($error == '')
                            {
                            
                                $new_pass = Encryption::random_str();

                                $u              = new User;
                                $u->email       = $f->email;
                                $u->fname       = $f->fname;
                                $u->lname       = $f->lname;
                                $u->pass        = '';
                                $u->parent_id   = $owner->id;

                                if($owner->group_id == 2)
                                    $u->group_id  = 3;
                                else
                                    $u->group_id  = 7;

                                $reg_date = time();

                                $u->date_reg                = $reg_date;
                                $u->company_id              = $owner->company_id;
                                $u->private_zipcode         = $f->private_zipcode;

                                $calendar = $GLOBALS['System']->SESSION->get('calendar');
                                switch($calendar)
                                {
                                    case ENGLISH_CALENDAR:
                                        $u->date_birth              = mktime(0,0,0,  $f->dob_month, $f->dob_day, $f->dob_year );
                                        break;

                                    case PERSIAN_CALENDAR:
                                        $calendar = new Calendar();
                                        list($en_year,$en_month,$en_date) = $calendar->jalali_to_gregorian($f->dob_year, $f->dob_month, $f->dob_day);
                                        $u->date_birth              = mktime(0,0,0,  $en_month, $en_date, $en_year );
                                        break;

                                    default:
                                        $u->date_birth              = mktime(0,0,0,  $f->dob_month, $f->dob_day, $f->dob_year );

                                }

                                $u->gender                  = $f->gender;
                                $u->private_city_id         = $f->private_city_id;
                                $u->private_state_id        = $f->private_state_id;
                                $u->national_id             = $f->national_id;
                                $u->private_address         = $f->private_address;
                                $u->mobile_number           = $f->mobile_number;
                                $u->private_phone           = $f->private_phone_code.$f->private_phone;
                                $u->private_fax             = $f->private_fax;
                                $u->pass                    = Encryption::encrypt_password($new_pass);
                                $u->status                  = 0;

                                $u->save();

                                $tpl = 'message';

                                $vcode = Application::make_vcode($f->email, $reg_date);

                                $url = ROOT_PATH."/index.php?controller=misc&method=validate&vc=".$vcode;
                                $user = new User("email",$f->email);
                                $mail  = Application::get_mail_parts($this->LANG[1513], $user, $url);
                                $mail['body'] = Application::formatText3($mail['body']);

                                    Email::push(
                                        Array(
                                            "to"        =>  $f->email,
                                            "subject"   =>  $mail['subject'],
                                            "body"      =>  $mail['body']
                                        )
                                    );
                                    $message = Application::formatText($this->LANG[1477]);
                                    Application::display_message($message);
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

            $lang = $GLOBALS['System']->COOKIE->get('lang');
            if($lang == NULL)
                $lang = "en";
            else
                $lang = ($lang == "en") ? "en" : "pr";

            $first_code  = substr(md5(base64_encode($verify_code_first)),0,3);
            $second_code = substr(md5(base64_encode($verify_code_second)),0,3);

            $this->view('HEADER', $tpl_head,Array('banner'=>'homeban03'));
            $this->view('BODY', $tpl,  Array( 'error' => $error,  'owner' => $owner, 'company' => $company, 'display' => $display, 'first'=>$sales_data,"lang" => $lang, 'first_code' =>  $first_code,'second_code' => $second_code ,'captcha' => $captcha) );
            $this->view('FOOTER', $tpl_footer);
            
        }
        
        function invite_owner()
        {
            $tpl_head   = 'header';
            $tpl = 'invite_owner';
            $tpl_footer = 'footer';
            $error = '';
            $first_form = @$this->SESSION->get('registration1');
            
            if($this->POST)
            {
                $f = new Form('invite_owner');
                switch ($f->evaluate())
                {
                    case FORM_OK :
                        $owner = new User("email",$f->email);
                        if($owner->count)
                        {
                                $mail  = Application::get_mail_parts($this->LANG[2615], $owner);

                                Email::push(
                                    Array(
                                        "to"        =>  $f->email,
                                        "subject"   =>  $mail['subject'],
                                        "body"      =>  $mail['body']
                                    )
                                );
                                $message = Application::formatText($this->LANG[53]);
                                Application::display_message($message);
                        }
                        else
                        {
                                $country = new Country("id",2);
                                $url = ROOT_PATH."?controller=registration";
                                $email_message = str_replace(Array("{last_name}","{url}","{region_phone1}","{region_ext}","{region_email}","{country}"),
                                                       Array($f->lname,$url,$country->phone1,$country->phone1_ext,$country->admin_email,$country->name), $this->LANG[1509]);

                                $mail  = Application::get_mail_parts($email_message);
                                $mail['body'] = Application::formatText3($mail['body']);

                                Email::push(
                                    Array(
                                        "to"        =>  $f->email,
                                        "subject"   =>  $mail['subject'],
                                        "body"      =>  $mail['body']
                                    )
                                );
                                $message = Application::formatText($this->LANG[53]);
                                Application::display_message($message);
                        }
                    break;
                }
      
            }
            
            $this->view('HEADER', $tpl_head,Array('banner'=>'homeban03'));
            $this->view('BODY', $tpl,Array('error' => $error));
            $this->view('FOOTER', $tpl_footer);                
        }
        
    }
    


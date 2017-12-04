<?php

    /**
     *
     * Controller for sales registrants, either normal or Mobile sales person
     * once info is corrent, it is going ot redirect user to a page where he provides
     * his details, and kept unapproved.
     *
     */

    class registration_sales_uae extends Controller
    {
        public $wrapper = 'page';


        function main()
        {
            $error = "";
            $form = 0;

            $tpl_head   = 'header';
            $tpl = 'registration_sales_uae';
            $tpl_footer = 'footer';

            $group    = Array( 1 => 8, 3 => 7 );

            $first = $this->SESSION->get('registration1');
            $reg_type =  intval($group[ $first['group_id'] ]);
            

            switch($reg_type)
            {
                case 8:
                    $tpl = 'message';
                    System::redirect_to_controller( $this->name, 'sales' );
                    break;
                default:
                    System::redirect_to_controller( 'registration' );
            }

            $lang = $GLOBALS['System']->COOKIE->get('lang');
            if($lang == NULL)
                $lang = "en";
            else
                $lang = ($lang == "en") ? "en" : "pr";

            $this->view('HEADER', $tpl_head,Array('banner'=>'homeban03'));
            $this->view('BODY', $tpl, Array('error' => $error,'reg_type'=>$reg_type_form));
            $this->view('FOOTER', $tpl_footer);

        }

        function sales()
        {
            $error = "";
            $first = $this->SESSION->get('registration1');
            $tpl_head   = 'header';
            $tpl = 'registration_sales_uae';
            $tpl_footer = 'footer';
            $country_selected = $this->SESSION->get("country_selected");

            if ($this->POST)
            {

                $f = new Form('registration_sales_uae');

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

                                $company = new Company;
                                $company->name          = $f->name;
                                $company->address       = $f->address;
                                $company->fax           = $f->fax;
                                $company->phone         = $f->phone_code.$f->phone;
                                $company->city_id       = $f->city_id;
                                $company->state_id      = $f->state_id;
                                $company->country_id    = $f->country_selected;
                                $company->zipcode       = $f->zipcode;
                                $company->save();

                                $reg_date = time();
                                $user = new User;
                                $user->email               = $f->email;
                                $user->fname               = $f->fname;
                                $user->lname               = $f->lname;
                                $user->fname_tr            = $f->fname;
                                $user->lname_tr            = $f->lname;
                                $user->pass                = '';
                                $user->parent_id           = 0;
                                $user->group_id            = 8;
                                $user->date_reg            = $reg_date;
                                $user->company_id          = $company->id;
                                $user->private_zipcode     = $f->private_zipcode;
                                $user->gender              = $f->gender;
                                $user->national_id         = $f->national_id;
                                $user->private_address     = $f->private_address;
                                $user->mobile_number       = $f->mobile_number;
                                $user->private_phone       = $f->private_phone_code.$f->private_phone;
                                $user->private_state_id    = $f->private_state_id;
                                $user->private_city_id     = $f->private_city_id;
                                $user->private_fax         = $f->private_fax;
                                $user->status              = Application::STATUS_MS;

                                $calendar = $GLOBALS['System']->SESSION->get('calendar');
                                switch($calendar)
                                {
                                    case ENGLISH_CALENDAR:
                                        $user->date_birth          = mktime(0,0,0,  $f->dob_month, $f->dob_day, $f->dob_year );
                                        break;

                                    case PERSIAN_CALENDAR:
                                        $calendar = new Calendar();
                                        list($en_year,$en_month,$en_date) = $calendar->jalali_to_gregorian($f->dob_year, $f->dob_month, $f->dob_day);
                                        $user->date_birth          = mktime(0,0,0,  $en_month, $en_date, $en_year );
                                        break;

                                    default:
                                        $user->date_birth          = mktime(0,0,0,  $f->dob_month, $f->dob_day, $f->dob_year );

                                }

                                $user->pass                = Encryption::encrypt_password($new_pass);
                                $user->save();

                                $tpl = 'message';

                                $vcode = Application::make_vcode($f->email, $reg_date);

                                $user_inserted = new User("email",$f->email);

                                $url = ROOT_PATH."/index.php?controller=misc&method=validate&vc=".$vcode;
                                $mail  = Application::get_mail_parts($this->LANG[1513], $user_inserted, $url);
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
            $this->view('BODY', $tpl,  Array( 'error' => $error, 'display' => $display,'country_selected' => $country_selected,"lang" => $lang,'first_code' =>  $first_code,'second_code' => $second_code ,'captcha' => $captcha ) );
            $this->view('FOOTER', $tpl_footer);

        }
        
    }



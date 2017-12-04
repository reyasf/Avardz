<?php


    class registration_owner extends Controller 
    {
        public $wrapper    = 'page';
        
        function main() 
        {
            $tpl_head   = 'header';
            $tpl        = 'registration_owner';
            $tpl_footer = 'footer';
            $error = '';

            $country_selected = $this->SESSION->get("country_selected");
            $old_owner_account = $this->SESSION->get("old_owner_account");
           
            if ($this->POST) 
            {
                $f = new Form('registration_owner');
               
                switch ($f->evaluate())
                {
                    default:
                        print_r($f->errors);
                        break;
                        
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
                            
                            if ($error == '')
                            {
                                if (isset($this->POST['company_not_listed']))
                                {

                                    /** create new company **/
                                    $company = new Company;
                                    $company->name          = $f->name;
                                    $company->address       = $f->address;
                                    $company->fax           = $f->fax;
                                    $company->phone         = $f->phone_code.$f->phone;
                                    $company->num_employees = $f->noemp;
                                    $company->city_id       = $f->city_id;
                                    $company->state_id      = $f->state_id;
                                    $company->country_id    = $f->country_id;
                                    $company->zipcode       = $f->zipcode;
                                    $company->save();
                                }
                                else
                                {
                                    $company = new Company('id', $this->POST['company_id']);

                                    if (!$company->count)
                                        $error = "Invalid company selected!";
                                }

                                $map=Array ( 1=>5, 2=>6 );

                                if($f->noemp)
                                   $group_id = 2;
                                else
                                   $group_id = 1;

                                $avajang_group_id = $f->owner_type;

                                if (isset($this->POST['group_override']) and @$this->POST['group_override']!='')
                                {
                                    if($avajang_group_id == 1 || $avajang_group_id == 2)
                                        $group_id = $map[$avajang_group_id];
                                    else
                                        $error = $this->LANG[2825];
                                }
                                $reg_date = time();

                                /* create owner account */

                                $new_pass = Encryption::random_str();

                                $owner = new User;
                                $owner->email               = $f->email;
                                $owner->fname               = $f->fname;
                                $owner->lname               = $f->lname;
                                $owner->fname_tr            = $f->fname;
                                $owner->lname_tr            = $f->lname;
                                $owner->pass                = '';
                                $owner->parent_id           = 0;
                                $owner->group_id            = $group_id;
                                $owner->date_reg            = $reg_date;
                                $owner->company_id          = $company->id;
                                $owner->private_zipcode     = $f->private_zipcode;
                                $owner->gender              = $f->gender;
                                $owner->national_id         = $f->national_id;
                                $owner->private_address     = $f->private_address;
                                $owner->mobile_number       = $f->mobile_number;
                                $owner->private_phone       = $f->private_phone_code.$f->private_phone;
                                $owner->private_state_id    = $f->private_state_id;
                                $owner->private_city_id     = $f->private_city_id;
                                $owner->private_fax         = $f->private_fax;
                                
                                $owner_existing_account     = new User("email", $old_owner_account);
                                
                                $owner->existing_account    = $owner_existing_account->id;
                                $owner->status              = Application::STATUS_MS;

                                $calendar = $GLOBALS['System']->SESSION->get('calendar');
                                switch($calendar)
                                {
                                    case ENGLISH_CALENDAR:
                                        $owner->date_birth          = mktime(0,0,0,  $f->dob_month, $f->dob_day, $f->dob_year );
                                        break;

                                    case PERSIAN_CALENDAR:
                                        $calendar = new Calendar();
                                        list($en_year,$en_month,$en_date) = $calendar->jalali_to_gregorian($f->dob_year, $f->dob_month, $f->dob_day);
                                        $owner->date_birth          = mktime(0,0,0,  $en_month, $en_date, $en_year );
                                        break;

                                    default:
                                        $owner->date_birth          = mktime(0,0,0,  $f->dob_month, $f->dob_day, $f->dob_year );

                                }

                                $owner->pass                = Encryption::encrypt_password($new_pass);
                                if($error == '')
                                {
                                    $owner->save();

                                    $vcode = Application::make_vcode($f->email, $reg_date);

                                    $url = ROOT_PATH."/index.php?controller=misc&method=validate&vc=".$vcode;
                                    $mail  = Application::get_mail_parts($this->LANG[1513], $owner, $url);
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

            $first  = substr(md5(base64_encode($verify_code_first)),0,3);
            $second = substr(md5(base64_encode($verify_code_second)),0,3);

            $this->view('HEADER', $tpl_head,Array('banner'=>'homeban03'));
            $this->view('BODY', $tpl, Array('error' => $error, 'display' => $display,'old_owner_account' => $old_owner_account,'country_selected'=>$country_selected,"lang" => $lang,"first" => $first, 'second' => $second,'captcha' => $captcha) );
            $this->view('FOOTER', $tpl_footer);
            System::no_store();
        }
    }
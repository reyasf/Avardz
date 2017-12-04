<?php
/*
 * 
 * update users profile
 * 
 */

class update_profile extends Controller
    {
        public $wrapper = 'page';
        function main()
        {
            Application::deal_unauthorized();

            $tpl_head   = 'header';
            $tpl        = 'update_profile';
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
                $u = new User("id",$current_user->id);


                $calendar = $GLOBALS['System']->SESSION->get('calendar');
                            switch($calendar)
                            {
                                case ENGLISH_CALENDAR:
                                    $birth_date = date("j,n,Y",$u->date_birth);
                                    break;

                                case PERSIAN_CALENDAR:
                                    $calendar = new Calendar();
                                    $birth_date_en = date("Y-m-d",$u->date_birth);
                                    list($en_year,$en_month,$en_date)=explode("-",$birth_date_en);
                                    list($pr_year,$pr_month,$pr_date) = $calendar->gregorian_to_jalali($en_year, $en_month, $en_date);
                                    $birth_date = $pr_date.",".$pr_month.",".$pr_year;
                                    break;

                                default:
                                    $birth_date = date("j,n,Y",$u->date_birth);

                            }

                //$birth_date = date("j,n,Y",$u->date_birth);
                $message = $this->LANG[227];
                $message = Application::get_message_parts($message,$current_user);

                if($u->group_id == Application::GROUP_IRAN_MOBILE_SALES)
                {
                    $display_notification = "display:block;";
                    $display_company = "display:none;";
                }
                else
                {
                    $display_company = "display:block;";
                    $display_notification = "display:none;";
                }
            }

            if ($this->POST)
            {
                if (Application::confirm_user())
                {
                    $f = new Form('update_profile');

                    switch ($f->evaluate())
                    {
                        default:
                            print_r($f->errors);
                            break;

                        case FORM_OK :

                            $u->private_address = $f->private_address.$f->private_address2 ;
                            $u->private_city_id = $f->private_city_id;
                            $u->private_state_id = $f->private_state_id;
                            $u->private_zipcode = $f->private_zipcode;
                            $u->private_fax = $f->private_fax;

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

                            $u->save();

                            break;
                    }
                }
            }

            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('error' => $error,'user' => $u, 'birth'=>$birth_date, 'message' =>$message, 'display_notification' => $display_notification, 'display_company' => $display_company));
            $this->view('FOOTER', $tpl_footer);
        }
    }

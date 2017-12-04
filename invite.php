<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class invite extends Controller
{
        public $wrapper = 'home';
        function main()
        {
            Application::deal_unauthorized();
            Application::check_page_rights("The page you are trying to view is not available for you",  get_class($this));

            $tpl_head   = 'header';
            $tpl        = 'invite';
            $tpl_footer = 'footer';
            $tpl_ads    = 'ads';
            $error          = '';
            $banner  = Application::createBanner();
            $this->url = ROOT_PATH."?controller=invite";

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
            }

            if($this->POST)
            {
                if (Application::confirm_user())
                {
                    $f = new Form('invite');
                    switch ($f->evaluate())
                    {
                        case FORM_OK:
                            $invitation = new Invitation('email_invited',$f->email);
                            $invited_same_member = new Invitation('email_invited',$f->email,'and','email_invitor',$current_user->email);
                            $u = new User("email", $f->email);
                            if($invited_same_member->count)
                                $error = $this->LANG[1473];
                            elseif($invitation->count)
                                $error = $this->LANG[1471];
                            elseif($u->count)
                                $error = $this->LANG[1469];
                            else
                            {
                                $current_user = Application::confirm_user();
                                $u = new User("id",$current_user->id);

                                $invitation->fname_invited = $f->fname;
                                $invitation->lname_invited = $f->lname;
                                $invitation->email_invitor = $u->email;
                                $invitation->email_invited = $f->email;
                                $invitation->invitor_id = $u->id;
                                $invitation->invited_id = 0;
                                $invitation->city_invited = $f->private_city_id;
                                $invitation->state_invited = $f->private_state_id;
                                $invitation->mobile_invited = $f->mobile;
                                if($f->invtype == Application::INVITATION_CATEGORY_SALES)
                                {
                                    $invitation->company_invited = $u->company->name;
                                    $invitation->company_tel = $u->company->phone;
                                }
                                else
                                {
                                    $invitation->company_invited = $f->company;
                                    $invitation->company_tel = $f->phone;
                                }
                                
                                $invitation->category = $f->invtype;
                                $invitation->date = time();
                                $invitation->status = 3;

                                $invitation->save();
                                $url = ROOT_PATH."index.php?controller=registration";

                                switch($f->invtype)
                                {
                                    case 1:
                                        $email_message = $this->LANG[1503];
                                        $response_message = $this->LANG[1523];
                                        break;
                                    case 2:
                                        $email_message = $this->LANG[1761];
                                        $response_message = $this->LANG[1525];
                                        break;
                                    case 3:
                                        $email_message = $this->LANG[1761];
                                        $response_message = $this->LANG[1525];
                                        break;
                                }

                                $email_message = str_replace(Array("{title}","{last_name}"), Array($f->u_title,$f->lname), $email_message);
                                $mail  = Application::get_mail_parts($email_message, $u, $url);
                                $mail["body"] = Application::formatText3($mail['body']);

                                Email::push(
                                    Array(
                                        "to"        =>  $f->email,
                                        "subject"   =>  $mail['subject'],
                                        "body"      =>  $mail['body']
                                    )
                                );
                                
                                $mail_owner = Application::get_mail_parts($response_message, $u, $url);

                                Email::push(
                                    Array(
                                        "to"        =>  $u->email,
                                        "subject"   =>  $mail_owner['subject'],
                                        "body"      =>  $mail_owner['body']
                                    )
                                );

                                $message = Application::formatText($this->LANG[53]);
                                Application::display_message($message,$this->url);

                            }
                    }
                }
            }

            if($error=='')
                $display = ":none";
            else
                $display = ":block";

            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('error' => $error , 'display' => $display, 'country_selected' => $u->company->country_id));
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);

        }

        function invitation_history()
        {
            Application::deal_unauthorized();
            Application::check_page_rights("The page you are trying to view is not available for you",  get_class($this));

            $tpl_head   = 'header';
            $tpl        = 'invitation_history';
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
                $u = new User("id",$current_user->id);
                $country_selected = $u->company->country_id;
            }

            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('error' => $error,'country_selected' => $country_selected));
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);
        }
}

<?php

/* 
 * Serial Registration
 * By Members
 */

class register_serial extends Controller
{
    public $wrapper = 'home';

    function main()
    {
        Application::deal_unauthorized();
        Application::check_page_rights("The page you are trying to view is not available for you",  get_class($this));

        $tpl_head   = 'header';
        $tpl = 'register_serial';
        $tpl_footer = 'footer';
        $tpl_ads    = 'ads';
        $banner  = Application::createBanner();
        $error = '';
        $current_user = Application::confirm_user();
        $this->url = ROOT_PATH."?controller=register_serial";
        if($this->POST)
        {
            if (Application::confirm_user())
            {
            $f = new Form('register_serial');

            switch ($f->evaluate())
            {
                default:
                    break;

                case FORM_OK :
                    $serial = $f->serial_number;
                    $check  = $f->check_number;

                    $serial = new Serial("serial", $serial, "and", "check_no", $check);

                    if ($serial->count)
                    {
                        /**
                         * get permissions for serial's related product
                         * and current group
                         */
                        $pg = new Products_group(
                            'group_id', $current_user->group_id,
                            'and',
                            'product_id', $serial->product_id
                        );
                        
                        if ($pg->count == 0)
                              Application::display_message($this->LANG[489],$this->url);
                        else
                        {
                            $rs = new Serials_user('serial_id', $serial->id);
                            $insert_new=false;

                            $lock_serial_main = new Duplicate('serial_id',$serial->id,'and','serial_registered_by',$current_user->id,'and','status', Application::STATUS_SERIAL_SWITCHED);
                            $lock_serial_sub = new Duplicate('serial_id',$serial->id,'and','serial_tried_by',$current_user->id,'and','status', Application::STATUS_SERIAL_LOCKED);

                            if ($lock_serial_main->count || $lock_serial_sub->count)
                            {
                                $insert_new=false;
                                Application::display_message($this->LANG[3149],$this->url);
                            }
                            elseif ($rs->count)
                            {
                                foreach ($rs as $registered_serial)
                                {
                                    /**
                                     * If serial already registered
                                     */
                                    if ($registered_serial->user_id == $current_user->id)
                                    {
                                        Application::display_message($this->LANG[549],$this->url);
                                        break;
                                    }
                                    else
                                    {
                                        if($registered_serial->user->group_id == Application::GROUP_AVAJANG2) //to all
                                        {
                                            $du = new Duplicate("serial_id",$serial->id,"and","serial_registered_by",$registered_serial->user_id,"and","serial_tried_by",$current_user->id);
                                            if($du->count==0)
                                            {
                                                /**
                                                * add a duplicate for avajang member
                                                */
                                                $d = new Duplicate;
                                                $d->serial_id = $serial->id;

                                                /**
                                                * the gnagster
                                                */
                                                $d->serial_registered_by = $registered_serial->user_id;

                                                /**
                                                * innocent
                                                */
                                                $d->serial_tried_by = $current_user->id;
                                                $d->serial_try_date = time();
                                                $d->save();

                                                $du_main    = new User("id",$registered_serial->user_id);
                                                $du_sub     = new User("id",$current_user->id);

                                                $du_main->dup_main++;
                                                $du_main->save();

                                                $du_sub->dup_sub++;
                                                $du_sub->save();

                                                switch($du_main->dup_main)
                                                {
                                                    case 1:
                                                        $mail_main_message = $this->LANG[2505];
                                                        break;
                                                    case 2:
                                                        $mail_main_message = $this->LANG[2507];
                                                        break;
                                                    default:
                                                        $mail_main_message = $this->LANG[2509];
                                                        break;
                                                }

                                                $url = "http://www.avardz.net/avardzweb/dev/termsncond/termsen.html";
                                                
                                                $mail_main_message = str_replace(Array("{product_model}","{sn}","{check_number}","{initial_reg_date}"), Array($registered_serial->serial->product->model,$registered_serial->serial->serial,$registered_serial->serial->check_no,Application::format_date($registered_serial->reg_date)), $mail_main_message);

                                                $mail_main = Application::get_mail_parts($mail_main_message, $du_main, $url);
                                                $mail_main['body'] = Application::formatText3($mail_main['body']);

                                                Email::push(
                                                    Array(
                                                        "to"        =>  $du_main->email,
                                                        "subject"   =>  $mail_main['subject'],
                                                        "body"      =>  $mail_main['body']
                                                    )
                                                );

                                                $mail_sub_message = $this->LANG[333];
                                                $mail_sub_message = str_replace(Array("{product_model}","{sn}","{check_number}","{initial_reg_date}"), Array($registered_serial->serial->product->model,$registered_serial->serial->serial,$registered_serial->serial->check_no,Application::format_date($registered_serial->reg_date)), $mail_sub_message);
                                                $mail_sub = Application::get_mail_parts($mail_sub_message, $du_sub);

                                                Email::push(
                                                    Array(
                                                        "to"        =>  $du_sub->email,
                                                        "subject"   =>  $mail_sub['subject'],
                                                        "body"      =>  $mail_sub['body']
                                                    )
                                                );
                                            }

                                            $error_msg = Application::formatText($this->LANG[2635]);
                                            Application::display_message($error_msg,$this->url);
                                            $insert_new=false;
                                            break;
                                        }
                                        else
                                        {
                                            /*
                                             * check duplicate already exists
                                             *
                                             */

                                            $du = new Duplicate("serial_id",$serial->id,"and","serial_registered_by",$registered_serial->user_id,"and","serial_tried_by",$current_user->id);
                                            if($du->count==0)
                                            {
                                                /**
                                                * add a duplicate for avardz member
                                                */
                                                $d = new Duplicate;
                                                $d->serial_id = $serial->id;
                                            
                                                /**
                                                * the gnagster
                                                */
                                                $d->serial_registered_by = $registered_serial->user_id;

                                                /**
                                                * innocent
                                                */
                                                $d->serial_tried_by = $current_user->id;
                                                $d->serial_try_date = time();
                                                $d->save();

                                                $du_main    = new User("id",$registered_serial->user_id);
                                                $du_sub     = new User("id",$current_user->id);

                                                $du_main->dup_main++;
                                                $du_main->save();

                                                $du_sub->dup_sub++;
                                                $du_sub->save();

                                                switch($du_main->dup_main)
                                                {
                                                    case ($du_main->dup_main == 1 || $du_main->dup_main == 2):
                                                        $mail_main_message = $this->LANG[1591];
                                                        break;
                                                    case 3:
                                                        $mail_main_message = $this->LANG[1949];
                                                        break;
                                                    default:
                                                        $mail_main_message = $this->LANG[2499];
                                                        break;
                                                }

                                                $url = "http://www.avardz.net/avardzweb/dev/termsncond/termsen.html";
                                                $mail_main_message = str_replace(Array("{product_model}","{sn}","{check_number}","{initial_reg_date}"), Array($registered_serial->serial->product->model,$registered_serial->serial->serial,$registered_serial->serial->check_no,Application::format_date($registered_serial->reg_date)), $mail_main_message);
                                                $mail_main = Application::get_mail_parts($mail_main_message, $du_main, $url);
                                                $mail_main['body'] = Application::formatText3($mail_main['body']);

                                                Email::push(
                                                    Array(
                                                        "to"        =>  $du_main->email,
                                                        "subject"   =>  $mail_main['subject'],
                                                        "body"      =>  $mail_main['body']
                                                    )
                                                );

                                                $mail_sub_message = $this->LANG[333];
                                                $mail_sub_message = str_replace(Array("{product_model}","{sn}","{check_number}","{initial_reg_date}"), Array($registered_serial->serial->product->model,$registered_serial->serial->serial,$registered_serial->serial->check_no,Application::format_date($registered_serial->reg_date)), $mail_sub_message);
                                                $mail_sub = Application::get_mail_parts($mail_sub_message, $du_sub);

                                                Email::push(
                                                    Array(
                                                        "to"        =>  $du_sub->email,
                                                        "subject"   =>  $mail_sub['subject'],
                                                        "body"      =>  $mail_sub['body']
                                                    )
                                                );
                                            }
                                            
                                            $error_msg = Application::formatText($this->LANG[2635]);
                                            Application::display_message($error_msg,$this->url);
                                            $insert_new=false;
                                            break;
                                        }
                                    }
                                }
                            } else
                                $insert_new=true;


                            if ($insert_new)
                            {
                                $first_reg=false;
                                $rs = new Serials_user('user_id', $current_user->id);
                                if ($rs->count == 0)
                                    $first_reg=true;

                                /**
                                 * register the serial here
                                 */
                                $su = new Serials_user;
                                $su->user_id   = $current_user->id;
                                $su->serial_id = $serial->id;
                                $su->reg_date  = time();
                                $su->save();


                                /**
                                 * Grant points and update total points
                                 */
                                $p = new Point;
                                $p->user_id = $current_user->id;
                                $p->rel_id  = $serial->id;
                                $p->points  = (int)($serial->product->points * ($current_user->group->point_percent / 100));
                                $p->type    = 'P';
                                $p->reg_date = time();
                                $p->save();

                                $u = new User("id",$current_user->id);
                                $u->total_points += $p->points;
                                $u->save();

                                if ($serial->avajang_user_id !=0 && ($current_user->id != $serial->avajang_user_id))
                                {
                                    /**
                                     * grant the point to avajang user
                                     */
                                    
                                    
                                    /**
                                     * get permissions for avajang user
                                     * and group
                                     */
                                    $u2 = new User('id', $serial->avajang_user_id);
                                    $av_pg = new Products_group(
                                        'group_id', $u2->group_id,
                                        'and',
                                        'product_id', $serial->product_id
                                    );
                                    if($av_pg->count)
                                    {
                                        $p = new Point;
                                        $p->user_id = $serial->avajang_user_id;
                                        $p->rel_id  = $serial->id;
                                        $group_percent = new Group("id",Application::GROUP_AVAJANG);
                                        $p->points  = (int)($serial->product->points * ($group_percent->point_percent / 100));
                                        $p->type    = 'P';
                                        $p->reg_date = time();
                                        $p->save();

                                        $u2->total_points += $p->points;
                                        $u2->save();
                                    }
                                }

                                if ($current_user->parent_id !=0)
                                {
                                    /**
                                     * grant the point to parent user
                                     */

                                    $u2 = new User('id',$current_user->parent_id);
                                    $p = new Point;
                                    $p->user_id = $u2->id;
                                    $p->rel_id  = $serial->id;
                                    $p->points  = (int)($serial->product->points * ($u2->group->point_percent / 100));
                                    $p->type    = 'P';
                                    $p->reg_date = time();
                                    $p->save();

                                    $u2->total_points += $p->points;
                                    $u2->save();
                                }

                                

                                /**
                                 * @todo Implement referal points later
                                 * @todo Duplicate warnings
                                 */

                                /*****************************/
                                //referal points
                                
                                if($first_reg)
                                {
                                    $today = time();
                                    $find_referer = new Invitation("email_invited",$current_user->email);
                                    if($find_referer->count)
                                    {
                                        $referer = new User("email",$find_referer->email_invitor);
                                        $difference = $today-$current_user->date_reg;
                                        $difference = $difference/86400;

                                        if(intval($difference)<=21)
                                        {
                                            switch($current_user->group_id)
                                            {
                                                case Application::GROUP_IRAN_MOBILE_SALES:
                                                    $referal_points = new Other_point("id",Application::REFERAL_POINTS_MOBILE_SALES);
                                                    break;
                                                case Application::GROUP_SHOP_OWNER_NO_SALES || Application::GROUP_SHOP_OWNER_WITH_SALES || Application::GROUP_AVAJANG || Application::GROUP_AVAJANG2:
                                                    $referal_points = new Other_point("id",Application::REFERAL_POINTS_OWNER_OR_DD);
                                                    break;
                                                case Application::GROUP_UAE_SHOP_OWNER:
                                                    $referal_points = new Other_point("id",Application::REFERAL_POINTS_UAE_OWNER);
                                                    break;
                                                case Application::GROUP_UAE_SALES_PERSON:
                                                    $referal_points = new Other_point("id",Application::REFERAL_POINTS_UAE_SALES);
                                                    break;
                                            }

                                            $referer->total_points += $referal_points->points;
                                            $referer->save();
                                            
                                            $point = new Point();
                                            $point->rel_id = $referal_points->id;
                                            $point->points = $referal_points->points;
                                            $point->type = "R";
                                            $point->user_id = $referer->id;
                                            $point->reg_date = time();
                                            $point->save();

                                        }
                                    }
                                }

                                Application::display_message($this->LANG[463],$this->url);
                            }
                        }
                    }
                    else
                    {
                        $error_msg = Application::formatText($this->LANG[1847]);
                        Application::display_message($error_msg,$this->url);
                    }
            }
        }
        }

        $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
        $this->view('BODY', $tpl, Array('error' => $error) );
        $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
        $this->view('FOOTER', $tpl_footer);
    }
}

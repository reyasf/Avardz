<?php

    class ajax extends Controller
    {
        public $wrapper = 'simple';
    
        function get_company_details()
        {
            $id = intval(@$this->GET['id']);

            $c = new Company('id', "$id");
            $address = $c->address;

            if($c->count)
            {
                $this->view('BODY', 'registration_owner_company_details', Array('company' => $c , 'address'=>$address));
            }
        }

        function redeem_gifts()
        {
            Application::deal_unauthorized();

            $current_user = Application::confirm_user();
            $process = true;
            
            if (isset($this->GET['v']))
            {
                $items = 0;
                $gg = $current_user->group->gifts;
                $pts = Application::get_current_user_points();

                $v = trim($this->GET['v']);
                if ($v == '') return;
                $v = explode("-",$v);
                
                $selected = $gids = $done = Array();

                foreach ($v as $k => $gf)
                {
                    $gf = explode(".",$gf);
                    $selected [intval(@$gf[0])] = intval(@$gf[1]);
                    $gids []= intval(@$gf[0]);
                }

                if ($gg->count && $pts > 0)
                {
                    $total = 0;
                    $ordered_quantity = 0;
                    foreach ($gg as $gift)
                    {
                        if (in_array($gift->id, $gids))
                        {
                            $done []= Array(
                                    "gift_id"   =>  $gift->id,
                                    "quantity"  =>  $selected[$gift->id],
                                    "points"    =>  $gift->points,
                            );
                            $total += $selected[$gift->id] * $gift->points;
                            $items++;
                            $ordered_quantity += $selected[$gift->id];

                            $gift_redeemed = new Redeem("gift_id",$gift->id,"and","user_id",$current_user->id,"and","month(from_unixtime(date))",date("n"),"and","year(from_unixtime(date))",date("Y"));
                            $total_redemption = 0;

                            if(($gift_redeemed->count) && ($process))
                            {
                                foreach($gift_redeemed as $gr)
                                    $total_redemption += $gr->quantity;

                                $total_redemption += $selected[$gift->id];

                                if($total_redemption > $gift->max_order_level)
                                    $process = false;
                            }
                        }
                    }
                    if (($total > $pts) || !($process))
                    {
                        $this->view_str('BODY', "Invalid input detected. Please reload page!");
                    }
                    else
                    {
                        $done['redeemed_points']  = $total;
                        $done['remaining_points'] = $pts - $total;
                        $done['items_ordered']    = $items;
                        $done['ordered_quantity'] = $ordered_quantity;
                        
                        $this->SESSION->set('gifts', $done);
                        $this->view_str('BODY', "success");
                    }
                }
                else
                {
                    $this->view('BODY','error');
                }
           }
        }

        function redeem_gifts_old()
        {
            Application::deal_unauthorized();

            $current_user = Application::confirm_user();
            
            if (isset($this->GET['v']))
            {
                $items = 0;
                $gg = $current_user->group->gifts;
                $pts = Application::get_current_user_points();

                $v = trim($this->GET['v']);
                if ($v == '') return;
                $v = explode("-",$v);
                
                $selected = $gids = $done = Array();

                foreach ($v as $k => $gf)
                {
                    $gf = explode(".",$gf);
                    $selected [intval(@$gf[0])] = intval(@$gf[1]);
                    $gids []= intval(@$gf[0]);
                }

                if ($gg->count && $pts > 0)
                {
                    $total = 0;
                    foreach ($gg as $gift)
                    {
                        if (in_array($gift->id, $gids))
                        {
                            //this gift was selected
                            $done []= Array(
                                    "gift_id"   =>  $gift->id,
                                    "quantity"  =>  $selected[$gift->id],
                                    "points"    =>  $gift->points,
                            );
                            $total += $selected[$gift->id] * $gift->points;
                            $items++;
                        }
                    }
                    if ($total > $pts)
                    {
                        $this->view_str('BODY', "Invalid input detected. Please reload page!");
                    }
                    else
                    {
                        $done['redeemed_points']  = $total;
                        $done['remaining_points'] = $pts - $total;
                        $done['items_ordered']    = $items;
                        
                        $this->SESSION->set('gifts', $done);
                        $this->view_str('BODY', "success");
                    }
                }
                else
                {
                    $this->view('BODY','error');
                }
           }
        }

        function get_gift_search()
        {
            Application::deal_unauthorized();

            $current_user = Application::confirm_user();

            $gg = $current_user->group->gifts;
            $gift_id = @$this->GET['id'];

            $searched_ids = explode("|",$gift_id);
            $today = time();
            
            if($gg->count && isset($this->GET['range']))
            {
                foreach ($gg as $gift)
                {
                    $found=true;
                    $total_redemption = 0;

                    $gift_redeemed = new Redeem("gift_id",$gift->id,"and","user_id",$current_user->id,"and","month(from_unixtime(date))",date("n"),"and","year(from_unixtime(date))",date("Y"));
                    if($gift_redeemed->count)
                        foreach($gift_redeemed as $gr)
                            $total_redemption += $gr->quantity;

                    if (isset($this->GET['range']))
                    {
                                        if (strpos($this->GET['range'],'-',0) === FALSE)
                                        {
                                            if(!($gift->points > intval($this->GET['range'])) || ($gift->date_available_upto < $today) || ($gift->stock < 0) || ($total_redemption > $gift->max_order_level))
                                                $found=false;
                                        }
                                        else
                                        {
                                            @list($min,$max) = explode("-",$this->GET['range']);

                                            $min = intval($min);
                                            $max = intval($max);

                                            if (!($gift->points >= $min && $gift->points <= $max) || ($gift->date_available_upto < $today) || ($gift->stock < 0) || ($total_redemption > $gift->max_order_level))
                                                  $found=false;
                                        }
                    }
                    if ($found)
                        $this->view('BODY', 'redeem_gift_single', Array('gift' => $gift,'total_ordered' => $total_redemption));
                }
            }


            foreach($searched_ids as $s_id)
            {

                if($s_id !='')
                {
                    $decoded = @base64_decode($s_id);
                    @list($gift_cat_id, $dec_message) = explode('|', $decoded);
                    $gift_cat_id = base64_decode($gift_cat_id);

                    if($gg->count) {

                        $id=0;
                        foreach ($gg as $gift)
                        {
                            $found=true;
                            $total_redemption = 0;

                            $gift_redeemed = new Redeem("gift_id",$gift->id,"and","user_id",$current_user->id,"and","month(from_unixtime(date))",date("n"),"and","year(from_unixtime(date))",date("Y"));
                            if($gift_redeemed->count)
                                foreach($gift_redeemed as $gr)
                                    $total_redemption += $gr->quantity;

                            if(intval($gift_cat_id) > 0)
                            {
                                if($gift->category_id != $gift_cat_id || ($gift->date_available_upto < $today) || ($gift->stock < 0) || ($total_redemption > $gift->max_order_level))
                                    $found=false;
                            }

                            if ($found)
                                $this->view('BODY', 'redeem_gift_single', Array('gift' => $gift,'id' => ++$id,'total_ordered' => $total_redemption));
                        }
                    }
                    else
                    {
                        $this->view('BODY','error_ticket',Array('message' => $this->LANG[2657]));
                    }
                }
            }

        }

        function get_all_gifts_old()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $gg = $current_user->group->gifts;

            if ($gg->count)
            {
                $id=0;
                foreach ($gg as $gift)
                {
                    $this->view('BODY', 'redeem_gift_single', Array('gift' => $gift,'id' => ++$id));
                }
            }
            else
            {
                $this->view('BODY','error');
            }
        }

        function get_all_gifts()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $gg = $current_user->group->gifts;
            $today = time();

            if ($gg->count)
            {
                $id=0;
                $this->view('BODY', 'redeem_gift_single_head');

                foreach ($gg as $gift)
                {
                    
                    $gift_redeemed = new Redeem("gift_id",$gift->id,"and","user_id",$current_user->id,"and","month(from_unixtime(date))",date("n"),"and","year(from_unixtime(date))",date("Y"));
                    $total_redemption = 0;

                    if($gift_redeemed->count)
                        foreach($gift_redeemed as $gr)
                            $total_redemption += $gr->quantity;
                    
                    if(($gift->date_available_upto > $today) && ($gift->stock > 0) && ($total_redemption < $gift->max_order_level))
                        $this->view('BODY', 'redeem_gift_single', Array('gift' => $gift,'id' => $id++,'total_ordered' => $total_redemption));
                                   
                    if(!($id%3))
                        $this->view('BODY', 'redeem_divider_line');
                }
                
                 $this->view('BODY', 'redeem_gift_single_footer');
                 $this->view('BODY', 'redeem_divider_line');
            }
            else
            {
                $this->view('BODY','error_ticket',Array('message' => $this->LANG[2657]));
            }
        }

        function get_affordable_gifts()
        {
            Application::deal_unauthorized();

            $current_user = Application::confirm_user();
            $today = time();

                $gg = $current_user->group->gifts;
                $pts = Application::get_current_user_points();
                $flag = false;

                if ($gg->count && $pts > 0 && ($gg->stock > 0))
                {
                    foreach ($gg as $gift)
                    {
                        $gift_redeemed = new Redeem("gift_id",$gift->id,"and","user_id",$current_user->id,"and","month(from_unixtime(date))",date("n"),"and","year(from_unixtime(date))",date("Y"));
                        $total_redemption = 0;
                        
                        if($gift_redeemed->count)
                            foreach($gift_redeemed as $gr)
                                $total_redemption += $gr->quantity;
                        if ($gift->points <= $pts && ($gift->date_available_upto > $today) && ($gift->stock > 0) && ($total_redemption < $gift->max_order_level))
                        {
                            $this->view('BODY', 'redeem_gift_single', Array('gift' => $gift,'total_ordered' => $total_redemption));
                            $flag = true;
                        }
                    }
                }
                
                if(!$flag)
                    $this->view('BODY','error_ticket',Array('message' => $this->LANG[3171]));
        }

        function points_history()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $su = new Serials_user("user_id",$current_user->id);
            
            if ($su->count)
            {
                $id=0;
                foreach ($su as $serials)
                {
                    $id++;
                    $this->view('BODY', 'points_history_single', Array('serials' => $serials,'id' => $id));
                }
            }
            else
            {
                $this->view('BODY','error');
            }

        }

        function purchased_products()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $s = new Serial("avajang_user_id",$current_user->id);

            if ($s->count)
            {
                $id=0;
                foreach ($s as $serials)
                {
                    $id++;
                    $su = new Serials_user("serial_id",$serials->id);
                    $status = $su->count ? $this->LANG[2787] : $this->LANG[2789];
                    $reg_date = $su->reg_date ? Application::format_date($su->reg_date) : "-";
                    if($su->count)
                        $sold_to = $su->user_id == $current_user->id ? $this->LANG[2785] : $this->LANG[1639];
                    else
                        $sold_to = "-";
                    $this->view('BODY', 'purchased_prods_single', Array('serials' => $serials,'reg_date'=>$reg_date,'sold_to'=>$sold_to,'status'=>$status,'id' => $id));
                }
            }
            else
            {
                $this->view('BODY','error');
            }
        }

        function purchased_products_search()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $arrserials = array();

            $calendar = $GLOBALS['System']->SESSION->get('calendar');
            switch($calendar)
            {

                case ENGLISH_CALENDAR:
                    $from_date = mktime(0,0,0,intval(@$this->GET['fm']),intval(@$this->GET['fd']),intval(@$this->GET['fy']) );
                    $to_date = mktime(0,0,0,intval(@$this->GET['tm']),intval(@$this->GET['td']),intval(@$this->GET['ty']) );
                    break;

                case PERSIAN_CALENDAR:
                    $calendar = new Calendar();
                    list($en_from_year,$en_from_month,$en_from_date) = $calendar->jalali_to_gregorian(intval(@$this->GET['fy']), intval(@$this->GET['fm']), intval(@$this->GET['fd']));
                    list($en_to_year,$en_to_month,$en_to_date) = $calendar->jalali_to_gregorian(intval(@$this->GET['ty']), intval(@$this->GET['tm']), intval(@$this->GET['td']));
                    $from_date              = mktime(0,0,0,  $en_from_month, $en_from_date, $en_from_year );
                    $to_date                = mktime(0,0,0,  $en_to_month, $en_to_date, $en_to_year );
                    break;

                default:
                    $from_date = mktime(0,0,0,intval(@$this->GET['fm']),intval(@$this->GET['fd']),intval(@$this->GET['fy']) );
                    $to_date = mktime(0,0,0,intval(@$this->GET['tm']),intval(@$this->GET['td']),intval(@$this->GET['ty']) );

            }

            $s = new Serial("avajang_user_id",$current_user->id);
            $flag = 0;
            
            if($s->count)
            {
                    $id = 0;
                    foreach ($s as $serials)
                    {
                        $id++;
                        $su = new Serials_user("serial_id",$s->id,"and","reg_date",">=",$from_date,"and","reg_date","<=",$to_date);
                        if($su->count)
                        {
                            $flag = 1;
                            $status = $su->count ? $this->LANG[2787] : $this->LANG[2789];
                            $reg_date = $su->reg_date ? Application::format_date($su->reg_date) : "-";
                            if($su->count)
                                $sold_to = $su->user_id == $current_user->id ? $this->LANG[2785] : $this->LANG[1639];
                            else
                                $sold_to = "-";
                            $this->view('BODY', 'purchased_prods_single', Array('serials' => $serials,'reg_date'=>$reg_date,'sold_to'=>$sold_to,'status'=>$status,'id' => $id));
                        }
                    }
                    if(!$flag)
                        $this->view('BODY','error');
            }
            else
            {
                $this->view('BODY','error');
            }

        }

        function invitation_single()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $iu = new Invitation("invitor_id",$current_user->id);
            $map = Array(1 => 'My Employee' ,2 => 'Shop Owner', 3 => 'Mobile Sales' );
            $status = intval(@$this->GET['status']);
            if($status > 0)
            {
                switch($status)
                {
                    case 1:
                        $id=0;
                        foreach ($iu as $invitation)
                        {
                            $id++;
                            $u = new User("email",$invitation->email_invited);
                            if($u->count)
                                $this->view('BODY', 'invitation_single', Array('invitation' => $invitation, 'id' => $id , 'category' => $map[$invitation->category] , 'status' => $this->LANG[255]));
                        }
                        break;
                    case 2:
                        $id=0;
                        foreach ($iu as $invitation)
                        {
                            $id++;
                            $u = new User("email",$invitation->email_invited);
                            if(!$u->count)
                                $this->view('BODY', 'invitation_single', Array('invitation' => $invitation, 'id' => $id , 'category' => $map[$invitation->category] , 'status' => $this->LANG[2755]));
                        }
                        break;
                }
            }
            elseif ($iu->count)
            {
                $id=0;
                foreach ($iu as $invitation)
                {
                    $id++;
                    $u = new User("email",$invitation->email_invited);
                    if($u->count)
                        $status = $this->LANG[255];
                    else
                        $status = $this->LANG[2755];
                    $this->view('BODY', 'invitation_single', Array('invitation' => $invitation, 'id' => $id , 'category' => $map[$invitation->category] , 'status' => $status));
                }
            }
            else
            {
                $this->view('BODY','error');
            }

        }

        function invitation_history()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $iu = new Invitation("invitor_id",$current_user->id);
            $map = Array(1 => $this->LANG[2621] ,2 => $this->LANG[442], 3 => $this->LANG[286] );
            $status_inv = Array(3 => $this->LANG[2755], 1 => $this->LANG[255] , 2 => $this->LANG[171]);
            $status = intval(@$this->GET['status']);
            $LANG = $GLOBALS['System']->COOKIE->get('lang');
            
            if ($status > 0)
            {
                    $id=0;
                    $si = new Invitation("invitor_id",$current_user->id,"and","status",$status);
                    if($si->count)
                    {
                        $city = new City("id",intval($si->city_invited));
                        $state = new State("id",intval($si->state_invited));
                        if($LANG=="en" || $LANG==NULL)
                        {
                            $city_name = $city->name; $state_name = $state->name;
                        }
                        else
                        {
                            $city_name = $city->name_tr; $state_name = $state->name_tr;
                        }
                        foreach ($si as $invitation)
                        {
                            $id++;
                            $display = $invitation->status == Application::INVITATION_STATUS_EXPIRED ? "block" : "none";
                            $inv_id = base64_encode(base64_encode($invitation->id).'|'.md5($invitation->id.$invitation->date));
                            $this->view('BODY', 'invitation_history_single', Array('invitation' => $invitation, 'id' => $id , 'category' => $map[$invitation->category] , 'status' => $status_inv[$invitation->status], 'city' => $city_name, 'state' => $state_name, 'invid' => $inv_id, 'display' => $display));
                        }
                    }
                    else
                    {
                        $this->view('BODY','error');
                    }
            }
            elseif ($iu->count)
            {
                $id=0;
                foreach ($iu as $invitation)
                {
                    $id++;
                    $display = $invitation->status == Application::INVITATION_STATUS_EXPIRED ? "block" : "none";
                    $city = new City("id",intval($invitation->city_invited));
                    $state = new State("id",intval($invitation->state_invited));
                    if($LANG=="en" || $LANG==NULL)
                    {
                        $city_name = $city->name; $state_name = $state->name;
                    }
                    else
                    {
                        $city_name = $city->name_tr; $state_name = $state->name_tr;
                    }
                    $inv_id = base64_encode(base64_encode($invitation->id).'|'.md5($invitation->id.$invitation->date));
                    $this->view('BODY', 'invitation_history_single', Array('invitation' => $invitation, 'id' => $id , 'category' => $map[$invitation->category] , 'status' => $status_inv[$invitation->status], 'city' => $city_name, 'state' => $state_name, 'invid' => $inv_id, 'display' => $display));
                }
            }
            else
            {
                $this->view('BODY','error');
            }

        }

        function invitation_history_search()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();

            $calendar = $GLOBALS['System']->SESSION->get('calendar');
            switch($calendar)
            {

                case ENGLISH_CALENDAR:
                    $from_date = mktime(0,0,0,intval(@$this->GET['fm']),intval(@$this->GET['fd']),intval(@$this->GET['fy']) );
                    $to_date = mktime(0,0,0,intval(@$this->GET['tm']),intval(@$this->GET['td']),intval(@$this->GET['ty']) );
                    break;

                case PERSIAN_CALENDAR:
                    $calendar = new Calendar();
                    list($en_from_year,$en_from_month,$en_from_date) = $calendar->jalali_to_gregorian(intval(@$this->GET['fy']), intval(@$this->GET['fm']), intval(@$this->GET['fd']));
                    list($en_to_year,$en_to_month,$en_to_date) = $calendar->jalali_to_gregorian(intval(@$this->GET['ty']), intval(@$this->GET['tm']), intval(@$this->GET['td']));
                    $from_date              = mktime(0,0,0,  $en_from_month, $en_from_date, $en_from_year );
                    $to_date                = mktime(0,0,0,  $en_to_month, $en_to_date, $en_to_year );
                    break;

                default:
                    $from_date = mktime(0,0,0,intval(@$this->GET['fm']),intval(@$this->GET['fd']),intval(@$this->GET['fy']) );
                    $to_date = mktime(0,0,0,intval(@$this->GET['tm']),intval(@$this->GET['td']),intval(@$this->GET['ty']) );

            }

            $map = Array(1 => $this->LANG[2621] ,2 => $this->LANG[442], 3 => $this->LANG[286] );
            $status_inv = Array(3 => $this->LANG[2755], 1 => $this->LANG[255] , 2 => $this->LANG[171]);
            $LANG = $GLOBALS['System']->COOKIE->get('lang');
            
            if($from_date < $to_date)
                $iu = new Invitation ("invitor_id",$current_user->id,"and","date",">=",$from_date,"and","date","<=",$to_date+TIME_FACTOR);
            else
                $iu = new Invitation ("invitor_id",$current_user->id);

            if($iu->count)
            {
                $id=0;
                foreach ($iu as $invitation)
                {
                    $id++;
                    $display = $invitation->status == Application::INVITATION_STATUS_EXPIRED ? "block" : "none";
                    $city = new City("id",intval($invitation->city_invited));
                    $state = new State("id",intval($invitation->state_invited));
                    if($LANG=="en" || $LANG==NULL)
                    {
                        $city_name = $city->name; $state_name = $state->name;
                    }
                    else
                    {
                        $city_name = $city->name_tr; $state_name = $state->name_tr;
                    }
                    $inv_id = base64_encode(base64_encode($invitation->id).'|'.md5($invitation->id.$invitation->date));
                    $this->view('BODY', 'invitation_history_single', Array('invitation' => $invitation, 'id' => $id , 'category' => $map[$invitation->category] , 'status' => $status_inv[$invitation->status], 'city' => $city_name, 'state' => $state_name, 'invid' => $inv_id, 'display' => $display));
                }
            }
            else
            {
                $this->view('BODY','error');
            }

        }

        function delete_invitation_confirmed()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();

            $invitation_id = @$this->GET['id'];
            $flag=true;

            $data = @base64_decode($invitation_id);
            if (!$data) $flag=false;

            @list($id, $code) = explode('|', $data);

            $id=base64_decode($id);

            if (trim($code) == '') $flag=false;
            if (intval($id) == 0) $flag=false;

            if($flag)
            {
                $invitation = new Invitation("id",$id,"and","invitor_id",$current_user->id);
                if($invitation->count)
                {
                    $invitation->deleted = 1;
                    $invitation->save();
                }
            }


        }

        function resend_invitation()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();

            $invitation_id = @$this->GET['id'];
            $flag=true;

            $data = @base64_decode($invitation_id);
            if (!$data) $flag=false;

            @list($id, $code) = explode('|', $data);

            $id=base64_decode($id);

            if (trim($code) == '') $flag=false;
            if (intval($id) == 0) $flag=false;

            if($flag)
            {
                $invitation = new Invitation("id",$id,"and","invitor_id",$current_user->id);
                if($invitation->count)
                {
                    $date = time();
                    $invitation->date = $date;
                    $invitation->status = Application::INVITATION_STATUS_PENDING;
                    $invitation->save();

                    $url = ROOT_PATH."index.php?controller=registration";
                    $u = new User("id",$current_user->id);

                                switch($invitation->category)
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

                                $mail  = Application::get_mail_parts($email_message, $u, $url);
                                $mail["body"] = Application::formatText3($mail['body']);

                                Email::push(
                                    Array(
                                        "to"        =>  $invitation->email_invited,
                                        "subject"   =>  $mail['subject'],
                                        "body"      =>  $mail['body']
                                    )
                                );

                                $mail_owner = Application::get_mail_parts($response_message, $u, $url);

                                Email::push(
                                    Array(
                                        "to"        =>  $invitation->email_invited,
                                        "subject"   =>  $mail_owner['subject'],
                                        "body"      =>  $mail_owner['body']
                                    )
                                );
                }
            }
        }

        function statement()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $user_id = $current_user->id;
            $user_group = $current_user->group_id;

            $point_category = Array(1 => $this->LANG[1651],2 => $this->LANG[1651],3 => $this->LANG[1651],4 => $this->LANG[1651],5 => $this->LANG[1651],6 => $this->LANG[1651],7 => $this->LANG[1651],8 => $this->LANG[1651],9 => $this->LANG[1651],10 => $this->LANG[1651],11 => $this->LANG[3237],12  => $this->LANG[3237],13  => $this->LANG[3237] ,14 => $this->LANG[3237],15 => $this->LANG[3239],16 => $this->LANG[3239],17 => $this->LANG[90]);

            switch($user_group)
            {
                case Application::GROUP_SHOP_OWNER_WITH_SALES:
                    $statement = $GLOBALS['System']->DB->query("
                                    SELECT p.id pid, p.user_id puid, p.type pty,pr.model pmodel,p.description pdesc, pc.name pcname, su.reg_date pdate, p.points pts
                                    FROM  `points` p 
                                    INNER JOIN serials_users su on p.rel_id=su.serial_id
                                    INNER JOIN serials s on su.serial_id=s.id
                                    INNER JOIN products pr on s.product_id=pr.id
                                    INNER JOIN product_categories pc on pr.category_id=pc.id
                                    WHERE p.user_id =$user_id AND p.deleted = 0 AND su.deleted = 0
                                    UNION ALL
                                    SELECT g.id gid, g.user_id guid, g.quantity gqty,gf.name gfname,gf.description gdesc, gc.name gcname, g.date gdate, g.points gpts
                                    FROM redeem g
                                    INNER JOIN gifts gf on g.gift_id=gf.id inner join gift_categories gc on gf.category_id = gc.id
                                    WHERE g.user_id =$user_id AND g.deleted = 0
                                    UNION ALL
                                    SELECT p.id pid, p.user_id puid, p.type pty,op.name oname,op.description opdesc,p.rel_id prel_id, p.reg_date, p.points pts
                                    FROM  `points` p
                                    INNER JOIN other_points op on p.rel_id=op.id
                                    WHERE p.user_id =$user_id AND p.deleted = 0  order by pdate"
                                 );
                    break;
                default:
                    $statement = $GLOBALS['System']->DB->query("
                                    SELECT p.id pid, p.user_id puid, p.type pty,pr.model pmodel,p.description pdesc, pc.name pcname, su.reg_date pdate, p.points pts
                                    FROM  `points` p 
                                    INNER JOIN serials_users su on p.rel_id=su.serial_id
                                    INNER JOIN serials s on su.serial_id=s.id
                                    INNER JOIN products pr on s.product_id=pr.id
                                    INNER JOIN product_categories pc on pr.category_id=pc.id
                                    WHERE p.user_id =$user_id AND p.deleted = 0 AND su.deleted = 0
                                    UNION ALL
                                    SELECT g.id gid, g.user_id guid, g.quantity gqty,gf.name gfname,gf.description gdesc, gc.name gcname, g.date gdate, g.points gpts
                                    FROM redeem g 
                                    INNER JOIN gifts gf on g.gift_id=gf.id
                                    INNER JOIN gift_categories gc on gf.category_id = gc.id
                                    WHERE g.user_id =$user_id AND g.deleted = 0
                                    UNION ALL
                                    SELECT p.id pid, p.user_id puid, p.type pty,op.name oname,op.description opdesc,p.rel_id prel_id, p.reg_date, p.points pts
                                    FROM  `points` p
                                    INNER JOIN other_points op on p.rel_id=op.id
                                    WHERE p.user_id =$user_id AND p.deleted = 0  order by pdate"
                                 );

                    

                    /*$statement = $GLOBALS['System']->DB->query("
                                    SELECT p.id pid, p.user_id puid, p.type pty,pr.model pmodel,p.description pdesc, pc.name pcname, su.reg_date pdate, p.points pts
                                    FROM  `points` p INNER JOIN serials_users su on p.rel_id=su.serial_id inner join serials s on su.serial_id=s.id inner join products pr on s.product_id=pr.id inner join product_categories pc on pr.category_id=pc.id
                                    WHERE p.user_id =$user_id AND p.deleted = 0 AND su.user_id = $user_id AND su.deleted = 0
                                    UNION ALL SELECT g.id gid, g.user_id guid, g.quantity gqty,gf.name gfname,gf.description gdesc, gc.name gcname, g.date gdate, g.points gpts
                                    FROM redeem g INNER JOIN gifts gf on g.gift_id=gf.id inner join gift_categories gc on gf.category_id = gc.id
                                    WHERE g.user_id =$user_id AND g.deleted = 0 order by pdate"
                                 );*/
                    break;
            }

            if($statement)
            {
                $id=0;
                foreach($statement as $s)
                {
                    if((int)$s["pty"]!=0 || $s["pty"] == Application::POINT_TYPE_PENALTY)
                    {
                        $id++;
                        if(intval($s["pcname"]) > 0)
                            $s["pcname"] = $point_category[$s["pcname"]];
                        $this->view('BODY', 'statement_redeem_single', Array('pid' => $s["pid"],'id' => $id,'type'=>$s["pcname"],'name'=>$s["pmodel"],'date'=>$s["pdate"],'gpoints'=>$s["pts"],'desc'=>$s["pdesc"]));
                    }
                    else
                    {
                        $id++;
                        if(intval($s["pcname"]) > 0)
                            $s["pcname"] = $point_category[$s["pcname"]];
                        $this->view('BODY', 'statement_points_single', Array('pid' => $s["pid"],'id' => $id,'type'=>$s["pcname"],'name'=>$s["pmodel"],'date'=>$s["pdate"],'ppoints'=>$s["pts"],'desc'=>$s["pdesc"]));
                    }
                }
            }
            else
            {
                $this->view('BODY','error');
            }
        }

        function points_history_search()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $arrserials = array();
            $flag = false;

            $calendar = $GLOBALS['System']->SESSION->get('calendar');
            switch($calendar)
            {

                case ENGLISH_CALENDAR:
                    $from_date = mktime(0,0,0,intval(@$this->GET['fm']),intval(@$this->GET['fd']),intval(@$this->GET['fy']) );
                    $to_date = mktime(0,0,0,intval(@$this->GET['tm']),intval(@$this->GET['td']),intval(@$this->GET['ty']) );
                    break;

                case PERSIAN_CALENDAR:
                    $calendar = new Calendar();
                    list($en_from_year,$en_from_month,$en_from_date) = $calendar->jalali_to_gregorian(intval(@$this->GET['fy']), intval(@$this->GET['fm']), intval(@$this->GET['fd']));
                    list($en_to_year,$en_to_month,$en_to_date) = $calendar->jalali_to_gregorian(intval(@$this->GET['ty']), intval(@$this->GET['tm']), intval(@$this->GET['td']));
                    $from_date              = mktime(0,0,0,  $en_from_month, $en_from_date, $en_from_year );
                    $to_date                = mktime(0,0,0,  $en_to_month, $en_to_date, $en_to_year );
                    break;

                default:
                    $from_date = mktime(0,0,0,intval(@$this->GET['fm']),intval(@$this->GET['fd']),intval(@$this->GET['fy']) );
                    $to_date = mktime(0,0,0,intval(@$this->GET['tm']),intval(@$this->GET['td']),intval(@$this->GET['ty']) );

            }

            if($from_date < $to_date)
                $su = new Serials_user("user_id",$current_user->id,"and","reg_date",">=",$from_date,"and","reg_date","<=",$to_date);
            else
                $su = new Serials_user("user_id",$current_user->id);

            $pmodel = intval(@$this->GET['pmod']);
            $pcateg = intval(@$this->GET['pcat']);
            $pbrand = intval(@$this->GET['pbrand']);
            
            if($pcateg > 0 && $su->count)
            {
                $id=0;
                if($pmodel > 0)
                {
                    foreach ($su as $serials)
                    {
                        if($serials->serial->product->id == $pmodel)
                        {
                            $id++;
                            $this->view('BODY', 'points_history_single', Array('serials' => $serials,'id' => $id));
                            $flag = true;
                        }
                    }
                }
                else
                {
                    foreach ($su as $serials)
                    {
                        if($serials->serial->product->category->id == $pcateg)
                        {
                            $id++;
                            $this->view('BODY', 'points_history_single', Array('serials' => $serials,'id' => $id));
                            $flag= true;
                        }
                    }
                }
            }
            elseif($pbrand > 0 && $su->count)
            {
                $id=0;
                foreach ($su as $serials)
                {
                        if($serials->serial->product->brand_id == $pbrand)
                        {
                            $id++;
                            $this->view('BODY', 'points_history_single', Array('serials' => $serials,'id' => $id));
                            $flag = true;
                        }
                }
            }
            /*else
            {
                $this->view('BODY','error');
            }*/

            if(!$flag)
                $this->view('BODY','error');
        }

        function statement_search()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $user_id = $current_user->id;
            $calendar = $GLOBALS['System']->SESSION->get('calendar');
            $point_category = Array(1 => $this->LANG[1651],2 => $this->LANG[1651],3 => $this->LANG[1651],4 => $this->LANG[1651],5 => $this->LANG[1651],6 => $this->LANG[1651],7 => $this->LANG[1651],8 => $this->LANG[1651],9 => $this->LANG[1651],10 => $this->LANG[1651],11 => $this->LANG[3237],12  => $this->LANG[3237],13  => $this->LANG[3237] ,14 => $this->LANG[3237],15 => $this->LANG[3239],16 => $this->LANG[3239],17 => $this->LANG[90]);

            switch($calendar)
            {

                case ENGLISH_CALENDAR:
                    $from_date = mktime(0,0,0,intval(@$this->GET['fm']),intval(@$this->GET['fd']),intval(@$this->GET['fy']) );
                    $to_date = mktime(0,0,0,intval(@$this->GET['tm']),intval(@$this->GET['td']),intval(@$this->GET['ty']) );
                    break;

                case PERSIAN_CALENDAR:
                    $calendar = new Calendar();
                    list($en_from_year,$en_from_month,$en_from_date) = $calendar->jalali_to_gregorian(intval(@$this->GET['fy']), intval(@$this->GET['fm']), intval(@$this->GET['fd']));
                    list($en_to_year,$en_to_month,$en_to_date) = $calendar->jalali_to_gregorian(intval(@$this->GET['ty']), intval(@$this->GET['tm']), intval(@$this->GET['td']));
                    $from_date              = mktime(0,0,0,  $en_from_month, $en_from_date, $en_from_year );
                    $to_date                = mktime(0,0,0,  $en_to_month, $en_to_date, $en_to_year );
                    break;

                default:
                    $from_date = mktime(0,0,0,intval(@$this->GET['fm']),intval(@$this->GET['fd']),intval(@$this->GET['fy']) );
                    $to_date = mktime(0,0,0,intval(@$this->GET['tm']),intval(@$this->GET['td']),intval(@$this->GET['ty']) );

            }

            $searched_ptype = explode("|",@$this->GET['ptype']);
            $flag = FALSE;

            foreach($searched_ptype as $ptype)
            {
                if($ptype != NULL)
                {
                    switch(intval($ptype))
                    {
                        case 1:
                            if($from_date < $to_date)
                                $pu = new Point("user_id",$current_user->id,"and","type",  Application::POINT_TYPE_PRODUCT,"and","reg_date",">=",$from_date,"and","reg_date","<=",$to_date+TIME_FACTOR);
                            else
                                $pu = new Point("user_id",$current_user->id,"and","type",Application::POINT_TYPE_PRODUCT);
                            break;
                        case 2:
                            if($from_date < $to_date)
                                $pu = new Redeem("user_id",$current_user->id,"and","date",">=",$from_date,"and","date","<=",$to_date+TIME_FACTOR);
                            else
                                $pu = new Redeem("user_id",$current_user->id);
                            break;
                        case 3:
                            if($from_date < $to_date)
                                $pu = new Point("user_id",$current_user->id,"and","type",Application::POINT_TYPE_AVARDZ_SPORTS,"and","reg_date",">=",$from_date,"and","reg_date","<=",$to_date+TIME_FACTOR);
                            else
                                $pu = new Point("user_id",$current_user->id,"and","type",Application::POINT_TYPE_AVARDZ_SPORTS);
                            break;
                        case 4:
                            if($from_date < $to_date)
                                $pu = new Point("user_id",$current_user->id,"and","type",Application::POINT_TYPE_BONUS,"and","reg_date",">=",$from_date,"and","reg_date","<=",$to_date+TIME_FACTOR);
                            else
                                $pu = new Point("user_id",$current_user->id,"and","type",Application::POINT_TYPE_BONUS);
                            break;
                        case 5:
                            if($from_date < $to_date)
                                $pu = new Point("user_id",$current_user->id,"and","type",Application::POINT_TYPE_PENALTY,"and","reg_date",">=",$from_date,"and","reg_date","<=",$to_date+TIME_FACTOR);
                            else
                                $pu = new Point("user_id",$current_user->id,"and","type",Application::POINT_TYPE_PENALTY);
                            break;
                        case 6:
                            if($from_date < $to_date)
                                $pu = new Point("user_id",$current_user->id,"and","type",Application::POINT_TYPE_REFERAL,"and","reg_date",">=",$from_date,"and","reg_date","<=",$to_date+TIME_FACTOR);
                            else
                                $pu = new Point("user_id",$current_user->id,"and","type",Application::POINT_TYPE_REFERAL);
                            break;
                        case 7:
                            if($from_date < $to_date)
                                $pu = new Point("user_id",$current_user->id,"and","type",Application::POINT_TYPE_STORE_EVALUATION,"and","reg_date",">=",$from_date,"and","reg_date","<=",$to_date+TIME_FACTOR);
                            else
                                $pu = new Point("user_id",$current_user->id,"and","type",Application::POINT_TYPE_STORE_EVALUATION);
                            break;
                        case 0:
                            $to_date += TIME_FACTOR;
                            if($from_date < $to_date)
                            {
                                $statement = $GLOBALS['System']->DB->query("
                                    SELECT p.id pid, p.user_id puid, p.type pty,pr.model pmodel,p.description pdesc, pc.name pcname, su.reg_date pdate, p.points pts
                                    FROM  `points` p
                                    INNER JOIN serials_users su on p.rel_id=su.serial_id
                                    INNER JOIN serials s on su.serial_id=s.id
                                    INNER JOIN products pr on s.product_id=pr.id
                                    INNER JOIN product_categories pc on pr.category_id=pc.id
                                    WHERE p.user_id =$user_id AND p.deleted = 0 AND p.reg_date >= $from_date AND p.reg_date <= $to_date AND su.user_id = $user_id AND su.deleted = 0
                                    UNION ALL
                                    SELECT g.id gid, g.user_id guid, g.quantity gqty,gf.name gfname,gf.description gdesc, gc.name gcname, g.date gdate, g.points gpts
                                    FROM redeem g
                                    INNER JOIN gifts gf on g.gift_id=gf.id
                                    INNER JOIN gift_categories gc on gf.category_id = gc.id
                                    WHERE g.user_id =$user_id AND g.deleted = 0 AND g.date >= $from_date AND g.date <= $to_date
                                    UNION ALL
                                    SELECT p.id pid, p.user_id puid, p.type pty,op.name oname,op.description opdesc,p.rel_id prel_id, p.reg_date, p.points pts
                                    FROM  `points` p
                                    INNER JOIN other_points op on p.rel_id=op.id
                                    WHERE p.user_id =$user_id AND p.deleted = 0 AND p.reg_date >= $from_date AND p.reg_date <= $to_date order by pdate"
                                    );

                                if($statement)
                                {
                                    $id=0;
                                    foreach($statement as $s)
                                    {
                                        if((int)$s["pty"]!=0 || $s["pty"] == Application::POINT_TYPE_PENALTY)
                                        {
                                            $id++;
                                            if(intval($s["pcname"]) > 0)
                                                $s["pcname"] = $point_category[$s["pcname"]];
                                            $this->view('BODY', 'statement_redeem_single', Array('pid' => $s["pid"],'id' => $id,'type'=>$s["pcname"],'name'=>$s["pmodel"],'date'=>$s["pdate"],'gpoints'=>$s["pts"],'desc'=>$s["pdesc"]));
                                            $flag = TRUE;
                                        }
                                        else
                                        {
                                            $id++;
                                            if(intval($s["pcname"]) > 0)
                                                $s["pcname"] = $point_category[$s["pcname"]];
                                            $this->view('BODY', 'statement_points_single', Array('pid' => $s["pid"],'id' => $id,'type'=>$s["pcname"],'name'=>$s["pmodel"],'date'=>$s["pdate"],'ppoints'=>$s["pts"],'desc'=>$s["pdesc"]));
                                            $flag = TRUE;
                                        }
                                    }
                                }
                            }
                            break;
                    }
            

                    if($pu->count)
                    {
                        $id=0;
                        foreach ($pu as $points)
                        {
                            $id++;
                            $flag = TRUE;

                            if($ptype==2)
                                $this->view('BODY', 'statement_redeem_single', Array('id' => $id,'type'=>$points->gifts->name,'name'=>$points->gifts->model,'date'=>$points->date,'gpoints'=>$points->points,'desc'=>$points->gifts->description));
                            elseif($ptype==1)
                                $this->view('BODY', 'statement_points_single', Array('id' => $id,'type'=>$points->serials->product->category->name,'name'=>$points->serials->product->model,'date'=>$points->reg_date,'ppoints'=>$points->points,'desc'=>$points->serials->product->description));
                            elseif($ptype==5)
                                $this->view('BODY', 'statement_redeem_single', Array('id' => $id,'type'=>$point_category[$points->other_points->id],'name'=>$points->other_points->name,'date'=>$points->reg_date,'gpoints'=>$points->points,'desc'=>$points->other_points->description));
                            else
                                $this->view('BODY', 'statement_points_single', Array('id' => $id,'type'=>$point_category[$points->other_points->id],'name'=>$points->other_points->name,'date'=>$points->reg_date,'ppoints'=>$points->points,'desc'=>$points->other_points->description));
                        }
                    }
                }
            }

            if(!$flag)
                $this->view('BODY','error');
        }

        function redemption_history()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $ru = new Redeem("user_id",$current_user->id);

            if ($ru->count)
            {
                $id=0;
                foreach ($ru as $redemption)
                {
                    $id++;
                    $status = Array(0 => $this->LANG[2781],1 => $this->LANG[150],2 => $this->LANG[2779],3 => $this->LANG[2755]);
                    $this->view('BODY', 'redemption_history_single', Array('redemption' => $redemption,'id' => $id,'status'=>$status[$redemption->status]));
                }
            }
            else
            {
                $this->view('BODY','error');
            }

        }

        function redemption_history_search()
        {
           //sleep(3);
           Application::deal_unauthorized();
           $current_user = Application::confirm_user();

           $calendar = $GLOBALS['System']->SESSION->get('calendar');
            switch($calendar)
            {

                case ENGLISH_CALENDAR:
                    $from_date = mktime(0,0,0,intval(@$this->GET['fm']),intval(@$this->GET['fd']),intval(@$this->GET['fy']) );
                    $to_date = mktime(0,0,0,intval(@$this->GET['tm']),intval(@$this->GET['td']),intval(@$this->GET['ty']) );
                    break;

                case PERSIAN_CALENDAR:
                    $calendar = new Calendar();
                    list($en_from_year,$en_from_month,$en_from_date) = $calendar->jalali_to_gregorian(intval(@$this->GET['fy']), intval(@$this->GET['fm']), intval(@$this->GET['fd']));
                    list($en_to_year,$en_to_month,$en_to_date) = $calendar->jalali_to_gregorian(intval(@$this->GET['ty']), intval(@$this->GET['tm']), intval(@$this->GET['td']));
                    $from_date              = mktime(0,0,0,  $en_from_month, $en_from_date, $en_from_year );
                    $to_date                = mktime(0,0,0,  $en_to_month, $en_to_date, $en_to_year );
                    break;

                default:
                    $from_date = mktime(0,0,0,intval(@$this->GET['fm']),intval(@$this->GET['fd']),intval(@$this->GET['fy']) );
                    $to_date = mktime(0,0,0,intval(@$this->GET['tm']),intval(@$this->GET['td']),intval(@$this->GET['ty']) );

            }

           if($from_date <= $to_date)
               $ru = new Redeem("user_id",$current_user->id,"and","date",">=",$from_date,"and","date","<=",$to_date);
           else
               $ru = new Redeem("user_id",$current_user->id);
           if($ru->count)
           {
               $id=0;
               foreach ($ru as $redemption)
               {
                    $id++;
                    $status = Array(0 => $this->LANG[2781],1 => $this->LANG[150],2 => $this->LANG[2779],3 => $this->LANG[2755]);
                    $this->view('BODY', 'redemption_history_single', Array('redemption' => $redemption,'id' => $id,'status'=>$status[$redemption->status]));
               }
           }
           else
           {
                $this->view('BODY','error');
           }
        }

        function get_referer_details()
        {
            $id = intval(@$this->GET['id']);

            $c = new User('id', "$id");

            if($c->count)
                $this->view('BODY', 'registration_referer_details', Array('referer' => $c));
        }

        function get_states()
        {
            $id = @$this->GET['id'];
            $default = intval(@$this->GET['sel'])==0 ? 0 : intval(@$this->GET['sel']);
            
            $name = Security::secure_input(@$this->GET['name']);

            $states = Application::states_combo("$id", $name, $default);
            $this->view_str('BODY', $states);
        }

        function get_cities()
        {
            $id = intval(@$this->GET['id']);
            $default = intval(@$this->GET['sel'])==0 ? 0 : intval(@$this->GET['sel']);

            $name = Security::secure_input(@$this->GET['name']);

            $cities = Application::cities_combo("$id", $name, $default);
            $this->view_str('BODY', $cities);
        }

        function get_product_models()
        {
            $id = intval(@$this->GET['id']);
            $name = @$this->GET['name'];
            $u = Application::confirm_user();
            $grp = $u->group_id;

            if($name!='')
                $product_models = Application::product_models_query_combo($grp, $name, '0');
            else
                $product_models = Application::product_models_combo($id, $grp, "product_model", '0');

            $this->view_str('BODY', $product_models);
        }

        function get_elibrary_product_models()
        {
            $id = @$this->GET['id'];
            $u = Application::confirm_user();

            $product_models = Application::elibrary_product_models_combo($id, "elibrary_product_model", '0');

            $this->view_str('BODY', $product_models);
        }

        function get_elibrary_chipset_maker()
        {
            $id = intval(@$this->GET['id']);
            $u = Application::confirm_user();

            $product_models = Application::elibrary_chipset_maker_combo($id, "elibrary_chipset_maker", '0');

            $this->view_str('BODY', $product_models);
        }

        function get_elibrary_chipset()
        {
            $id =@$this->GET['id'];
            $u = Application::confirm_user();

            $product_models = Application::elibrary_chipset_combo($id, "elibrary_chipset", '0');

            $this->view_str('BODY', $product_models);
        }

        function elibrary()
        {
            $u = Application::confirm_user();
            $argument = @$this->GET['args'];
            $arg = explode(",", $argument);

            $prod_model = stripslashes($arg[0]);
            $chipset = stripslashes($arg[1]);
            $chipset_maker = stripslashes($arg[2]);
            $product_type = intval($arg[3]);
            $product_brand = intval($arg[4]);

            if($prod_model)
                $elibrary = new Elibrary("model",$prod_model);
            elseif($chipset)
                $elibrary = new Elibrary("chpset",$chipset);
            elseif($chipset_maker)
                $elibrary = new Elibrary("chp_maker",$chipset_maker);
            elseif($product_type)
                $elibrary = new Elibrary("category_id",$product_type);
            elseif($product_brand)
                $elibrary = new Elibrary("brand_id",$product_brand);

            $id = 0;
            if($elibrary->count)
            foreach($elibrary as $e)
            {
                $id++;
                $this->view('BODY','elibrary_single',Array('elib'=>$elibrary,'id'=>$id));
            }
        }

        function get_product_list()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $pu = new Products_group("group_id",$current_user->group_id);
            $today = time();
            if ($pu->count && ($pu->product->date_available_upto > $today))
            {
                $id=0;
                foreach ($pu as $product)
                {
                    $id++;
                    $points = $pu->product->points;
                    $ppercent = $pu->group->point_percent;
                    switch($current_user->group_id)
                    {
                        case Application::GROUP_AVAJANG2:
                            $enduserpoint = $points * ($ppercent/100);
                            $resellerpoint = $points * (15/100);
                            $actpoint = $enduserpoint.$this->LANG[2335].$resellerpoint;
                            break;
                        default:
                            $actpoint = $points * ($ppercent/100);
                    }
                    $this->view('BODY', 'product_list_single', Array('product' => $product,'id' => $id, 'points' => $actpoint));
                }
            }
            else
            {
                $this->view('BODY','error');
            }
        }

        function product_list_search()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();

            $pu = new Products_group("group_id",$current_user->group_id);

            $pbrand = intval(@$this->GET['pbrand']);
            $ptype = intval(@$this->GET['ptype']);
            $flag=0;
            $today = time();

            if($pbrand && $ptype)
            {
                $id=0;
                foreach($pu as $p)
                {
                    if($p->product->category_id == $ptype && $p->product->brand_id == $pbrand)
                    {
                        if($pu->product->date_available_upto > $today)
                        {
                            $id++;
                            $points = $p->product->points;
                            $ppercent = $p->group->point_percent;
                            switch($current_user->group_id)
                            {
                                case Application::GROUP_AVAJANG2:
                                    $enduserpoint = $points * ($ppercent/100);
                                    $resellerpoint = $points * (15/100);
                                    $actpoint = $enduserpoint.$this->LANG[2335].$resellerpoint;
                                    break;
                                default:
                                    $actpoint = $points * ($ppercent/100);
                            }
                            $this->view('BODY', 'product_list_single', Array('product' => $p,'id' => $id, 'points' => $actpoint));
                            $flag=1;
                        }
                    }

                }
            }

            elseif($pbrand)
            {
                $id=0;
                foreach($pu as $p)
                {
                    if($p->product->brand_id == $pbrand)
                    {
                        if($pu->product->date_available_upto > $today)
                        {
                            $id++;
                            $points = $p->product->points;
                            $ppercent = $p->group->point_percent;
                            $actpoint = $points * ($ppercent/100);
                            $this->view('BODY', 'product_list_single', Array('product' => $p,'id' => $id, 'points' => $actpoint));
                            $flag=1;
                        }
                    }

                }
            }

            elseif($pbrand || $ptype)
            {
                $id=0;
                foreach($pu as $p)
                {
                    if($p->product->category_id == $ptype)
                    {
                        if($pu->product->date_available_upto > $today)
                        {
                            $id++;
                            $points = $p->product->points;
                            $ppercent = $p->group->point_percent;
                            $actpoint = $points * ($ppercent/100);
                            $this->view('BODY', 'product_list_single', Array('product' => $p,'id' => $id, 'points' => $actpoint));
                            $flag=1;
                        }
                    }

                }
            }
  
            if($flag==0)
            {
                $this->view('BODY','error');
            }
        }

        function get_gift_list()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $gu = new Gifts_group("group_id",$current_user->group_id);
            
            if ($gu->count)
            {
                $id=0;
                foreach ($gu as $gift)
                {
                    $id++;
                    $this->view('BODY', 'gift_list_single', Array('gift' => $gift,'id' => $id));
                }
            }
            else
            {
                $this->view('BODY','error');
            }
        }

        function get_companies()
        {
            $id = intval(@$this->GET['id']);
            $pr_id = intval(@$this->GET['pr']);

            $companies = Application::companies_combo("company_id", $id, 0, $pr_id);
            
            $this->view_str('BODY', $companies);
        }

        function inbox()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $iu = new Inbox("user_id",$current_user->id,"and","rel_id","!=",Application::TRASHED_MESSAGE);
            $image='';
            
            $id = @intval($this->GET["id"]);

            switch($id)
            {
                case 1:
                    $iu = new Inbox("user_id",$current_user->id,"and","rel_id",Application::PRIVATE_MESSAGE);
                    break;
                case 2:
                    $iu = new Inbox("user_id",$current_user->id,"and","rel_id",Application::GENERAL_MESSAGE);
                    break;
                case 3:
                    $iu = new Inbox("user_id",$current_user->id,"and","rel_id",Application::TRASHED_MESSAGE);
                    break;
            }
            
            if ($iu->count)
            {
                $id=0;
                foreach ($iu as $inbox)
                {
                    $id++;
                    if($inbox->rel_id == Application::PRIVATE_MESSAGE)
                        $image = "privatemessage";
                    elseif($inbox->rel_id == Application::GENERAL_MESSAGE)
                        $image = "genralmessage";
                    elseif($inbox->rel_id == Application::TRASHED_MESSAGE)
                        $image = "delmessage";
                    
                    $msgid = base64_encode(base64_encode($inbox->id).'|'.md5($inbox->id.$inbox->date));

                    $message = $inbox->subject;
                    
                    if($inbox->rel_id == Application::PRIVATE_MESSAGE || $inbox->rel_id == Application::GENERAL_MESSAGE)
                    {
                        $class = $inbox->status ? "link01inbox":"link01";
                        $this->view('BODY', 'inbox_single', Array('inbox' => $inbox,'id' => $msgid, 'image' => $image, 'refid' => $id, 'class' => $class, 'message' => $message));
                    }
                    elseif($inbox->rel_id == Application::TRASHED_MESSAGE)
                        $this->view('BODY', 'inbox_single_trash', Array('inbox' => $inbox,'id' => $msgid, 'image' => $image, 'refid' => $id, 'message' => $message));
                }
            }
            else
            {
                $this->view('BODY','error');
            }
        }

        function faq_ajax()
        {
            $id = @intval($this->GET["id"]);
            $f = new Faq("id",">",0);
            $LANG = $GLOBALS['System']->COOKIE->get('lang');
            $id = 0;

            foreach ($f as $faq)
            {
                $id++;
                if($LANG=="en" || $LANG==NULL)
                    $this->view('BODY', 'query_single', Array('question' => $faq->question,'answer' => $faq->answer , 'id'=> $id));
                else
                    $this->view('BODY', 'query_single', Array('question' => $faq->question_tr,'answer' => $faq->answer_tr , 'id'=> $id));
            }

        }

        function faq_search()
        {
            $cat_id = @intval($this->GET["cat"]);
            $faq_text = @$this->GET["text"];
            $flag = false;
            
            if($cat_id > 0)
                $f = new Faq("id",">",0,"and", "cat_id",$cat_id);
            else
                $f = new Faq("id",">",0);

            $LANG = $GLOBALS['System']->COOKIE->get('lang');
            $id = 0;

            foreach ($f as $faq)
            {
                if($LANG=="en" || $LANG==NULL)
                {
                    if($faq_text != '')
                    {
                        $pos = stripos($faq->question,$faq_text);
                        if($pos)
                        {
                            $id++;
                            $this->view('BODY', 'query_single', Array('question' => $faq->question,'answer' => $faq->answer , 'id'=> $id));
                            $flag = true;
                        }
                    }
                    else
                    {
                        $id++;
                        $this->view('BODY', 'query_single', Array('question' => $faq->question,'answer' => $faq->answer , 'id'=> $id));
                        $flag = true;
                    }
                }
                else
                {
                    if($faq_text != '')
                    {
                        $pos = strpos($faq->question_tr,$faq_text);
                        if($pos)
                        {
                            $id++;
                            $this->view('BODY', 'query_single', Array('question' => $faq->question_tr,'answer' => $faq->answer_tr , 'id'=> $id));
                            $flag = true;
                        }
                    }
                    else
                    {
                        $id++;
                        $this->view('BODY', 'query_single', Array('question' => $faq->question_tr,'answer' => $faq->answer_tr , 'id'=> $id));
                        $flag = true;
                    }
                }
            }

            if(!$flag)
                $this->view('BODY','error');

        }

        function tickets()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $tu = new Ticket("user_id",$current_user->id,"and","followup_id","0");

            if ($tu->count)
            {
                $id=0;
                foreach ($tu as $ticket)
                {
                    $id++;
                    $msgid = base64_encode(base64_encode($ticket->id).'|'.md5($ticket->id.$ticket->message));
                    if($ticket->subject == Application::TICKET_PRODUCT_REGISTRATION)
                        $message = $this->LANG[$ticket->subject];
                    else
                        $message = substr($ticket->message,0,50);
                    switch($ticket->status)
                    {
                        case 'P':
                            $this->view('BODY', 'ticket_new', Array('slno' => $id,'message' => $message,'id' => $ticket->id,'msgid'=>$msgid,'ticket'=>$ticket));
                            break;
                        case 'O':
                            $reply = new Ticket("followup_id",$ticket->id);
                            $shortmsg = substr($reply->message,0,38);
                            $this->view('BODY', 'ticket_open', Array('slno' => $id,'message' => $message,'reply' => $shortmsg,'id' => $ticket->id,'msgid'=>$msgid,'ticket'=>$ticket));
                            break;
                        case 'C':
                            $this->view('BODY', 'ticket_closed', Array('slno' => $id,'message' => $message,'id' => $ticket->id,'msgid'=>$msgid,'ticket'=>$ticket));
                            break;
                    }
                }
            }
            else
            {
                $this->view('BODY','error_ticket',Array('message' => $this->LANG[3231]));
            }
        }

        function ticket_detail()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $tu = new Ticket("user_id",$current_user->id);
            $flag=true;

            $data = @base64_decode($this->GET["id"]);
            if (!$data) $flag=false;

            @list($id, $message) = explode('|', $data);

            $id=base64_decode($id);

            if (trim($message) == '') $flag=false;
            if (intval($id) == 0) $flag=false;

            if($flag)
            {
                $ticket = new Ticket("id",intval($id),"and","user_id",$current_user->id);
                if ($ticket->count)
                {
                    foreach ($ticket as $t)
                    {
                        if($t->followup_id==0 && $t->admin_id==0)
                        {
                            $tfollow = new Ticket("followup_id",$t->id);
                            $img = $tfollow->count ? "ticketans" : "ticketon";
                            $subject = $this->LANG[$t->subject];
                            $this->view('BODY', 'ticket_head', Array('message' => $t->message,'id' => $t->id,'msgid'=>$this->GET["id"],'ticket'=>$t,'img'=>$img,'subject' => $subject));
                        }
                        $fu = new Ticket("followup_id",$t->id);
                        if($fu->count)
                        foreach ($fu as $f)
                        {
                            if($f->admin_id == 0)
                                $this->view('BODY', 'ticket_body_user', Array('message' => $f->message,'id' => $f->id,'ticket'=>$f));
                            else
                                $this->view('BODY', 'ticket_body_admin', Array('message' => $f->message,'id' => $f->id,'ticket'=>$f));
                        }
                    }
                }
                else
                    $this->view("BODY","message",Array("message"=>"You are not Authorized"));
            }
            else
                System::redirect_to_controller("userticket");
        }

        function delete_serial()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $su = new Serials_user("user_id",$current_user->id);
            
            if ($su->count)
            {
                $id=0;
                foreach ($su as $serials)
                {
                    $id++;
                    $serialid = base64_encode(base64_encode($serials->serial_id).'|'.md5($serials->id.$serials->regdate));
                    $this->view('BODY', 'delete_serial_single', Array('serials' => $serials,'id' => $id,'serialid'=>$serialid));
                }
            }
            else
            {
                $this->view('BODY','error');
            }

        }

        function delete_serial_search()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $arrserials = array();

            $calendar = $GLOBALS['System']->SESSION->get('calendar');
            switch($calendar)
            {

                case ENGLISH_CALENDAR:
                    $from_date = mktime(0,0,0,intval(@$this->GET['fm']),intval(@$this->GET['fd']),intval(@$this->GET['fy']) );
                    $to_date = mktime(0,0,0,intval(@$this->GET['tm']),intval(@$this->GET['td']),intval(@$this->GET['ty']) );
                    break;

                case PERSIAN_CALENDAR:
                    $calendar = new Calendar();
                    list($en_from_year,$en_from_month,$en_from_date) = $calendar->jalali_to_gregorian(intval(@$this->GET['fy']), intval(@$this->GET['fm']), intval(@$this->GET['fd']));
                    list($en_to_year,$en_to_month,$en_to_date) = $calendar->jalali_to_gregorian(intval(@$this->GET['ty']), intval(@$this->GET['tm']), intval(@$this->GET['td']));
                    $from_date              = mktime(0,0,0,  $en_from_month, $en_from_date, $en_from_year );
                    $to_date                = mktime(0,0,0,  $en_to_month, $en_to_date, $en_to_year );
                    break;

                default:
                    $from_date = mktime(0,0,0,intval(@$this->GET['fm']),intval(@$this->GET['fd']),intval(@$this->GET['fy']) );
                    $to_date = mktime(0,0,0,intval(@$this->GET['tm']),intval(@$this->GET['td']),intval(@$this->GET['ty']) );

            }

            if($from_date < $to_date)
                $su = new Serials_user("user_id",$current_user->id,"and","reg_date",">=",$from_date,"and","reg_date","<=",$to_date);
            else
                $su = new Serials_user("user_id",$current_user->id);

            $pmodel = intval(@$this->GET['pmod']);
            $pcateg = intval(@$this->GET['pcat']);

            if($pcateg > 0 && $su->count)
            {
                $id=0;
                if($pmodel > 0)
                {
                    foreach ($su as $serials)
                    {
                        if ($serials->serial->product->id == $pmodel)
                        {
                            $id++;
                            $serialid = base64_encode(base64_encode($serials->serial_id).'|'.md5($serials->id.$serials->regdate));
                            $this->view('BODY', 'delete_serial_single', Array('serials' => $serials,'id' => $id,'serialid'=>$serialid));
                        }
                    }
                }
                else
                {
                    foreach ($su as $serials)
                    {
                        if($serials->serial->product->category->id == $pcateg)
                        {
                            $id++;
                            $serialid = base64_encode(base64_encode($serials->serial_id).'|'.md5($serials->id.$serials->regdate));
                            $this->view('BODY', 'delete_serial_single', Array('serials' => $serials,'id' => $id,'serialid'=>$serialid));
                        }
                    }
                }
            }
            else
            {
                $this->view('BODY','error');
            }
        }

        function delete_serial_confirmed()
        {
            //sleep(3);
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();

            $serial_id = @$this->GET['id'];
            $flag=true;

            $data = @base64_decode($serial_id);
            if (!$data) $flag=false;

            @list($id, $code) = explode('|', $data);

            $id=base64_decode($id);

            if (trim($code) == '') $flag=false;
            if (intval($id) == 0) $flag=false;

            if($flag)
            {
                $u = new user("id",$current_user->id);
                $serial = new Serials_user("serial_id",$id,"and","user_id",$current_user->id);
                $points = new Point("rel_id",$serial->serial_id,"and","user_id",$current_user->id);
                $duplicates = new Duplicate("serial_id",$id,"and","serial_registered_by",$current_user->id);

                $pr_points = $points->points;
                $avail_points = $u->total_points;

                $total_points = intval(@$this->GET['pts']);
                $total_deleted = intval(@$this->GET['count']);

                if($avail_points > $pr_points)
                {
                    $rem_points = $avail_points - $pr_points;
                    $serial -> deleted = 1;
                    $points -> deleted = 1;
                    $u -> total_points = $rem_points;
                    $serial->save();
                    $points->save();
                    $u->save();

                    if($duplicates->count)
                    {
                        foreach($duplicates as $d)
                        {
                            $main_dup = new User("id",$d->serial_registered_by); // decrease no.of duplicate from the main
                            $main_dup->dup_main -= 1;
                            $main_dup->save();

                            $sub_dup = new User("id",$d->serial_tried_by); // decrease no.of duplicate from the sub
                            $sub_dup->dup_sub -= 1;
                            $sub_dup->save();

                            $d->deleted = 1; //delete each duplicate from the duplicates
                            $d->save();
                        }
                    }

                    $this->view('BODY', 'deleted_summary', Array('total' =>$avail_points,'prpoints' =>$total_points,'rem'=>$rem_points,'deleted' => $total_deleted));
                }
                else
                {
                    $this->view('BODY', 'message_1', Array('message' => "you don have enough points"));
                }
            }


        }

        function get_evaluation_list()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $el = new Store_evaluation("id",">=",1);
            $LANG = $GLOBALS['System']->COOKIE->get('lang');

            if ($el->count)
            {
                foreach ($el as $list)
                if($LANG=="en" || $LANG==NULL)
                {
                    $category = $el->category_en;
                    $description = $el->description_en;
                    $points = $el->points;
                    $this->view('BODY', 'store_evaluation_single', Array('category' => $category , 'desc'=> $description , 'points'=>$points));
                }
                else
                {
                    $category = $el->category_pr;
                    $description = $el->description_pr;
                    $points = $el->points;
                    $this->view('BODY', 'store_evaluation_single', Array('category' => $category , 'desc'=> $description , 'points'=>$points));
                }
            }
            else
            {
                $this->view('BODY','error');
            }
        }

        function get_evaluation_summary()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $user_id = $current_user->id;

            $statement = $GLOBALS['System']->DB->query("
                            SELECT sum( `points` ) total , month( FROM_UNIXTIME( `reg_date` ) ) AS month , year( FROM_UNIXTIME( `reg_date` ) ) AS year, reg_date AS `date`
                            FROM `points`
                            WHERE `user_id` =$user_id
                            AND `type` = 'S'
                            GROUP BY year( FROM_UNIXTIME( `reg_date` ) ) , month( FROM_UNIXTIME( `reg_date` ) )"
                         );

            $id=0;
            if($statement)
            {
                foreach($statement as $elp)
                {
                    $id++;
                    $date = base64_encode($elp["date"]);
                    $this->view('BODY','store_evaluation_points_single',Array('edate'=>$elp["date"],'total'=>$elp["total"],"id"=>$id, "date"=>$date));
                }
            }
           else
            {
                $this->view('BODY','error');
            }
        }

        function get_evaluation_details()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $user_id = $current_user->id;
            $date = @$this->GET['id'];

            @list($id,$date)=explode("_",$date);
            $date = base64_decode($date);
            
            $statement = $GLOBALS['System']->DB->query("
                            SELECT p.points AS ob, o.category_en cen, o.category_pr cpr, o.description_en AS desct_en , o.description_pr AS desct_pr, o.points AS total, c.comment AS comt
                            FROM `points` p
                            INNER JOIN store_evaluation o ON p.`rel_id` = o.`id`
                            INNER JOIN events e ON e.`rel_id` = p.`id`
                            INNER JOIN comments c ON c.`event_id` = e.`id`
                            WHERE p.`user_id` =$user_id
                            AND p.`type` = 'S' AND e.`type` = 2
                            AND MONTH( FROM_UNIXTIME( p.`reg_date` ) ) = MONTH( FROM_UNIXTIME( $date ) )
                         ");

            $id=0;

            $LANG = $GLOBALS['System']->COOKIE->get('lang');
            foreach($statement as $elp)
            {
                if($LANG=="en" || $LANG==NULL)
                    $this->view('BODY','store_evaluation_detail_single',Array('obtained'=>$elp["ob"], 'desc'=>$elp["desct_en"], 'category'=>$elp["cen"], 'total'=>$elp["total"], 'comment'=>$elp["comt"]));
                else
                    $this->view('BODY','store_evaluation_detail_single',Array('obtained'=>$elp["ob"], 'desc'=>$elp["desct_pr"], 'category'=>$elp["cpr"], 'total'=>$elp["total"], 'comment'=>$elp["comt"]));
            }

        }

        function get_evaluation_date()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $user_id = $current_user->id;
            $date = @$this->GET['id'];
            $time = @intval($this->GET['time']);

            @list($id,$date)=explode("_",$date);
            $date = base64_decode($date);

            $this->view('BODY','store_evaluation_date',Array('date'=>$date,'time'=>$time));
            
        }
        function get_evaluation_total()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();
            $user_id = $current_user->id;
            $date = @$this->GET['id'];

            $this->view_str('BODY',Application::get_current_user_store_evaluation_points($date));


        }

        function get_city_code()
        {
            $city_id = intval(@$this->GET['id']);
            if($city_id > 0)
            {
                $city = new City("id",$city_id);
                $this->view_str('BODY', $city->code);
            }
            else
                $this->view_str('BODY', "");
        }

        /*
         * widgets
         *
         */

        public static function get_widget_database()
        {
            $current_user = Application::confirm_user();
            $value = $GLOBALS['System']->GET['value'];

            if($value)
            {
                $widget = new Widget("user_id",$current_user->id);
                if($widget->count)
                {
                    $widget->user_id = $current_user->id;
                    $widget->configuration = $value;
                    $widget->save();
                }
                else
                {
                    $widget->user_id = $current_user->id;
                    $widget->configuration = $value;
                    $widget->save();
                }
            }
            elseif($value==0)
            {
               $c = new Widget("user_id",$current_user->id);
               echo $c->configuration;
            }
            else
            {
                echo "";
            }
        }

        function get_widgets()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();

            $widget_id = @$this->GET["widget_id"];
            $widget_id = str_replace("widget","",$widget_id);
            $wi_id = intval($widget_id);
            $pr_cat[1] = $gf_cat[1]= $gf_cat[2]= $pr_model[1] = $pr_points[1] = $pr_date[1] = $gf_name[1] = $gf_points[1] = $gf_date[1] = $gf_name[2] = $gf_points[2] = $gf_date[2] = "-";
            
            switch($wi_id)
            {
                case 1:
                    $this->view('BODY', 'wid_avardz_sports');
                    break;
                case 2:
                    $user_id = $current_user->id;
                    $statement = $GLOBALS['System']->DB->query("
                                    SELECT p.id pid, p.user_id puid, p.type pty,pr.model pmodel,p.description pdesc, pc.name pcname, su.reg_date pdate, p.points pts
                                    FROM  `points` p INNER JOIN serials_users su on p.rel_id=su.serial_id inner join serials s on su.serial_id=s.id inner join products pr on s.product_id=pr.id inner join product_categories pc on pr.category_id=pc.id
                                    WHERE p.user_id =$user_id AND p.deleted = 0
                                    UNION ALL SELECT g.id gid, g.user_id guid, g.quantity gqty,gf.name gfname,gf.description gdesc, gc.name gcname, g.date gdate, g.points gpts
                                    FROM redeem g INNER JOIN gifts gf on g.gift_id=gf.id inner join gift_categories gc on gf.category_id = gc.id
                                    WHERE g.user_id =$user_id AND g.deleted = 0 order by pdate"
                                 );

                    $count = 0;
                    if($statement)
                    {
                        foreach($statement as $s)
                            $count++;
                    }
                    if($count>=3)
                    {
                        if($statement)
                        {
                            $pr_id=0;
                            $gf_id=0;
                            foreach($statement as $s)
                            {
                                if((int)$s["pty"]!=0)
                                {
                                    $gf_id++;
                                    $gf_name[$gf_id] = $s["pmodel"];
                                    $gf_points[$gf_id] = $s["pts"];
                                    $gf_cat[$gf_id] = $s["pcname"];
                                    $gf_date[$gf_id] = Application::format_date_sec($s["pdate"]);
                                }
                                else
                                {
                                    $pr_id++;
                                    $pr_model[$pr_id] = $s["pmodel"];
                                    $pr_points[$pr_id] = $s["pts"];
                                    $pr_cat[$pr_id] = $s["pcname"];
                                    $pr_date[$pr_id] = Application::format_date_sec($s["pdate"]);
                                }
                            }
                        }
                    }
                    else
                    {
                        $pr_cat[1] = $gf_cat[1]= $gf_cat[2]= $pr_model[1] = $pr_points[1] = $pr_date[1] = $gf_name[1] = $gf_points[1] = $gf_date[1] = $gf_name[2] = $gf_points[2] = $gf_date[2] = "-";
                    }
                    $this->view('BODY','wid_statement',Array('prmodel'=>$pr_model,'prpoints'=>$pr_points,'gfname'=>$gf_name,'gfpoints'=>$gf_points,'prdate'=>$pr_date,'gfdate'=>$gf_date,'pcat'=>$pr_cat,'gfcat'=>$gf_cat));
                    break;
                case 3:
                    $ui = new Inbox("user_id",$current_user->id);
                    $count = $ui->count;

                    $id = 0;
                    if($count > 4)
                    {
                        foreach($ui as $u)
                        {
                            $id++;
                            $message[$id] = $u->subject;
                        }

                        $message[1] = substr($message[$count],0,50);
                        $message[2] = substr($message[$count-1],0,50);
                        $message[3] = substr($message[$count-2],0,50);
                        $message[4] = substr($message[$count-3],0,50);
                    }
                    else
                    {
                        $message[1] = $message[2] = $message[3] = $message[4] = "-";
                    }

                    $unread_count = Application::get_inbox_count($current_user->id);
                    $unread_count ? $display_uc = "display:inline;" : $display_uc = "display:none;";

                    $unread_private_count = Application::get_inbox_count($current_user->id,'private');
                    $unread_private_count ? $display_pc = "display:inline;" : $display_pc = "display:none;";

                    $unread_general_count = Application::get_inbox_count($current_user->id,'general');
                    $unread_general_count ? $display_gc = "display:inline;" : $display_gc = "display:none;";

                    $this->view('BODY', 'wid_inbox',Array('uc'=>$unread_count,'pc'=>$unread_private_count,'gc'=>$unread_general_count,'display_uc'=>$display_uc,'display_pc'=>$display_pc,'display_gc'=>$display_gc,'message'=>$message));
                    break;
                case 4:
                    $groups_products = new Products_group("group_id",$current_user->group_id);
                    $id=0;
                    foreach($groups_products as $gp)
                    {
                        $id++;
                        if($groups_products->count < 3)
                        {
                            if($id<=3)
                            {
                                $ptype[$id] = "-";
                                $pname[$id] = "-";
                                $edate[$id] = "-";
                                $points[$id] = "-";
                            }
                        }
                        else
                        {
                            if($id<=3)
                            {
                                $ptype[$id] = $gp->product->category->name;
                                $pname[$id] = $gp->product->model;
                                $edate[$id] = Application::format_date_sec($gp->product->date_available_upto);
                                $points[$id] = $gp->product->points * ($gp->group->point_percent/100);
                            }
                        }
                    }
                    $this->view('BODY', 'wid_eligible_products',Array('ptype'=>$ptype,'pname'=>$pname,'edate'=>$edate,'points'=>$points));
                    break;
                case 5:
                    $statement = new Serials_user("user_id",$current_user->id);
                    $id=0;
                    if($statement->count < 3)
                    {
                        for($i=1;$i<=3;$i++)
                        {
                            $ptype[$i] = "-";
                            $pname[$i] = "-";
                            $rdate[$i] = "-";
                            $points[$i] = "-";
                        }
                    }
                    else
                    {
                        foreach($statement as $p)
                        {
                            $id++;
                            if($id<=3)
                            {
                                $ptype[$id] = $p->serial->product->category->name;
                                $pname[$id] = $p->serial->product->model;
                                $rdate[$id] = Application::format_date_sec($p->reg_date);
                                $points[$id] = $p->points->points;
                            }
                        }
                    }
                    $this->view('BODY', 'wid_product_history',Array('ptype'=>$ptype,'pname'=>$pname,'rdate'=>$rdate,'points'=>$points));
                    break;
                case 6:
                    $this->view('BODY', 'wid_promotions');
                    break;
                case 7:
                    $this->view('BODY', 'wid_calendar');
                    break;
                case 8:
                    $this->view('BODY', 'wid_quiz');
                    break;
                case 9:
                    $this->view('BODY', 'wid_elibrary');
                    break;
                case 10:
                    $tickets = new Ticket("user_id",$current_user->id,"and","status","!=","C","and","followup_id","0","and","subject","!=",Application::TICKET_PRODUCT_REGISTRATION);
                    $count = $tickets->count;
                    $id = 0;
                    if($count > 3)
                    {
                        $status = Array("O" => $this->LANG[2757], "P" => $this->LANG[2755]);
                        foreach($tickets as $t)
                        {
                            $id++;
                            $query[$id] = $t->message;
                            $date[$id] = Application::format_date_sec($t->date);
                            $t_status[$id] = $status[$t->status];
                        }

                        $query[1] = substr($query[$count],0,30); $date[1] = $date[$count]; $t_status[1] = $t_status[$count];
                        $query[2] = substr($query[$count-1],0,30); $date[2] = $date[$count-1]; $t_status[2] = $t_status[$count-1];
                        $query[3] = substr($query[$count-2],0,30); $date[3] = $date[$count-2]; $t_status[3] = $t_status[$count-2];
                    }
                    else
                    {
                        $query[1] = $date[1] = $t_status[1] = $query[2] = $date[2] =  $t_status[2] = $query[3] = $date[3] = $t_status[3] = "-";
                    }
                    $this->view('BODY', 'wid_query',Array('query'=>$query,'date'=>$date,'status'=>$t_status));
                    break;
                case 11:
                    $rand = rand(1,50);
                    $limit = $rand + 6;
                    $LANG = $GLOBALS['System']->COOKIE->get('lang');
                    $i=1;
                    $faq = new Faq("id",">=",$rand,"and","id","<=",$limit);
                    if($faq->count)
                    {
                        foreach($faq as $f)
                        {
                            if($LANG=="en" || $LANG==NULL)
                                $question[$i] = $f->question;
                            else
                                $question[$i] = $f->question_tr;
                            $i++;
                        }
                    }
                    $this->view('BODY', 'wid_faq',Array('question'=>$question));
                    break;
                case 12:
                    $this->view('BODY', 'wid_news_stats');
                    break;
                default:
                    $this->view('BODY', 'wid_faq');
                    break;
            }
        }

        function get_widgets_head()
        {
            Application::deal_unauthorized();
            $current_user = Application::confirm_user();

            $widget_id = @$this->GET["widget_id"];
            $widget_id = str_replace("widget","",$widget_id);
            $wi_id = intval($widget_id);

            switch($wi_id)
            {
                case 1:
                    $this->view('BODY', 'wid_avardz_sports_head');
                    break;
                case 2:
                    $this->view('BODY', 'wid_statement_head');
                    break;
                case 3:
                    $this->view('BODY', 'wid_inbox_head');
                    break;
                case 4:
                    $this->view('BODY', 'wid_eligible_products_head');
                    break;
                case 5:
                    $this->view('BODY', 'wid_product_history_head');
                    break;
                case 6:
                    $this->view('BODY', 'wid_promotions_head');
                    break;
                case 7:
                    $this->view('BODY', 'wid_calendar_head');
                    break;
                case 8:
                    $this->view('BODY', 'wid_quiz_head');
                    break;
                case 9:
                    $this->view('BODY', 'wid_elibrary_head');
                    break;
                case 10:
                    $this->view('BODY', 'wid_query_head');
                    break;
                case 11:
                    $this->view('BODY', 'wid_faq_head');
                    break;
                case 12:
                    $this->view('BODY', 'wid_news_stats_head');
                    break;
                default:
                    $this->view('BODY', 'wid_eligible_products_head');
                    break;
            }
        }
    }
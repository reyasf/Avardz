<?php

    /*
     * Gift Redemption
     * By Members
     */

    class redeem_gift extends Controller
    {
        public $wrapper = 'home';

        function main()
        {
            Application::deal_unauthorized();

            $tpl_head   = 'header';
            $tpl        = 'redeem_gift';
            $tpl_footer = 'footer';
            $tpl_ads    = 'ads';
            $banner  = Application::createBanner();

            $error = '';
            
            if (!Application::confirm_user())
            {
                $tpl = "message";
                $error = "you are not authorized";
                System::redirect_to_controller("misc","login");
            }
            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('error' => $error) );
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);
        }

        function confirm()
        {

            Application::deal_unauthorized();
            
            $current_user = Application::confirm_user();

            $tpl_head   = 'header';
            $tpl        = 'redeem_gift_confirm';
            $tpl_footer = 'footer';
            $tpl_ads    = 'ads';
            $banner  = Application::createBanner();

            $error = '';
            $gifts = $this->SESSION->get('gifts');

            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl,
                            Array(
                                'error' => $error,
                                'total' => $current_user->total_points,
                                'redeemed_points' => $gifts['redeemed_points'],
                                'remaining_points' => $gifts['remaining_points'],
                                'items_ordered' => $gifts['items_ordered'],
                                'ordered_quantity' => $gifts['ordered_quantity']
                            )
            );
            unset($gifts['redeemed_points'], $gifts['remaining_points'], $gifts['items_ordered'], $gifts['ordered_quantity']);
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);

        }

        function redeem()
        {
            Application::deal_unauthorized();

            $current_user = Application::confirm_user();

            $gifts = $this->SESSION->get('gifts');
            $error='';

            if ($gifts && $current_user)
            {
                $total=0;
                $ref_no = $current_user->redeemed + 1;

                foreach($gifts as $gift)
                {
                    $cg = new Gift('id', $gift['gift_id']);
                    if (!$cg->count)
                        continue;

                    $r = new Redeem;
                    $r->user_id  = $current_user->id;
                    $r->gift_id  = $gift['gift_id'];
                    $r->points   = $gift['quantity'] * $cg->points;
                    $r->quantity = $gift['quantity'];
                    $r->date     = time();
                    $r->status   = Application::GIFT_REDEEMED;
                    $r->ref_no   = $ref_no;

                    $r->save();

                    $cg->stock -= $gift['quantity'];
                    $cg->save();

                    Application::create_event(
                                            $r,
                                            Application::EVENT_TYPE_GIFT,
                                            Application::EVENT_GIFT_REDEMPTION
                                    );

                    $total += $gift['quantity'] * $cg->points;
                }
                
                $pts = $current_user->total_points;
                $current_user->total_points = $pts - $total;
                $current_user->redeemed    += $total;
                $current_user->save();

                $ref_no = Application::new_reference_number($ref_no);
                $dis_gifts = Application::format_selected_gifts();

                $mail = Application::get_mail_parts($this->LANG[1553], $current_user,'','', $ref_no, $dis_gifts);

                Email::push(
                                    Array(
                                        "to"        =>  $current_user->email,
                                        "subject"   =>  $mail['subject'],
                                        "body"      =>  $mail['body']
                                    )
                                );

                $message = Application::get_message_parts($this->LANG[1183], $current_user, $ref_no, $dis_gifts);
                
                $gifts = $this->SESSION->remove('gifts');
                $this->COOKIE->remove('p_top');
                $this->COOKIE->remove('p_search');
                $this->COOKIE->remove('p_selected');

                Application::display_receipt($message,$dis_gifts,$ref_no,$current_user);
                
                //email  member   details
                //gift - points - quantity
            }
        }
    }




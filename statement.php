<?php

/*
 *Users statement
 *
 */


class statement extends Controller
    {
        public $wrapper = 'home';
        function main()
        {
            Application::deal_unauthorized();

            $tpl_head   = 'header';
            $tpl        = 'statement';
            $tpl_footer = 'footer';
            $tpl_ads    = 'ads';
            $error      = '';
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
                $earned     = $current_user->total_points + $current_user->redeemed;
                $redeemed   = $current_user->redeemed;
                $balance    = $current_user->total_points;
                $penalty_points = 0;
                $total_points_earned = 0;

                switch($current_user->group_id)
                {
                    case Application::GROUP_AVAJANG:
                        $display = Array(1=>"display:none;", 2=>"", 3=>"", 4=>"", 5=>"", 6=>"", 7=>"", 8=>"");
                        break;
                    case Application::GROUP_AVAJANG2:
                        $display = Array(1=>"", 2=>"", 3=>"", 4=>"", 5=>"", 6=>"", 7=>"", 8=>"");
                        break;
                    case Application::GROUP_IRAN_MOBILE_SALES:
                        $display = Array(1=>"", 2=>"", 3=>"", 4=>"", 5=>"", 6=>"display:none;", 7=>"display:none;", 8=>"");
                        break;
                    case Application::GROUP_IRAN_SALES_PERSON:
                        $display = Array(1=>"", 2=>"", 3=>"", 4=>"", 5=>"", 6=>"display:none;", 7=>"display:none;", 8=>"");
                        break;
                    case Application::GROUP_SHOP_OWNER_NO_SALES:
                        $display = Array(1=>"", 2=>"", 3=>"", 4=>"", 5=>"", 6=>"", 7=>"", 8=>"");
                        break;
                    case Application::GROUP_SHOP_OWNER_WITH_SALES:
                        $display = Array(1=>"", 2=>"", 3=>"", 4=>"", 5=>"", 6=>"", 7=>"", 8=>"");
                        break;
                    case Application::GROUP_UAE_SALES_PERSON:
                        $display = Array(1=>"", 2=>"", 3=>"display:none;", 4=>"", 5=>"", 6=>"", 7=>"", 8=>"display:none;");
                        break;
                    case Application::GROUP_UAE_SHOP_OWNER:
                        $display = Array(1=>"", 2=>"", 3=>"display:none;", 4=>"", 5=>"", 6=>"", 7=>"", 8=>"display:none;");
                        break;
                }

                $user_pe_points = new Point("user_id",$current_user->id,"and","type",Application::POINT_TYPE_PENALTY);
                foreach($user_pe_points as $upp)
                    $penalty_points += $upp->points;

                $user_tot_points = new Point("user_id",$current_user->id,"and","type","!=",Application::POINT_TYPE_PENALTY);
                foreach($user_tot_points as $utp)
                    $total_points_earned += $utp->points;

                if(($total_points_earned-$penalty_points) < 0)
                    $penalty_points = abs($total_points_earned-$penalty_points);
                else
                     $penalty_points = 0;

                $pu = new Point("user_id",$current_user->id);
                $gu = new Redeem("user_id",$current_user->id);

            }
            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl, Array('error' => $error, 'earned'=> $total_points_earned,'redeemed' => $redeemed,'balance' => $balance,'penalty' => $penalty_points,'display' => $display));
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);
        }
    }

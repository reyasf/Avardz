<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class promotions extends Controller
{
        public $wrapper = 'home';
        function main()
        {
            Application::deal_unauthorized();

            $tpl_head   = 'header';
            $tpl        = 'promotions';
            $tpl_footer = 'footer';
            $tpl_ads    = 'ads';
            $banner  = Application::createBanner();

            $lang = $GLOBALS['System']->COOKIE->get('lang');
            if($lang == 'pr')
                $rtl='rtl';
            else
                $rtl='';

            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl,Array('rtl'=>$rtl));
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);

        }
}

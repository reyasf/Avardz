<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class query extends Controller
{
        public $wrapper = 'home';
        function main()
        {
            Application::deal_unauthorized();

            $tpl_head   = 'header';
            $tpl        = 'query';
            $tpl_footer = 'footer';
            $tpl_ads    = 'ads';
            $error          = '';
            $banner  = Application::createBanner();

            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl);
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);

        }
}

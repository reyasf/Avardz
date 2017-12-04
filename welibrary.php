<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class welibrary extends Controller
{
        public $wrapper = 'home';
        function main()
        {
            $tpl_head   = 'header';
            $tpl        = 'elibrary';
            $tpl_footer = 'footer';
            $tpl_ads    = 'ads';
            $banner  = Application::createBanner();

            if (!Application::confirm_user())
            {
                $tpl = "message";
                $error = "you are not authorized";
                System::redirect_to_controller("misc","login");
            }

            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));


            $LANG = $GLOBALS['System']->COOKIE->get('lang');
            if($LANG=="en" || $LANG==NULL)
            {
                $url = Array(1 => "http://uk.gigabyte.com/products/comparison/main.aspx?ck=2",
                             2 => "http://uk.gigabyte.com/press-center/channel.aspx",
                             3 => "http://www.gigabyte.com/support-downloads/download-center.aspx?ck=3",
                             4 => "http://uk.gigabyte.com/press-center/news.aspx",
                             5 => "http://www.gigabyte.com/support-downloads/multimedia.aspx?ck=3",
                             6 => "http://uk.gigabyte.com/products/main.aspx?s=42",
                             7 => "http://uk.gigabyte.com/products/main.aspx?s=43");
            }
            else
            {
                $url = Array(1 => "http://www.gigabyte.ir/products/comparison/main.aspx?ck=2",
                             2 => "http://www.gigabyte.ir/products/microsite.aspx?s=42",
                             3 => "http://www.gigabyte.ir/support-downloads/download-center.aspx",
                             4 => "http://www.gigabyte.ir/press-center/news.aspx",
                             5 => "http://www.gigabyte.ir/support-downloads/multimedia.aspx?ck=2",
                             6 => "http://www.avajang.com/products/gigabyte/index.asp",
                             7 => "http://www.avajang.com/products/gigabyte/index.asp#vga");
            }
            $this->view('BODY',   $tpl, Array('url' => $url));
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);

        }
}

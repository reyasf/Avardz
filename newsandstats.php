<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class newsandstats extends Controller
{
        public $wrapper = 'page';
        function main()
        {
            $tpl_head   = 'header';
            $tpl        = 'coming_soon';
            $tpl_footer = 'footer';
            $banner  = Application::createBanner();

            $this->view('HEADER', $tpl_head,Array('banner'=>$banner));
            $this->view('BODY',   $tpl);
            $this->view('FOOTER', $tpl_footer);

        }
}

<?php

/**
 *
 *
 *
 *
 *
 *
 */

    class country_sel extends Controller
    {
        public $wrapper = 'simple';

        function main()
        {
            $tpl = "country_sel";
            $this->view('BODY',$tpl);
        }
    }

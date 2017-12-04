<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class contactus extends Controller
{
        public $wrapper = 'home';
        function main()
        {
            $tpl_head   = 'header';
            $tpl        = 'contactus';
            $tpl_footer = 'footer';
            $tpl_ads    = 'ads';
            $error          = '';
            $banner  = Application::createBanner();

            if(System::get_language_name() == DEFAULT_LANGUAGE)
                $country = new Country("id",1);
            else
                $country = new Country("id",2);

            $contact = str_replace(Array("{region_phone1}","{region_phone2}","{region_ext}","{region_email}","{region_fax}","{region_fax_ext}","{office_hours}"),
                                   Array($country->phone1,$country->phone2,$country->phone1_ext,$country->admin_email,$country->fax,$country->fax_ext,$country->work_time),$this->LANG[1713]);


            
            $contact = Application::formatText($contact);

            $this->view('HEADER', $tpl_head,Array('banner' => $banner));
            $this->view('BODY',   $tpl,Array('contact' => $contact));
            $adverts = new Advertisement("id",">","0");
            foreach($adverts as $ad)
            {
                $this->view('ADS',   $tpl_ads, Array('ad' => $ad) );
            }
            $this->view('FOOTER', $tpl_footer);

        }
}


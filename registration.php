<?php

    class registration extends Controller 
    {
        public $wrapper    = 'page';
        const  message_tpl = 'message';

        private  $reg_map    = Array(
            1 => 'registration_sales',
            2 => 'registration_sales',
            3 => 'registration_owner',
            8 => 'registration_sales_uae',
            7 => 'registration_owner_uae'
        );

        private  $group    = Array(
            1 => 8,
            3 => 7
        );
    
        function main() 
        {
            $tpl_head   = 'header';
            $tpl_body   = 'registration';
            $tpl_footer = 'footer';
            $display = ":none;";
            $error = '';

            if ($this->POST) 
            {
                $f = new Form('registration1');
                
                switch ($f->evaluate())
                {
                    case FORM_OK :
                        $tpl_body = 'message';
                        $this->SESSION->set('registration1', $this->POST);
                        $this->SESSION->set('country_selected', $this->POST['country']);
                        $this->SESSION->set('old_owner_account', $this->POST['old_owner_account']);
                        $group_id = $this->POST['group_id'];
                        if($this->SESSION->get("country_selected") == COUNTRY_UAE)
                        {
                            $group_id = $this->group[ $this->POST['group_id'] ];
                        }

                        if(@$this->POST["multiowner"])
                          if($this->POST["old_owner_account"]==NULL)
                          {
                              $display = ":block;";
                              $tpl_body = "registration";
                              $error = $this->LANG[3253];
                          }
                          else
                          {
                              if(!filter_var($this->POST["old_owner_account"], FILTER_VALIDATE_EMAIL))
                              {
                                  $display = ":block;";
                                  $tpl_body = "registration";
                                  $error = $this->LANG[487];
                              }
                              else
                              {
                                  $owner_existing_account = new User("email",$this->POST["old_owner_account"],"and","status",Application::STATUS_AC);
                                  if(!$owner_existing_account->count)
                                  {
                                      $display = ":block;";
                                      $tpl_body = "registration";
                                      $error = $this->LANG[3255];
                                  }
                                  else
                                  {
                                      if($owner_existing_account->group_id == Application::GROUP_IRAN_MOBILE_SALES || $owner_existing_account->group_id == Application::GROUP_IRAN_SALES_PERSON)
                                      {
                                          $display = ":block;";
                                          $tpl_body = "registration";
                                          $error = Application::formatText($this->LANG[3257]);
                                      }
                                  }
                              }
                          }
                      break;
                }
            }



            if ($tpl_body == self::message_tpl && $error == '')
                System::redirect_to_controller( $this->reg_map[ $group_id ] );
            else
            {
                $this->view('HEADER', $tpl_head,Array('banner'=>'homeban03'));
                $this->view('BODY'  , $tpl_body, Array('error' => $error,'display' => $display,'url' => ROOT_PATH.'?controller=misc&method=join_avardz'));
                $this->view('FOOTER', $tpl_footer);
            }

            System::no_store();
        }
    }
    
    
    


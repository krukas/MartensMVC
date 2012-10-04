<?php if (!defined('__SITE_PATH')) exit('No direct script access allowed');

class home extends controller {
   function index(){
        $this->load->view('home');
   }
   
   function about() {
       $this->load->view('about');
   }
}        
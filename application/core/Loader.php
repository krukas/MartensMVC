<?php
/**
* MartensMCV is an simple and smal framework that make use of OOP and MVC patern.
* Copyright (C) 2012 Maikel Martens
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('__SITE_PATH')) exit('No direct script access allowed');
/**
 * MartensMVC
 *
 * An MVC framework for PHP/MYSQL
 *
 * @author		Maikel Martens
 * @copyright           Copyright (c) 20012 - 2012, Martens.me.
 * @license		http://martens.me/license.html
 * @link		http://martens.me
 * @since		Version 1.0
 * @filesource
 */
// ------------------------------------------------------------------------

/**
 * loader class
 *
 * Loads models and views for controller
 *
 * @package		 MartensMCV
 * @subpackage   Core
 * @category     Core
 * @author	     Maikel Martens
 */
// ------------------------------------------------------------------------

class Core_Loader {

    /**
     * __get
     *
     * Allows views to access Controller's loaded classes using the same
     * syntax as controllers.
     *
     * @param   string
     * @access private
     */
    function __get($key) {
        $c = Core_Controller::get_instance();
        return $c->$key;
    }

    /**
     *
     * _autoLoad
     * 
     * Auto load libraries and models that er set in config/config.php
     *
     * @return  void
     *
     */
    function _autoLoad() {
        $loadConfig = __CONFIG_PATH. "config.php";

        if (is_file($loadConfig)) {
            include $loadConfig;

            /* Load the libraries */
            if (isset($autoload['libraries']) && count($autoload['libraries']) > 0) {
                if (in_array('Database', $autoload['libraries'])) {
                    $this->database();
                    $autoload['libraries'] = array_diff($autoload['libraries'], array('Database'));
                }

                // Load all other libraries
                foreach ($autoload['libraries'] as $item) {
                    $this->library($item);
                }
            }

            /* Load the models */
            if (isset($autoload['model']) && count($autoload['model']) > 0) {
                foreach ($autoload['model'] as $item) {
                    $this->model($item);
                }
            }
        }
    }
    
    /**
     *
     * Databse
     * 
     * Add database to the controller
     *
     * @return  void
     *
     */
    function database() {
        load_class("Database", "core");
        if (isset(Core_Controller::get_instance()->db)) {
            throw new Exception("Database already loaded!");
        }
        Core_Controller::get_instance()->db = new Core_Database();
    }

    /**
     *
     * Model
     * 
     * Add model to the controller
     *
     * @param   string  model name
     * @return  void
     *
     */
    function model($name) {
        $varName = 'Model'.$name;
        $className = 'Model_'.$name;

        load_class($name, 'models');
        
        if (!class_exists($className)) {
            throw new Exception("Model class not exists $name");
        }

        if (!isset(Core_Controller::get_instance()->$varName)) {
            Core_Controller::get_instance()->$varName = new $className();
        }
    }

    /**
     *
     * library
     * 
     * Add library to the controller
     *
     * @param   string  library name
     * @return  void
     *
     */
    function library($name) {
        $varName    = 'Lib' . $name;
        $className  = 'Library_' . $name;

        load_class($name, 'libraries');

        if (!class_exists($className)) {
            throw new Exception("Library class not exists $name");
        }

        if (!isset(Core_Controller::get_instance()->$varName)) {
            Core_Controller::get_instance()->$varName = new $className();
        }
    }

    /**
     *
     * view
     *
     * @param   string  view name
     * @param   array   data used in view
     * @return  void
     *
     */
    function view($file, $data = array(), $autoloadViews = true) {
        $loadConfig = __CONFIG_PATH. "config.php";
        if (!is_file($loadConfig)){
            throw new Exception("Could not load the config file!");
        }
        include $loadConfig;

        /* Load header when set */
        if($autoloadViews && !empty($autoloadHeaderView)){
            $headerFile = __APPLICATION_PATH . 'views/' . $autoloadHeaderView . '.php';
            if (!is_readable($headerFile)) {
                throw new Exception("Auto load header view file not exists '" . $headerFile . "'");
            }
            include $headerFile;
        }

        /* Set variables from array */
        foreach ($data as $key => $value) {
            $$key = $value;
        }
        $file = __APPLICATION_PATH . 'views/' . $file . '.php';
        if (!is_readable($file)) {
            throw new Exception("View file not exists '" . $file . "'");
        }
        include $file;

        /* Load footer when set */
        if($autoloadViews && !empty($autoloadFooterView)){
            $footerFile = __APPLICATION_PATH . 'views/' . $autoloadFooterView . '.php';
            if (!is_readable($footerFile)) {
                throw new Exception("Auto load footer view file not exists '" . $footerFile . "'");
            }
            include $footerFile;
        }
    }

}

/* End of file loader.php */
/* Location: ./application/core/loader.php */

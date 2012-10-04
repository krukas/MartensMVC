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
 * Upload class
 *
 * Upload class for uploading multiple files.
 *
 * @package		MartensMCV
 * @subpackage          Libraries
 * @category            Libraries
 * @author		Maikel Martens
 */
// ------------------------------------------------------------------------

class upload {
    /* @var String contains directory where the files should be uploaded. */

    private $uploadDirectory;

    /* @var array contains arrays with the information about the file. */
    private $uploadedFiles = array();

    /* @var array contains allowed mimes. */
    private $allowedMimes;

    /* @var int contains the maximum file size. */
    private $maximumFileSize;

    /* @var String contains valid extansions */
    private $validExtensions;

    /* @var boolean value to check for images */
    private $isImage;

    /* @var boolean value to check if random strings is added to file */
    private $addRandom;

    /* @var int contains maximum width of image */
    private $maximumWidth;

    /* @var int contains maximum height of image */
    private $maximumHeight;

    /* @var array contains messages */
    private $message = array();

    /**
     * Constructer
     *
     * Create uploader en set default values for:
     * $isImage         false
     * $message         array
     * $UploadedFiles   array
     *
     * @access	public
     * @return	void
     */
    function __construct() {
        /* Inlude allowed mimes */
        include __CONFIG_PATH . 'mimes.php';
        $this->allowedMimes = $mimes;

        /* Set default options */
        $this->addRandom = false;
        $this->isImage = false;
        $this->validExtensions = '*';
        $this->setUploadDirectory('data/uploads');
    }

    /**
     * RandomString
     *
     * Creates an random string, used to add on files
     *
     * @access	private
     * @param	int     Length of the return string
     * @return	String
     */
    private function randomString($length) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        $size = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[rand(0, $size - 1)];
        }
        return $str;
    }

    /**
     * getValidFileTypes
     *
     * returns an array with valid file type mimes 
     *
     * @access	private
     * @return	array
     */
    private function getValidFileTypes() {
        $validFileTypes = array();
        if ($this->validExtensions != '*') {
            $extensions = explode('|', $this->validExtensions);

            /* Create the array with mimes only with the allowed extensions */
            foreach ($this->allowedMimes as $extension => $validMimes) {
                foreach ($extensions as $validExtension) {
                    if ($validExtension == $extension) {
                        if (is_array($validMimes)) {
                            $validFileTypes = array_merge($validFileTypes, $validMimes);
                        } else {
                            $validFileTypes[] = $validMimes;
                        }
                    }
                }
            }
        } else {
            foreach ($this->allowedMimes as $validMimes) {
                if (is_array($validMimes)) {
                    $validFileTypes = array_merge($validFileTypes, $validMimes);
                } else {
                    $validFileTypes[] = $validMimes;
                }
            }
        }
        return $validFileTypes;
    }

    /**
     * validateExtensions
     *
     * Checks if the files match the given valid extension and file types, 
     * when validExtensions not is set it wil return true;
     *
     * @access	private
     * @return	boolean
     */
    private function validateExtensions() {
        if ($this->validExtensions != '*') {
            $controle = true;

            /* loops to each file */
            foreach ($this->uploadedFiles as $key => $file) {
                /* Get file extension */
                $x = explode('.', $file['name']);
                $extension = $x[count($x) - 1];

                /* Get file type */
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $type = finfo_file($finfo, $file['tmp_name']);

                $extensions = explode('|', $this->validExtensions);

                /* When it's not a valid extension or file create message */
                if (!in_array($extension, $extensions) || !in_array($type, $this->getValidFileTypes())) {
                    $this->uploadedFiles[$key]['validate'] = false;
                    $controle = false;
                    $this->message[] = "Error for file \"" . $file['name'] . "\" file type is not allowed!";
                }
            }
            return $controle;
        }
        return true;
    }

    /**
     * validateSize
     *
     * Checks if the files are not biger then given maximumFileSize, when maximumFileSize
     * not is set it wil return true;
     *
     * @access	private
     * @return	boolean
     */
    private function validateSize() {
        if (!empty($this->maximumFileSize)) {
            $controle = true;
            foreach ($this->uploadedFiles as $key => $file) {
                if ($file['size'] > $this->maximumFileSize) {
                    $this->uploadedFiles[$key]['validate'] = false;
                    $controle = false;
                    $size = (int) ($file['size'] / 100);
                    $this->message[] = "Error for file \"" . $file['name'] . "\" is too big. It must be less than "
                            . $this->maximumFileSize / 1000 . " kB and it is " . ($size / 10) . " kB.";
                }
            }
            return $controle;
        }
        return true;
    }

    /**
     * validateImages
     *
     * Checks if the files are images and not biger then given maximumWidth and
     * maximumHeight.
     *
     * @access	private
     * @return	boolean
     */
    private function validateImages() {
        if ($this->isImage) {
            $controle = true;

            /* loops to each file */
            foreach ($this->uploadedFiles as $key => $file) {
                $imageDate = getimagesize($file['tmp_name']);

                /* Validate if file is a image */
                if ($imageDate !== false && ($imageDate['mime'] == "image/png" || $imageDate['mime'] == "image/jpeg" || $imageDate['mime'] == "image/jpeg")) {

                    /* Validate size of the image  */
                    if ($imageDate [0] > $this->maximumWidth) {
                        $this->uploadedFiles[$key]['validate'] = false;
                        $controle = false;
                        $this->message[] = "Error for file \"" . $file['name'] . "\" exceeds the maximum width " . $this->maximumWidth . ".";
                    }
                    if ($imageDate [1] > $this->maximumHeight) {
                        $this->uploadedFiles[$key]['validate'] = false;
                        $controle = false;
                        $this->message[] = "Error for file \"" . $file['name'] . "\" exceeds the maximum height " . $this->maximumHeight . ".";
                    }
                } else {
                    $this->uploadedFiles[$key]['validate'] = false;
                    $controle = false;
                    $this->message[] = "Error for file \"" . $file['name'] . "\" is not a valid image!";
                }
            }
            return $controle;
        }
        return true;
    }

    /**
     * uploadFiles
     *
     * Upload files gives boolean when one file gives a error message.
     *
     * @access	public
     * @return	boolean
     */
    public function uploadFiles() {
        if (empty($this->uploadedFiles)) {
            $this->message[] = "No files are uploaded";
            return false;
        }
        $controle = true;

        /* Validate the file size */
        if (!$this->validateSize()) {
            $controle = false;
        }

        /* Validate the file extenions */
        if (!$this->validateExtensions()) {
            $controle = false;
        }

        /* Validate images when isImages is true */
        if (!$this->validateImages()) {
            $controle = false;
        }

        /* move files to uploadDirectory */
        foreach ($this->uploadedFiles as $key => $file) {
            if ($file['validate']) {

                /* Add random string to file when addRandom is set to true */
                if ($this->addRandom) {
                    $fileDestination = $this->uploadDirectory . '/' . $this->randomString(10) . "_" . $file['name'];
                } else {
                    $fileDestination = $this->uploadDirectory . '/' . $file['name'];
                }

                /* Validate if file not already exists */
                while (file_exists($fileDestination)) {
                    $fileDestination = $this->uploadDirectory . '/' . $this->randomString(10) . "_" . $file['name'];
                }

                /* Validate if file is uploaded and move the file */
                if (is_uploaded_file($file['tmp_name'])) {
                    if (!move_uploaded_file($file['tmp_name'], $fileDestination)) {
                        $this->uploadedFiles[$key]['validate'] = false;
                        $this->message[] = "Error for file \"" . $file['name'] . "\" was not uploaded!";
                        $controle = false;
                    } else {
                        $name = explode('/', $fileDestination);
                        $this->uploadedFiles[$key]['uploaded'] = true;
                        $this->uploadedFiles[$key]['location'] = $fileDestination;
                        $this->uploadedFiles[$key]['name'] = $name[count($name) - 1];
                        $this->message[] = $file['name'] . " is successfully uploaded!";
                    }
                } else {
                    $this->uploadedFiles[$key]['validate'] = false;
                    $this->message[] = "Error for file \"" . $file['name'] . "\" is not an uploaded file!";
                    $controle = false;
                }
            }
        }
        return $controle;
    }

    /**
     * Loadfile
     *
     * Load a single file in class throws Exception if not all the array field 
     * are set or when no array is given
     *
     * @access	public
     * @param   array   
     * @return	void
     */
    public function loadFile($file) {
        if (is_array($file)) {
            if (isset($file['name']) && isset($file['type']) && isset($file['tmp_name']) && isset($file['size'])) {
                if ($file['size'] != 0) {
                    $file['validate'] = true;
                    $file['uploaded'] = false;
                    $this->uploadedFiles[] = $file;
                }
            } else {
                throw new Exception("Not evrything is set in array in loadFile!");
            }
        } else {
            throw new Exception("No array given when array expected in loadFile!");
        }
    }

    /**
     * Loadfiles
     *
     * Load all files that are in $_FILES in the class.
     *
     * @access	public   
     * @return	void
     */
    public function loadFiles() {
        foreach ($_FILES as $key => $file) {
            if ($file['size'] != 0) {
                $this->uploadedFiles[$key] = $file;
                $this->uploadedFiles[$key]['validate'] = true;
                $this->uploadedFiles[$key]['uploaded'] = false;
            }
        }
    }

    /**
     * setUploadDirectory
     *
     * Set the directory path from __SITE_PATH, throws Exception when not valid directory is given.
     *
     * @access	public
     * @param   String  path  
     * @return	void
     */
    public function setUploadDirectory($path) {
        $path = realpath(__SITE_PATH . $path).'/';
        if (is_writable($path)) {
            $this->uploadDirectory = $path;
        } else {
            throw new Exception("No valid directory was given or not writeable in setUploadDirectory! " . $path);
        }
    }

    /**
     * setMaximumFileSize
     *
     * Set the maximum file size in kB, throws Exception when no int is given.
     *
     * @access	public
     * @param   int     size  
     * @return	void
     */
    public function setMaximumFileSize($size) {
        if (is_int($size)) {
            $this->maximumFileSize = (int) ($size * 1000);
        } else {
            throw new Exception("No int given when int expected in setMaximumFileSize!");
        }
    }

    /**
     * setValidExtensions
     *
     * Set the valid extensions in a string separated by | 
     * throws Exception when no String is given.
     *
     * @access	public
     * @param   String    Extensions  
     * @return	void
     */
    public function setValidExtensions($extensions) {
        if (is_string($extensions)) {
            $this->validExtensions = $extensions;
        } else {
            throw new Exception("No String was given when String expected in setValidExtensions!");
        }
    }

    /**
     * setIsImage
     *
     * Set the isImage boolean
     * throws Exception when no boolean is given.
     *
     * @access	public
     * @param   boolean     boolean
     * @return	void
     */
    public function setIsImage($boolean) {
        if (is_bool($boolean)) {
            $this->isImage = $boolean;
        } else {
            throw new Exception("No boolean given when boolean expected in setIsImage!");
        }
    }

    /**
     * setAddRandomString
     *
     * When set it wil add random string to file, if file already exsist it 
     * also will add random string.
     * throws Exception when no boolean is given.
     *
     * @access	public
     * @param   boolean     boolean
     * @return	void
     */
    public function setAddRandomString($boolean) {
        if (is_bool($boolean)) {
            $this->addRandom = $boolean;
        } else {
            throw new Exception("No boolean given when boolean expected in setAddRandomString!");
        }
    }

    /**
     * setMaximumWidth
     *
     * Set the maximum width for images
     * throws Exception when no int is given.
     *
     * @access	public
     * @param   int     width
     * @return	void
     */
    public function setMaximumWidth($width) {
        if (is_int($width)) {
            $this->maximumWidth = $width;
        } else {
            throw new Exception("No int given when int expected in setMaximumWidth!");
        }
    }

    /**
     * setMaximumHeight
     *
     * Set the maximum height for images
     * throws Exception when no int is given.
     *
     * @access	public
     * @param   int     height
     * @return	void
     */
    public function setMaximumHeight($height) {
        if (is_int($height)) {
            $this->maximumHeight = $height;
        } else {
            throw new Exception("No int given when int expected in setMaximumHeight!");
        }
    }

    /**
     * getMessages
     *
     * Get messages, returns array when there are no messages it wil return a
     * empty array.
     *
     * @access	public
     * @return	array
     */
    public function getMessages() {
        return $this->message;
    }

    /**
     * getSuccessfullUploudedFiles
     *
     * Get the succesfull uplouded files.
     * returns array with the file orginal name as value and the location as key
     *
     * @access	public
     * @return	array
     */
    public function getSuccessfullUploudedFiles() {
        $successfullUplouds = array();
        foreach ($this->uploadedFiles as $file) {
            if ($file['uploaded']) {
                $successfullUplouds[$file['location']] = $file['name'];
            }
        }
        return $successfullUplouds;
    }

}
/* End of file upload.php */
/* Location: ./application/libraries/upload.php */
<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of HgDriverclass
 * @TODO Create Class exception to thro HG messages
 * @TODO Make this driver compliant with Apache ??
 * @TODO Make sure directories tree to manage forks and repo is a good choice
 * @author gstorchi
 */
 $DIR = dirname(__FILE__);
 require_once('HgBackend.class.php');
 require_once($DIR.'/../DVCS/DVCSDriver.class.php');
 require_once('exceptions/HgDriverException.class.php');
 require_once('exceptions/HgDriverErrorException.class.php');
 require_once('exceptions/HgDriverSourceNotFoundException.class.php');
 require_once('exceptions/HgDriverDestinationNotEmptyException.class.php');

class HgDriver implements DVCSDriver {

    private static $instance;
    
    private function __construct() {       
    }

    public static function instance() {
        if ( empty(self::$instance) ) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }    

    /**
     * Make a clone of a source repository
     * @param <String> $source      source directory
     * @param <String> $destination destination directory
     * @return <boolean>
     */
    public function fork($source, $destination) {
        $rcode = 0;
        if ( !file_exists($source) ) {
            throw new HgDriverSourceNotFoundException($source);
        }
        //WARNING : never use --shared/--reference options
        $cmd    = 'hg clone '.$source.' '.$destination;
        $output = system($cmd, $rcode);
        if ( $rcode != 0 ) {
            throw new HgDriverErrorException($cmd.'->'.$output);
        }        
        $this->setPermissions($destination);
        return true;
    }

    /**
     * Initialize a repository    
     * @param Boolean $bare is a bare a repository
     * @return Boolean
     */
    public function init($bare=false) {
        //TODO there are more things to do
        /*$rcode = 0;            
        $cmd = 'hg init';
        if ( $bare === false ) {
            $cmd = 'hg init';
            $output = system($cmd, $rcode);
            if ( $rcode != 0 ) {
                throw new HgDriverErrorException($cmd.' -> '.$output);
            }
            return true;
        }*/

        $cmd = 'hg init 2>&1';
        $out = array();
        $ret = -1;
        exec($cmd, $out, $ret);
        if ( $ret !== 0 ) {
            throw new HgDriverErrorException('Hg init failed on '.$cmd.PHP_EOL.implode(PHP_EOL, $out));
        }
        //Bypass permissions setting
        //$this->setPermissions(getcwd());
        return true;
    }

    public function delete($path) {
        if ( empty($path) || !is_writable($path) ) {
           throw new HgDriverErrorException('Empty path or permission denied '.$path);
        }
        $rcode = 0;
        $output = system('rm -fr '.$path, $rcode);
        if ( $rcode != 0 ) {
           throw new HgDriverErrorException('Unable to delete path '.$path);
        }
        return true;
    }    

    public function activateHook($hookName, $repoPath, $uid, $gid) {
    //NOT IMPLEMENTED YET
        return true;
    }

    public function masterExists($repoPath) {
    //NOT IMPLEMENTED YET
        return true;
    }

    //TODO control access types
    public function setRepositoryAccess($repoPath, $access) {
        //HTTP configuration
    }

    public function setDescription($repoPath, $description) {
        return true;
    }

    public function getDescription($repoPath) {
        return '';
    }

    /**
     * Ensure repository has the right permissions
     *
     * @param String $path Path to the repository
     *
     * @return Boolean
     */
    protected function setPermissions($path) {
        $rcode  = 0;
        $cmd    = 'find '.$path.' -type d | xargs chmod u+rwx,g+rwxs '.$path;
        $output = system($cmd, $rcode);
        if ( $rcode != 0 ) {
            throw new HgDriverErrorException($cmd.' -> '.$output);
        }

        if (!chmod($path.DIRECTORY_SEPARATOR.'HEAD', 0664)) {
            throw new GitDriverErrorException('Unable to set permissions on HEAD');
        }
        return true;
    }
 
}

?>
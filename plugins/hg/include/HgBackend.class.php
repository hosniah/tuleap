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
require_once('common/backend/Backend.class.php');
require_once('HgDao.class.php');
require_once('HgDriver.class.php');
require_once('exceptions/HgBackendException.class.php');
/**
 * Description of HgBackend
 *
 * @author Guillaume Storchi
 */

class HgBackend extends Backend {
    
    private $driver;    
    private $packagesFile;
    private $configFile;
    //path MUST end with a '/'
    const HG_ROOT_PATH = '/var/lib/codendi/hgroot/';
    private $hgRootPath;

    const DEFAULT_DIR_MODE = '770';    

    protected function __construct() {
        $this->hgRootPath  = '';
        $this->driver       = HgDriver::instance();
        $this->packagesFile = 'etc/packages.ini';
        $this->configFile   = 'etc/config.ini';
        $this->dao          = new HgDao();
        //WARN : this is much safer to set it to an absolute path
        $this->hgRootPath  = self::HG_ROOT_PATH;
        $this->hgBackupDir = PluginManager::instance()->getPluginByName('hg')->getPluginInfo()->getPropVal('hg_backup_dir');        
    }

    public function setHgRootPath($hgRootPath) {
        $this->hgRootPath = $hgRootPath;
    }

    public function getHgRootPath() {
        return $this->hgRootPath;
    }   

    public function getDao() {
        return $this->dao;
    }
    public function getDriver() {
        return $this->driver;
    }
    
    public function createFork($clone) {
        if ( $clone->exists() ) {
           throw new HgBackendException('Repository already exists');
        }        
        $parentPath  = $clone->getParent()->getPath();
        $parentPath  = $this->getHgRootPath().DIRECTORY_SEPARATOR.$parentPath;
        $forkPath    = $clone->getPath();
        $forkPath    = $this->getHgRootPath().DIRECTORY_SEPARATOR.$forkPath;        
        $this->getDriver()->fork($parentPath, $forkPath);
        $this->getDriver()->activateHook('post-update', $forkPath, 'codendiadm', $clone->getProject()->getUnixName());
        $this->setRepositoryPermissions($clone);        
        $id = $this->getDao()->save($clone);
        $clone->setId($id);
        $this->changeRepositoryAccess($clone);
        return true;
    }

    /**
     * Function that setup a repository , each repository has repo/ directory and forks/ directory
     * @param <type> $rootPath
     * @param <type> $mode
     * @return <type>
     * @todo move hgroopath creation to an install script
     */
    public function createReference($repository) {
        if ( $repository->exists() ) {
            throw new HgBackendException('Repository already exists');
        }
        $path = $repository->getPath();
        //create hg root if does not exist
        $this->createHgRoot();
        //create project dir if does not exists
        $this->createProjectRoot($repository);
        $path = $this->getHgRootPath().DIRECTORY_SEPARATOR.$path;
        mkdir($path, 0775, true);
        chdir($path);
        $this->getDriver()->init($bare=true);
        //$this->getDriver()->activateHook('post-update', $path, 'codendiadm', $repository->getProject()->getUnixName());
        $this->setRepositoryPermissions($repository);
        $id = $this->getDao()->save($repository);
        $repository->setId($id);
        $this->changeRepositoryAccess($repository);
        return true;
    }

    public function delete($repository) {
        $path = $repository->getPath();
        if ( empty($path) ) {
            throw new HgBackendException('Bad repository path: '.$path);
        }
        $path = $this->getHgRootPath().DIRECTORY_SEPARATOR.$path;
        if ( $this->getDao()->hasChild($repository) === true ) {
            throw new HgBackendException( $GLOBALS['Language']->getText('plugin_hg', 'backend_delete_haschild_error') );
        }
        $this->archive($repository);
        $this->getDao()->delete($repository);        
        $this->getDriver()->delete($path);
        return true;
    }

    public function save($repository) {
        $path          = self::HG_ROOT_PATH.'/'.$repository->getPath();
        $fsDescription = $this->getDriver()->getDescription($path);
        $description   = $repository->getDescription();
        if ( $description != $fsDescription ) {
            $this->getDriver()->setDescription( $path, $description );
        }
        $this->getDao()->save($repository);
    }

    public function isInitialized($repository) {
        $masterExists = $this->getDriver()->masterExists( $this->getHgRootPath().'/'.$repository->getPath() );
        if ( $masterExists ) {
            $this->getDao()->initialize( $repository->getId() );
            return true;
        } else {
            return false;
        }
    }

    public function changeRepositoryAccess($repository) {
        $access   = $repository->getAccess();
        $repoPath = $repository->getPath();
        $path     = self::HG_ROOT_PATH.'/'.$repoPath;        
        $this->getDriver()->setRepositoryAccess($path, $access);
        $this->getDao()->save($repository);
        return true;
    }

    /**
     * INTERNAL METHODS
     */
    
    protected function setRepositoryPermissions($repository) {
        $path = $this->getHgRootPath().DIRECTORY_SEPARATOR.$repository->getPath();   
        $this->recurseChownChgrp($path, 'codendiadm',$repository->getProject()->getUnixName() );
        return true;
    }

    protected function createHgRoot() {
        $hgRootPath    = $this->getHgRootPath();        
        //create the hgroot directory
        if ( !is_dir($hgRootPath) ) {
            if ( !mkdir($hgRootPath, 0755) ) {
                throw new HgBackendException( $GLOBALS['Language']->getText('plugin_hg', 'backend_hgroot_mkdir_error').' -> '.$hgRootPath );
            }            
        }
        return true;
    }

    //TODO : public project
    protected function createProjectRoot($repository) {
        $hgProjectPath = $this->getHgRootPath().DIRECTORY_SEPARATOR.$repository->getRootPath();
        $groupName      = $repository->getProject()->getUnixName();
        if ( !is_dir($hgProjectPath) ) {

            if ( !mkdir($hgProjectPath, 0775, true) ) {
                throw new HgBackendException($GLOBALS['Language']->getText('plugin_hg', 'backend_projectroot_mkdir_error').' -> '.$hgProjectPath);
            }

            if ( !$this->chgrp($hgProjectPath, $groupName ) ) {
                throw new HgBackendException($GLOBALS['Language']->getText('plugin_hg', 'backend_projectroot_chgrp_error').$hgProjectPath.' group='.$groupName);
            }            
        }
        return true;
    }

    /**
     *@todo move the archive to another directory
     * @param <type> $repository
     * @return <type>
     */
    protected function archive($repository) {
        chdir( $this->getHgRootPath() );
        $path = $repository->getPath();
        $name = $repository->getName();
        $date = $repository->getDeletionDate();
        $projectName = $repository->getProject()->getUnixName();
        $archiveName = $projectName.'_'.$name.'_'.strtotime($date).'.tar.bz2 ';
        $cmd    = ' tar cjf '.$archiveName.' '.$path;
        $rcode  = 0 ;
        $output = $this->system( $cmd, $rcode );        
        if ( $rcode != 0 ) {
            throw new HgBackendException($cmd.' -> '.$output);
        }
        if ( !empty($this->hgBackupDir) && is_dir($this->hgBackupDir) ) {
            $this->system( 'mv '.$this->getHgRootPath().'/'.$archiveName.' '.$this->hgBackupDir.'/'.$archiveName );
        }
        return true;
    }    
}

?>

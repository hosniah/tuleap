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
require_once('mvc/PluginActions.class.php');
require_once('events/SystemEvent_HG_REPO_CREATE.class.php');
require_once('events/SystemEvent_HG_REPO_CLONE.class.php');
require_once('events/SystemEvent_HG_REPO_DELETE.class.php');
require_once('events/SystemEvent_HG_REPO_ACCESS.class.php');
require_once('common/system_event/SystemEventManager.class.php');
require_once('HgBackend.class.php');
require_once('HgRepository.class.php');
require_once('HgDao.class.php');

/**
 * HgActions
 * @todo call Event class instead of SystemEvent
 * @author Guillaume Storchi
 */
class HgActions extends PluginActions {


    public function __construct($controller) {
        parent::__construct($controller);
        $this->systemEventManager = SystemEventManager::instance();

    }

    protected function getText($key) {
        return $GLOBALS['Language']->getText('plugin_hg', $key);
    }
    
    public function process($action, $params) {
       return call_user_func_array(array($this,$action), $params);
    }
    
    public function deleteRepository( $projectId, $repositoryId ) {
        $c            = $this->getController();
        $projectId    = intval($projectId);
        $repositoryId = intval($repositoryId);
        if ( empty($projectId) || empty($repositoryId) ) {
            $c->addError( $this->getText('actions_params_error') );            
            return false;
        }       
        $repository   = new HgRepository();
        $repository->setId( $repositoryId );
        if ( $repository->hasChild() ) {
            $c->addError( $this->getText('backend_delete_haschild_error') );
            $c->redirect('/plugins/hg/index.php/'.$projectId.'/view/'.$repositoryId.'/');
            return false;
        }
        $this->systemEventManager->createEvent(
            'HG_REPO_DELETE',
            $projectId.SystemEvent::PARAMETER_SEPARATOR.$repositoryId,
            SystemEvent::PRIORITY_MEDIUM
        );       
        $c->addInfo( $this->getText('actions_delete_process') );
        $c->addInfo( $this->getText('actions_delete_backup').' : '.PluginManager::instance()->getPluginByName('hg')->getPluginInfo()->getPropVal('hg_backup_dir') );
        $c->redirect('/plugins/hg/?action=index&group_id='.$projectId);
    }
       
	public function createReference( $projectId, $repositoryName) {
        $c              = $this->getController();
        $projectId      = intval( $projectId );
        if ( empty($repositoryName) ) {
            $c->addError($this->getText('actions_params_error'));
            $c->redirect('/plugins/hg/?action=index&group_id='.$projectId);
            return false;
        }
        if ( HgDao::checkName($repositoryName) === false ) {
            $c->addError( $this->getText('actions_input_format_error').' '.HgDao::REPO_NAME_MAX_LENGTH);
            $c->redirect('/plugins/hg/?action=index&group_id='.$projectId);
            return false;
        }
        $this->systemEventManager->createEvent(
            'HG_REPO_CREATE',
            $projectId.SystemEvent::PARAMETER_SEPARATOR.$repositoryName.SystemEvent::PARAMETER_SEPARATOR.$this->user->getId(),
            SystemEvent::PRIORITY_MEDIUM
        );
        $c->addInfo( $this->getText('actions_create_repo_process') );
        $c->redirect('/plugins/hg/?action=index&group_id='.$projectId);
        return;
    }
    
    public function cloneRepository( $projectId, $forkName, $parentId) {
        
        $c         = $this->getController();
        $projectId = intval($projectId);
        $parentId  = intval($parentId);
        if ( empty($projectId) || empty($forkName) || empty($parentId) ) {
            $c->addError($this->getText('actions_params_error'));            
            return false;
        }
        if ( HgDao::checkName($forkName) === false ) {
            $c->addError( $this->getText('actions_input_format_error').' '.HgDao::REPO_NAME_MAX_LENGTH );
            $c->redirect('/plugins/hg/index.php/'.$projectId.'/view/'.$parentId.'/');
            return false;
        }
        $parentRepo = new HgRepository();
        $parentRepo->setId($parentId);
        try {
            $parentRepo->load();
            if ( !$parentRepo->isInitialized() ) {
                $c->addError( $this->getText('repo_not_initialized') );
                $c->redirect('/plugins/hg/index.php/'.$projectId.'/view/'.$parentId.'/');
                return false;
            }
        } catch ( HgDaoException $e ) {
            $c->addError( $e->getMessage() );
            $c->redirect('/plugins/hg/?action=index&group_id='.$projectId);
            return false;
        }
        $this->systemEventManager->createEvent(
            'HG_REPO_CLONE',
            $projectId.SystemEvent::PARAMETER_SEPARATOR.$forkName.SystemEvent::PARAMETER_SEPARATOR.$parentId.SystemEvent::PARAMETER_SEPARATOR.$this->user->getId(),
            SystemEvent::PRIORITY_MEDIUM
        );
        $c->addInfo( $this->getText('actions_create_repo_process') );
        $c->redirect('/plugins/hg/index.php/'.$projectId.'/view/'.$parentId.'/');
        return;
    }

    public function getProjectRepositoryList($projectId) {
        $projectId = intval($projectId);              
        $dao       = new HgDao();        
        $repositoryList = $dao->getProjectRepositoryList($projectId);        
        $this->addData( array('repository_list'=>$repositoryList) );        
        return true;
    }
    
    //TODO check repo - project?
    public function getRepositoryDetails($projectId, $repositoryId) {
        $c = $this->getController();
        $projectId    = intval($projectId);
        $repositoryId = intval($repositoryId);
        if ( empty($repositoryId) ) {
            $c->addError( $this->getText('actions_params_error') );
            return false;
        }
         
        $repository = new HgRepository();
        $repository->setId($repositoryId);        
        try {
            $repository->load();            
        } catch (HgDaoException $e) {
            $c->addError( $this->getText('actions_repo_not_found') );
            $c->redirect('/plugins/hg/?action=index&group_id='.$projectId);
            return;
        }
        $this->addData( array('repository'=>$repository) );
        return true;
    }

    public function confirmDeletion($projectId, $repoId) {
        $c = $this->getController();
        if ( empty($repoId) ) {
            $c->addError( $this->getText('actions_params_error') );
            $c->redirect('/plugins/hg/?action=index&group_id='.$projectId);
            return false;
        }
        $c->addWarn( $this->getText('confirm_deletion_msg'));
    }

    /**
     * This method allows one to save any repository attribues changes from the web interface.
     * @param <type> $repoId
     * @param <type> $repoAccess
     * @param <type> $repoDescription
     * @return <type>
     */
    public function save( $projectId, $repoId, $repoAccess, $repoDescription ) {
        
        $c = $this->getController();
        if ( empty($repoId) ) {
            $c->addError( $this->getText('actions_params_error') );
            $c->redirect('/plugins/hg/?action=index&group_id='.$projectId);
            return false;
        }
        if ( empty($repoAccess) || empty($repoDescription) ) {
            $c->addError( $this->getText('actions_params_error') );
            $c->redirect('/plugins/hg/index.php/'.$projectId.'/view/'.$repoId.'/');
            return false;
        }        
        $repository = new HgRepository();
        $repository->setId($repoId);
        try {
            $repository->load();
            if ( !empty($repoAccess) && $repository->getAccess() != $repoAccess) {
                $this->systemEventManager->createEvent(
                                              'HG_REPO_ACCESS',
                                               $repoId.SystemEvent::PARAMETER_SEPARATOR.$repoAccess,
                                               SystemEvent::PRIORITY_HIGH
                                            );
                $c->addInfo( $this->getText('actions_repo_access') );
            }
            if ( !empty($repoDescription) ) {
                $repository->setDescription($repoDescription);
            }
        } catch (HgDaoException $e) {
            $c->addError( $this->getText('actions_repo_not_found') );
            $c->redirect('/plugins/hg/?group_id='.$projectId);            
            return false;
        } catch (HgRepositoryException $e1) {
            die('HgRepositoryException');
            $c->addError( $e1->getMessage() );
            return false;
        }

        try {
            $repository->save();
        } catch (HgDaoException $e) {
            $c->addError( $e->getMessage() );             
             $c->redirect('/plugins/hg/index.php/'.$projectId.'/view/'.$repoId.'/');
            return false;
        }
        $c->addInfo( $this->getText('actions_save_repo_process') );
        $c->redirect('/plugins/hg/index.php/'.$projectId.'/view/'.$repoId.'/');
        return;
    }

    /**
     * Internal method called by SystemEvent_PROJECT_IS_PRIVATE
     * @param <type> $projectId
     * @param <type> $isPublic
     * @return <type>
     */
    public static function changeProjectRepositoriesAccess($projectId, $isPrivate) {
        //if the project is private, then no changes may be applied to repositories,
        //in other words only if project is set to private, its repositories have to be set to private
        if ( empty($isPrivate) ) {
            return;
        }
        $dao          = new HgDao();
        $repositories = $dao->getProjectRepositoryList($projectId);
        if ( empty($repositories) ) {
            return false;
        }

        foreach ( $repositories as $repoId=>$repoData ) {
            $r = new HgRepository();
            $r->setId($repoId);
            if ( !$r->exists() ) {
                continue;
            }
            $newAccess = !empty($isPrivate) ? HgRepository::PRIVATE_ACCESS : HgRepository::PUBLIC_ACCESS;
            if ( $r->getAccess() == $newAccess ) {
                continue;
            }
            $r->setAccess( $newAccess );
            $r->changeAccess();
            unset($r);
        }

    }


}


?>
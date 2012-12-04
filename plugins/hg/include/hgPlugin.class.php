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

require_once('common/plugin/Plugin.class.php');
require_once('common/system_event/SystemEvent.class.php');
require_once('HgActions.class.php');

/**
 * HgPlugin
 */
class hgPlugin extends Plugin {


    public function __construct($id) {
        $this->Plugin($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->_addHook('cssfile', 'cssFile', false);
        $this->_addHook('javascript_file', 'jsFile', false);
        $this->_addHook(Event::GET_SYSTEM_EVENT_CLASS, 'getSystemEventClass', false);
        $this->_addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES, 'getReferenceKeywords', false);
        $this->_addHook('get_available_reference_natures', 'getReferenceNatures', false);
        $this->_addHook('SystemEvent_PROJECT_IS_PRIVATE', 'changeProjectRepositoriesAccess', false);
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'HgPluginInfo')) {
            require_once('HgPluginInfo.class.php');
            $this->pluginInfo = new HgPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function siteAdminHooks($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">Hg</a></li>';
    }

    public function cssFile($params) {
        // Only show the stylesheet if we're actually in the Hg pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/hgphp.css" />';
        }
    }

    public function jsFile() {        
        // Only show the javascript if we're actually in the Hg pages.       
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {            
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/hg.js"></script>';
        }
    }

    /**
     *This callback make SystemEvent manager knows about hg plugin System Events
     * @param <type> $params
     */
    public function getSystemEventClass($params) {
        switch($params['type']) {
            case 'HG_REPO_CREATE' :
                require_once(dirname(__FILE__).'/events/SystemEvent_HG_REPO_CREATE.class.php');
                $params['class'] = 'SystemEvent_HG_REPO_CREATE';
                break;
            case 'HG_REPO_CLONE' :
                require_once(dirname(__FILE__).'/events/SystemEvent_HG_REPO_CLONE.class.php');
                $params['class'] = 'SystemEvent_HG_REPO_CLONE';
                break;
            case 'HG_REPO_DELETE' :
                require_once(dirname(__FILE__).'/events/SystemEvent_HG_REPO_DELETE.class.php');
                $params['class'] = 'SystemEvent_HG_REPO_DELETE';
                break;
            case 'HG_REPO_ACCESS':
                require_once(dirname(__FILE__).'/events/SystemEvent_HG_REPO_ACCESS.class.php');
                $params['class'] = 'SystemEvent_HG_REPO_ACCESS';
                break;
            default:
                break;
        }
    }

    public function getReferenceKeywords($params) {
        $params['keywords'] = array_merge($params['keywords'], array('hg') );
    }

    public function getReferenceNatures($params) {
        $params['natures'] = array_merge( $params['natures'],
                array( 'hg_commit'=>array('keyword'=>'hg', 'label'=> $GLOBALS['Language']->getText('plugin_hg', 'reference_commit_nature_key') ) ) );
    }

    public function changeProjectRepositoriesAccess($params) {
        $groupId   = $params[0];
        $isPrivate = $params[1];
        HgActions::changeProjectRepositoriesAccess($groupId, $isPrivate);
    }

    public function process() {
        require_once('Hg.class.php');
        $controler = new Hg($this);
        $controler->process();
    }
}

?>

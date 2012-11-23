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
 * @author    guillaume Storchi
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class PluginActions {

    protected $controller;    

    public function __construct( $controller ) {
        $this->controller = $controller;
        $this->user       = $controller->getUser();
        $this->request    = $controller->getRequest();
    }

    public function getController() {
        return $this->controller;
    }

    public function getData() {
        return $this->controller->getData();
    }

    public function addData($data) {
        $this->controller->addData($data);
    }

    public function check() {
        return true;
    }

    public function process($actionName, $actionParams) {
        if( $this->check() ) {
            return call_user_func_array(array($this,$actionName), $actionParams);
        }
    }
}
?>
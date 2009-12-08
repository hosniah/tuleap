<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'common/dao/include/DataAccessObject.class.php';

class AdminDelegation_UserServiceDao extends DataAccessObject {

    public function __construct(DataAccess $da) {
        parent::__construct($da);
    }

    public function searchAllUsers() {
        $sql = 'SELECT * FROM plugin_admindelegation_service_user';
        return $this->retrieve($sql);
    }

    public function searchUser($userId) {
        $sql = 'SELECT service_id FROM plugin_admindelegation_service_user'.
               ' WHERE user_id = '.$this->da->quoteSmart($userId);
        return $this->retrieve($sql);
    }

    public function searchUserService($userId, $serviceId) {
        $sql = 'SELECT NULL FROM plugin_admindelegation_service_user'.
               ' WHERE user_id = '.$this->da->quoteSmart($userId).
               ' AND service_id = '.$this->da->quoteSmart($serviceId);
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function isUserGranted($userId) {
        $sql = 'SELECT NULL FROM plugin_admindelegation_service_user'.
               ' WHERE user_id = '.$this->da->quoteSmart($userId).
               ' LIMIT 1';
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function addUserService($userId, $serviceId) {
        $sql = 'INSERT INTO plugin_admindelegation_service_user (service_id, user_id)'.
               ' VALUES ('.$this->da->quoteSmart($serviceId).', '.$this->da->quoteSmart($userId).')';
        return $this->update($sql);
    }

    public function removeUserService($userId, $serviceId) {
        $sql = 'DELETE FROM plugin_admindelegation_service_user'.
               ' WHERE user_id = '.$this->da->quoteSmart($userId).
               ' AND service_id = '.$this->da->quoteSmart($serviceId);
        return $this->update($sql);
    }
    
    public function removeUser($userId) {
        $sql = 'DELETE FROM plugin_admindelegation_service_user'.
               ' WHERE user_id = '.$this->da->quoteSmart($userId);
        return $this->update($sql);
    }
}

?>
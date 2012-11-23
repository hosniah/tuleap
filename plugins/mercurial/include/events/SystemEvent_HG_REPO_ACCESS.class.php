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
require_once('common/system_event/SystemEvent.class.php');
require_once(dirname(__FILE__).'/../HgRepository.class.php');
require_once('common/backend/Backend.class.php');
require_once('common/user/UserManager.class.php');


class SystemEvent_HG_REPO_ACCESS  extends SystemEvent {

    public function process() {
        $parameters  = $this->getParametersAsArray();                
        //repo id
        $repositoryId = '';
        if ( !empty($parameters[0]) ) {
            $repositoryId = $parameters[0];
        }
        else {
            $this->error('Missing argument repository id');
            return false;
        }
        //repo access
        $repositoryAccess = '';
        if ( !empty($parameters[1]) ) {
            $repositoryAccess = $parameters[1];
        }
        else {
            $this->error('Missing argument repository access');
            return false;
        }

        //save
        $repository = new HgRepository();
        $repository->setId($repositoryId);
        try {
            $repository->load();
            $repository->setAccess($repositoryAccess);
            $repository->changeAccess();
        } catch (HgDaoException $e) {
            $this->error( $e->getMessage() );
            return false;
        }
        $this->done();
    }

    public function verbalizeParameters($with_link) {
        return  $this->parameters;
    }


}

?>
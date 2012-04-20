<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

class MockTrackerBuilder {
    public function __construct() {
        $this->tracker = mock('Tracker');
    }
    
    public function withId($id) {
        stub($this->tracker)->getId()->returns($id);
        return $this;
    }
    
    public function withName($name) {
        stub($this->tracker)->getName()->returns($name);
        return $this;
    }
    
    public function havingFormElementWithNameAndType($name, $type_or_types) {
        stub($this->tracker)->hasFormElementWithNameAndType($name, $type_or_types)->returns(true);
        return $this;
    }
    
    public function havingNoFormElement($name) {
        stub($this->tracker)->hasFormElementWithNameAndType($name, '*')->returns(false);
        return $this;
    }
    
    public function build() {
        return $this->tracker;
    }
}

function aMockTracker() {
    return new MockTrackerBuilder();
}
?>
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

require_once(dirname(__FILE__).'/../../include/Planning/PlanningFactory.class.php');
require_once(dirname(__FILE__).'/../builders/planning_factory.php');

Mock::generate('Planning');
Mock::generate('PlanningDao');

class PlanningFactoryTest extends TuleapTestCase {
    
    function itDuplicatesPlannings() {
        $dao     = new MockPlanningDao();
        $factory = aPlanningFactory()->withDao($dao)->build();
        
        $group_id = 123;
        
        $sprint_tracker_id      = 1;
        $story_tracker_id       = 2;
        $bug_tracker_id         = 3;
        $faq_tracker_id         = 4;
        $sprint_tracker_copy_id = 5;
        $story_tracker_copy_id  = 6;
        $bug_tracker_copy_id    = 7;
        $faq_tracker_copy_id    = 8;
        
        $tracker_mapping = array($sprint_tracker_id => $sprint_tracker_copy_id,
                                 $story_tracker_id  => $story_tracker_copy_id,
                                 $bug_tracker_id    => $bug_tracker_copy_id,
                                 $faq_tracker_id    => $faq_tracker_copy_id);
        
        $sprint_planning_name = 'Sprint Planning';
        
        $rows = TestHelper::arrayToDar(
            array('id'                  => 1,
                  'name'                => $sprint_planning_name,
                  'group_id'            => 101,
                  'planning_tracker_id' => $sprint_tracker_id,
                  'backlog_tracker_ids' => "$story_tracker_id,$bug_tracker_id")
        );
        
        stub($dao)->searchByPlanningTrackerIds(array_keys($tracker_mapping))->returns($rows);
        
        $dao->expectOnce('createPlanning', array($sprint_planning_name,
                                                 $group_id,
                                                 array($story_tracker_copy_id, $bug_tracker_copy_id),
                                                 $sprint_tracker_copy_id));
        
        $factory->duplicatePlannings($group_id, $tracker_mapping);
    }
    
    function itDoesNothingIfThereAreNoTrackerMappings() {
        $dao     = new MockPlanningDao();
        $factory = aPlanningFactory()->withDao($dao)->build();
        $group_id = 123;
        $empty_tracker_mapping = array();
        
        $dao->expectNever('createPlanning');
        
        $factory->duplicatePlannings($group_id, $empty_tracker_mapping);
    }
    
    function itReturnAnEmptyArrayIfThereIsNoPlanningDefinedForAProject() {
        $dao          = new MockPlanningDao();
        $factory      = aPlanningFactory()->withDao($dao)->build();
        $empty_result = TestHelper::arrayToDar();
        $dao->setReturnValue('searchPlannings', $empty_result);
        
        $this->assertEqual(array(), $factory->getPlannings(123));
    }
    
    function itReturnAllDefinedPlanningsForAProject() {
        $dao          = new MockPlanningDao();
        $factory      = aPlanningFactory()->withDao($dao)->build();
        $empty_result = TestHelper::arrayToDar(
            array('id' => 1, 'name' => 'Release Backlog', 'group_id' => 102),
            array('id' => 2, 'name' => 'Product Backlog', 'group_id' => 102)
        );
        $dao->setReturnValue('searchPlannings', $empty_result);
        
        $expected = array(
            new Planning(1, 'Release Backlog', 102),
            new Planning(2, 'Product Backlog', 102),
        );
        $this->assertEqual($expected, $factory->getPlannings(123));
    }
}

?>
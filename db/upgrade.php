<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Group self selection interface
 *
 * @package    mod
 * @subpackage groupselect
 * @copyright  2008-2011 Petr Skoda (http://skodak.org)
 * @copyright  2014 Tampere University of Technology, P. Pyykkönen (pirkka.pyykkonen ÄT tut.fi)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_groupselect_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2009020600) {
        $table = new xmldb_table('groupselect');

        // Define field timecreated to be added to groupselect
        $field_timecreated_new = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timedue');

        // Conditionally launch add temporary fields
        if (!$dbman->field_exists($table, $field_timecreated_new)) {
            $dbman->add_field($table, $field_timecreated_new);
        }

        // search savepoint reached
        upgrade_mod_savepoint(true, 2009020600, 'groupselect');

    }

    if ($oldversion < 2009030500) {

        // Define field targetgrouping to be added to groupselect
        $table = new xmldb_table('groupselect');
        $field_targetgrouping_new = new xmldb_field('targetgrouping', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'intro');
        // Conditionally launch adding fields
        if (!$dbman->field_exists($table, $field_targetgrouping_new)) {
            $dbman->add_field($table, $field_targetgrouping_new);
        }

        // search savepoint reached
        upgrade_mod_savepoint(true, 2009030500, 'groupselect');

    }

    // ==== Moodle 2.0 upgrade line =====

    if ($oldversion < 2010010100) {
        // Define field introformat to be added to groupselect
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');

        // Launch add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('groupselect', 'introformat', FORMAT_HTML, array());

        // groupselect savepoint reached
        upgrade_mod_savepoint(true, 2010010100, 'groupselect');
    }

    if ($oldversion < 2010010102) {
        $table = new xmldb_table('groupselect');

        // Define field signuptype to be added to groupselect
        $field_signuptype = new xmldb_field('signuptype', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'targetgrouping');

        // Conditionally launch removing fields
        if ($dbman->field_exists($table, $field_signuptype)) {
            $dbman->drop_field($table, $field_signuptype);
        }

        // search savepoint reached
        upgrade_mod_savepoint(true, 2010010102, 'groupselect');

    }

    if ($oldversion < 2011101800) {
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null, 'name');

        // Make text field bigger
        $dbman->change_field_precision($table, $field);

        // savepoint reached
        upgrade_mod_savepoint(true, 2011101800, 'groupselect');
    }
    
    // Group self-formation update
    if ($oldversion < 2014090201) {
        
        // Update module settings table
        $table = new xmldb_table('groupselect');
        $fields[] = new xmldb_field('hidefullgroups', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timemodified');
        $fields[] = new xmldb_field('deleteemptygroups', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'hidefullgroups');     
        $fields[] = new xmldb_field('studentcancreate', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'deleteemptygroups');
        $fields[] = new xmldb_field('minmembers', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'studentcancreate');
        $fields[] = new xmldb_field('assignteachers', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'minmembers');
        $fields[] = new xmldb_field('studentcansetdesc', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'assignteachers');
        $fields[] = new xmldb_field('showassignedteacher', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'studentcansetdesc');

        foreach($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        
        // Add a new table for group passwords
        $table = new xmldb_table('groupselect_passwords');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('password', XMLDB_TYPE_CHAR, '60', null, XMLDB_NOTNULL, null, null, 'groupid');
        $table->add_field('instance_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'password');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        
        if(!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Add a new table for group-teacher relations
        $table = new xmldb_table('groupselect_groups_teachers');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'groupid');
        $table->add_field('instance_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'teacherid');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        
        if(!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
   
    	// search savepoint reached
    	upgrade_mod_savepoint(true, 2014090201, 'groupselect');
    }
   
   if ($oldversion < 2015032500) {
      $table = new xmldb_table('groupselect');
      $field = new xmldb_field('password', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, 'maxmembers');
      if($dbman->table_exists( $table ) and $dbman->field_exists($table, $field)) 
      { 
         
             $dbman->drop_field($table, $field);
      }
      
    	upgrade_mod_savepoint(true, 2015032500, 'groupselect');
    }
    return true;
}

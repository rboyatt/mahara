<?php
/**
 * @package    mahara
 * @subpackage tests
 * @author     Nathan Lewis <nathan.lewis@totaralms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 */

require_once(get_config('docroot') . '/blocktype/activitystream/lib.php');

class ArtefactAccessTest extends MaharaUnitTest {

    private $viewer;
    private $other;
    private $friend12;
    private $friend21;
    private $group;
    private $institution;

    private $conditionstotest;
    private $conditionstotestknownfriends;

    const OBJECT_ID_PUBLIC = 1001;
    const OBJECT_ID_LOGGEDIN = 1002;
    const OBJECT_ID_FRIEND12 = 1003;
    const OBJECT_ID_FRIEND21 = 1004;
    const OBJECT_ID_USR = 1005;
    const OBJECT_ID_GROUP = 1006;
//    const OBJECT_ID_MYGROUPS = 1007;
    const OBJECT_ID_INSTITUTION = 1008;

    private $objectids = array(
            self::OBJECT_ID_PUBLIC,
            self::OBJECT_ID_LOGGEDIN,
            self::OBJECT_ID_FRIEND12,
            self::OBJECT_ID_FRIEND21,
            self::OBJECT_ID_USR,
            self::OBJECT_ID_GROUP,
//            self::OBJECT_ID_MYGROUPS,
            self::OBJECT_ID_INSTITUTION
        );

    private $testcases = array(
            array('public', null, array(self::OBJECT_ID_PUBLIC => 1)),
            array('loggedin', null, array(self::OBJECT_ID_LOGGEDIN => 1)),
            array('friend12', null, array(self::OBJECT_ID_FRIEND12 => 1)),
            array('friend21', null, array(self::OBJECT_ID_FRIEND21 => 1)),
            array('usr', null, array(self::OBJECT_ID_USR => 1)),
            array('group', null, array(self::OBJECT_ID_GROUP => 1)),
//            array('mygroups', null, array(self::OBJECT_ID_GROUP => 1)),
            array('institution', null, array(self::OBJECT_ID_INSTITUTION => 1)),
            array('public', true, array(self::OBJECT_ID_PUBLIC => 1)),
            array('loggedin', true, array(self::OBJECT_ID_LOGGEDIN => 1)),
            array('friends', true, array(self::OBJECT_ID_FRIEND12 => 1, self::OBJECT_ID_FRIEND21 => 1)),
            array('usr', true, array(self::OBJECT_ID_USR => 1)),
            array('group', true, array(self::OBJECT_ID_GROUP => 1)),
//            array('mygroups', true, array(self::OBJECT_ID_MYGROUPS => 1)),
            array('institution', true, array(self::OBJECT_ID_INSTITUTION => 1)),
        );

    /**
     * Shared setUp method.
     */
    public function setUp() {
        parent::setUp();

        $this->institution = $this->create_test_institution(array('name' => 'testinstitution'));

        // Other user.
        $this->other = $this->create_test_user((object)array(
            'username'      => 'other',
            'email'         => 'other@localhost',
            'firstname'     => 'other',
            'lastname'      => 'other',
        ));

        // Friend12.
        $this->friend12 = $this->create_test_user((object)array(
            'username'      => 'friend12',
            'email'         => 'friend12@localhost',
            'firstname'     => 'friend12',
            'lastname'      => 'friend12',
        ));

        // Friend21.
        $this->friend21 = $this->create_test_user((object)array(
            'username'      => 'friend21',
            'email'         => 'friend21@localhost',
            'firstname'     => 'friend21',
            'lastname'      => 'friend21',
        ));

        // Viewer.
        $this->viewer = $this->create_test_user((object)array(
            'username'      => 'viewer',
            'email'         => 'viewer@localhost',
            'firstname'     => 'viewer',
            'lastname'      => 'viewer',
        ), 'testinstitution');

        // They are a member of a group.
        $this->group = $this->create_test_group(array(
            'name' => 'test',
            'grouptype' => 'standard',
            'members' => array($this->viewer => 'member')
        ));

        // Create two friend records so that they can be tested individually.
        execute_sql("INSERT INTO {usr_friend} (usr1, usr2, ctime) VALUES ({$this->viewer}, {$this->friend21}, NOW())");
        execute_sql("INSERT INTO {usr_friend} (usr2, usr1, ctime) VALUES ({$this->viewer}, {$this->friend12}, NOW())");

        // Public.
        execute_sql("INSERT INTO {artefact} (id, artefacttype, container, owner, title, ctime, mtime, atime, authorname)
                VALUES (" . self::OBJECT_ID_PUBLIC . ", 'html', 0, {$this->other}, 'artefactaccesstest', NOW(), NOW(), NOW(), '')");
        execute_sql("INSERT INTO {artefact_access} (artefact, accesstype, ctime)
                VALUES (" . self::OBJECT_ID_PUBLIC . ", 'public', NOW())");

        // Loggedin.
        execute_sql("INSERT INTO {artefact} (id, artefacttype, container, owner, title, ctime, mtime, atime, authorname)
                VALUES (" . self::OBJECT_ID_LOGGEDIN . ", 'html', 0, {$this->other}, 'artefactaccesstest', NOW(), NOW(), NOW(), '')");
        execute_sql("INSERT INTO {artefact_access} (artefact, accesstype, ctime)
                VALUES (" . self::OBJECT_ID_LOGGEDIN . ", 'loggedin', NOW())");

        // Friends12.
        execute_sql("INSERT INTO {artefact} (id, artefacttype, container, owner, title, ctime, mtime, atime, authorname)
                VALUES (" . self::OBJECT_ID_FRIEND12 . ", 'html', 0, {$this->friend12}, 'artefactaccesstest', NOW(), NOW(), NOW(), '')");
        execute_sql("INSERT INTO {artefact_access} (artefact, accesstype, ctime)
                VALUES (" . self::OBJECT_ID_FRIEND12 . ", 'friends', NOW())");

        // Friends21.
        execute_sql("INSERT INTO {artefact} (id, artefacttype, container, owner, title, ctime, mtime, atime, authorname)
                VALUES (" . self::OBJECT_ID_FRIEND21 . ", 'html', 0, {$this->friend21}, 'artefactaccesstest', NOW(), NOW(), NOW(), '')");
        execute_sql("INSERT INTO {artefact_access} (artefact, accesstype, ctime)
                VALUES (" . self::OBJECT_ID_FRIEND21 . ", 'friends', NOW())");

        // Usr.
        execute_sql("INSERT INTO {artefact} (id, artefacttype, container, owner, title, ctime, mtime, atime, authorname)
                VALUES (" . self::OBJECT_ID_USR . ", 'html', 0, {$this->other}, 'artefactaccesstest', NOW(), NOW(), NOW(), '')");
        execute_sql("INSERT INTO {artefact_access} (artefact, usr, ctime)
                VALUES (" . self::OBJECT_ID_USR . ", {$this->viewer}, NOW())");

        // Group.
        execute_sql("INSERT INTO {artefact} (id, artefacttype, container, owner, title, ctime, mtime, atime, authorname)
                VALUES (" . self::OBJECT_ID_GROUP . ", 'html', 0, {$this->other}, 'artefactaccesstest', NOW(), NOW(), NOW(), '')");
        execute_sql("INSERT INTO {artefact_access} (artefact, \"group\", ctime)
                VALUES (" . self::OBJECT_ID_GROUP . ", {$this->group}, NOW())");

        /*
        // My groups.
        execute_sql("INSERT INTO {artefact} (id, artefacttype, container, owner, title, ctime, mtime, atime, authorname)
                VALUES (" . self::OBJECT_ID_MYGROUPS . ", 'html', 0, {$this->other}, 'artefactaccesstest', NOW(), NOW(), NOW(), '')");
        execute_sql("INSERT INTO {artefact_access} (artefact, accesstype, ctime)
                VALUES (" . self::OBJECT_ID_MYGROUPS . ", 'mygroups', NOW())");
        */

        // Institution.
        execute_sql("INSERT INTO {artefact} (id, artefacttype, container, owner, title, ctime, mtime, atime, authorname)
                VALUES (" . self::OBJECT_ID_INSTITUTION . ", 'html', 0, {$this->other}, 'artefactaccesstest', NOW(), NOW(), NOW(), '')");
        execute_sql("INSERT INTO {artefact_access} (artefact, institution, ctime)
                VALUES (" . self::OBJECT_ID_INSTITUTION . ", 'testinstitution', NOW())");

        // Get the things that are going to be tested.
        $this->conditionstotest = get_artefact_access_conditions($this->viewer);
        $this->conditionstotestknownfriends = get_artefact_access_conditions($this->viewer, true);
    }

    /**
     * Public, loggedin, friends, friend12, friend21, usr, group.
     * Variables: Are known friends.
     */
    public function testUntestedArtefactAccessConditions() {
        foreach ($this->conditionstotest as $conditionkey => $condition) {
            $found = false;
            foreach ($this->testcases as $testcase) {
                if ($testcase[0] == $conditionkey && empty($testcase[1])) {
                    $found = true;
                }
            }
            $this->assertEquals(true, $found, "condition: " . $conditionkey . "\nsql: " . $condition['sql']);
        }
        foreach ($this->conditionstotestknownfriends as $condition) {
            $found = false;
            foreach ($this->testcases as $testcase) {
                if ($testcase[0] == $conditionkey && $testcase[1] === true) {
                    $found = true;
                }
            }
            $this->assertEquals(true, $found, "condition: " . $conditionkey . "\nsql: " . $condition['sql']);
        }
    }

    /**
     * Public, loggedin, friends, friend12, friend21, usr, group.
     * Variables: Are known friends.
     */
    public function testGetArtefactAccessConditions() {
        $viewer = $this->viewer;

        foreach ($this->testcases as $testcase) {
            $conditionkey = $testcase[0];
            $knownfriends = $testcase[1];
            $expectedactivityids = $testcase[2];

            if ($knownfriends) {
                $condition = $this->conditionstotestknownfriends[$conditionkey];
            }
            else {
                $condition = $this->conditionstotest[$conditionkey];
            }

            $params = array();
            $sql = "SELECT artefact.id FROM {artefact} artefact " . $condition['sql'] .
                    " WHERE artefact.title = 'artefactaccesstest'";
            foreach($condition['params'] as $accessconditionparam) {
                $params[] = $accessconditionparam;
            }
            $results = get_records_sql_array($sql, $params);

            $this->assertEquals(count($expectedactivityids), count($results),
                    "condition: " . $conditionkey ."\nsql: " . $sql . "\nparams: " . implode(", ", $params));

            if (count($results) == count($expectedactivityids)) {
                foreach ($results as $result) {
                    $this->assertNotEmpty($expectedactivityids[$result->id],
                            "condition: " . $conditionkey . "\nsql: " . $sql . "\nparams: " . implode(", ", $params));
                }
            }
        }
    }

    public function tearDown() {
        execute_sql("DELETE FROM {usr_friend} WHERE usr1 = ? AND usr2 = ?", array($this->viewer, $this->friend21));
        execute_sql("DELETE FROM {usr_friend} WHERE usr2 = ? AND usr1 = ?", array($this->viewer, $this->friend12));
        foreach ($this->objectids as $id) {
            execute_sql("DELETE FROM {artefact_access} WHERE artefact = ?", array($id));
            execute_sql("DELETE FROM {artefact} WHERE id = ?", array($id));
        }
        parent::tearDown();
    }
}
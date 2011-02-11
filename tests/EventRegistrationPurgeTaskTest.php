<?php
/**
 * Tests that the registration purge task works as expected.
 *
 * @package    silverstripe-eventmanagement
 * @subpackage tests
 */
class EventRegistrationPurgeTaskTest extends SapphireTest {

	public static $fixture_file = 'eventmanagement/tests/EventRegistrationPurgeTaskTest.yml';

	/**
	 * @covers EventRegistrationPurgeTask::purgeUnsubmittedRegistrations
	 */
	public function testPurgeTaskDeletesUnsubmittedRegistrations() {
		$task         = new EventRegistrationPurgeTask();
		$unsubmitted1 = $this->objFromFixture('EventRegistration', 'unsubmitted_1');
		$unsubmitted2 = $this->objFromFixture('EventRegistration', 'unsubmitted_2');

		$count  = 'SELECT COUNT(*) FROM "EventRegistration" WHERE "Status" = \'Unsubmitted\'';
		$update = 'UPDATE "EventRegistration" SET "Created" = \'%s\' WHERE "ID" = %d';

		ob_start();

		$task->run(null);
		$this->assertEquals(2, DB::query($count)->value());

		// Update the first registration to be 10 minutes ago, it shouldn't get
		// deleted.
		DB::query(sprintf(
			$update,
			date('Y-m-d H:i:s', sfTime::subtract(time(), 15, sfTime::MINUTE)),
			$unsubmitted1->ID));

		$task->run(null);
		$this->assertEquals(2, DB::query($count)->value());

		// Now update it to 20 minutes ago, one should be deleted.
		DB::query(sprintf(
			$update,
			date('Y-m-d H:i:s', sfTime::subtract(time(), 20, sfTime::MINUTE)),
			$unsubmitted1->ID));

		$task->run(null);
		$this->assertEquals(1, DB::query($count)->value());

		// Now push the second one way into the past.
		$created = sfTime::subtract(time(), 1000, sfTime::DAY);
		DB::query(sprintf(
			$update,
			date('Y-m-d H:i:s', $created),
			$unsubmitted2->ID));

		$task->run(null);
		$this->assertEquals(0, DB::query($count)->value());

		// Ensure the confirmed event is still there.
		$confirmed = DB::query(
			'SELECT COUNT(*) FROM "EventRegistration" WHERE "Status" = \'Confirmed\''
		);
		$this->assertEquals(1, $confirmed->value());

		ob_end_clean();
	}

	/**
	 * @covers EventRegistrationPurgeTask::purgeUnconfirmedRegistrations
	 */
	public function testPurgeTaskCancelsUnconfirmedRegistrations() {
		$task         = new EventRegistrationPurgeTask();
		$unconfirmed1 = $this->objFromFixture('EventRegistration', 'unconfirmed_1');
		$unconfirmed2 = $this->objFromFixture('EventRegistration', 'unconfirmed_2');

		$canceled = 'SELECT COUNT(*) FROM "EventRegistration" WHERE "Status" = \'Canceled\'';
		$update   = 'UPDATE "EventRegistration" SET "Created" = \'%s\' WHERE "ID" = %d';

		ob_start();

		$task->run(null);
		$this->assertEquals(0, DB::query($canceled)->value());

		// Update the first task to be just shy of six hours less than the
		// created date.
		$created = strtotime($unconfirmed1->Created);
		$created = sfTime::subtract($created, 5, sfTime::HOUR);
		DB::query(sprintf($update, date('Y-m-d H:i:s', $created), $unconfirmed1->ID));

		$task->run(null);
		$this->assertEquals(0, DB::query($canceled)->value());

		// Now push it beyond six hours
		DB::query(sprintf(
			$update,
			date('Y-m-d H:i:s', sfTime::subtract($created, 1, sfTime::HOUR)),
			$unconfirmed1->ID));

		$task->run(null);
		$this->assertEquals(1, DB::query($canceled)->value());

		// Now push the second one way back, and check it's also canceled
		$created = sfTime::subtract(time(), 1000, sfTime::DAY);
		DB::query(sprintf(
			$update,
			date('Y-m-d H:i:s', $created),
			$unconfirmed2->ID));

		$task->run(null);
		$this->assertEquals(2, DB::query($canceled)->value());

		// Ensure the confirmed event is still there.
		$confirmed = DB::query(
			'SELECT COUNT(*) FROM "EventRegistration" WHERE "Status" = \'Confirmed\''
		);
		$this->assertEquals(1, $confirmed->value());

		ob_end_clean();
	}

}
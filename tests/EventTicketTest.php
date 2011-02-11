<?php
/**
 * Contains tests for the {@link EventTicket} class.
 *
 * @package    silverstripe-eventmanagement
 * @subpackage tests
 */
class EventTicketTest extends SapphireTest {

	/**
	 * @covers EventTicket::getAvailableForDateTime()
	 */
	public function testGetAvailableForDatetimeWithDates() {
		$ticket = new EventTicket();
		$time   = new RegisterableDateTime();

		// First test making the ticket unavailable with a fixed start date in
		// the past.
		$ticket->StartType = 'Date';
		$ticket->StartDate = $startDate = date('Y-m-d H:i:s', time() + 60);
		$avail = $ticket->getAvailableForDateTime($time);

		$this->assertFalse($avail['available']);
		$this->assertEquals(strtotime($startDate), $avail['available_at']);

		// Then test making it unavailable with a date to start set relative
		// to the datetime start date.
		$ticket->StartType = 'TimeBefore';
		$ticket->StartDays = 2;
		$time->StartDate = date('Y-m-d', time() + 3 * 3600 * 24);
		$time->StartTime = date('H:i:s', time());
		$avail = $ticket->getAvailableForDateTime($time);

		$this->assertFalse($avail['available']);
		$this->assertEquals(time() + 1 * 3600 * 24, $avail['available_at']);

		// Then set it to a valid time and check it's valid.
		$time->StartDate = date('Y-m-d', time() + 1 * 3600 * 24);
		$avail = $ticket->getAvailableForDateTime($time);
		$this->assertTrue($avail['available']);

		// Make it beyond the end date.
		$ticket->EndType = 'Date';
		$ticket->EndDate = date('Y-m-d H:i:s');
		$avail = $ticket->getAvailableForDateTime($time);
		$this->assertFalse($avail['available']);

		// Then set the end date to be relative.
		$ticket->EndType = 'TimeBefore';
		$ticket->EndDays = 1;
		$avail = $ticket->getAvailableForDateTime($time);
		$this->assertFalse($avail['available']);

		// Then make it valid and check it works.
		$ticket->EndDays  = 0;
		$ticket->EndHours = 6;
		$avail = $ticket->getAvailableForDateTime($time);
		$this->assertTrue($avail['available']);
	}

	/**
	 * @covers EventTicket::getAvailableForDateTime()
	 */
	public function testGetAvailableForDatetimeWithQuantity() {
		$ticket = new EventTicket();
		$ticket->StartType = 'Date';
		$ticket->StartDate = date('Y-m-d', time() - 3600 * 24);
		$ticket->EndType   = 'Date';
		$ticket->EndDate   = date('Y-m-d', time() + 3600 * 24);
		$ticket->write();

		$time = new RegisterableDateTime();
		$time->write();

		$ticket->Available = 50;
		$avail = $ticket->getAvailableForDateTime($time);
		$this->assertEquals(50, $avail['available']);

		// Create a registration that consumes some of the tickets.
		$rego = new EventRegistration();
		$rego->Status = 'Valid';
		$rego->TimeID = $time->ID;
		$rego->write();
		$rego->Tickets()->add($ticket, array('Quantity' => 49));

		$avail = $ticket->getAvailableForDateTime($time);
		$this->assertEquals(1, $avail['available']);

		// Then check we can exclude it.
		$avail = $ticket->getAvailableForDateTime($time, $rego->ID);
		$this->assertEquals(50, $avail['available']);

		// Then bump up the quantity so there are no more available.
		$rego->Tickets()->remove($ticket);
		$rego->Tickets()->add($ticket, array('Quantity' => 50));

		$avail = $ticket->getAvailableForDateTime($time);
		$this->assertFalse($avail['available']);
	}

	/**
	 * @covers EventTicket::getSaleEndForDateTime()
	 */
	public function testGetSaleEndForDateTime() {
		$ticket = new EventTicket();
		$time   = new RegisterableDateTime();
		$now    = time();

		$ticket->EndType = 'Date';
		$ticket->EndDate = date('Y-m-d H:i:s', $now);
		$this->assertEquals(
			$now,
			$ticket->getSaleEndForDateTime($time),
			'The correct end time is returned with a fixed date.'
		);

		$ticket->EndType  = 'TimeBefore';
		$ticket->EndDays  = 1;
		$ticket->EndHours = 12;

		$time->StartDate = date('Y-m-d', $now);
		$time->StartTime = date('H:i:s', $now);

		$this->assertEquals(
			$now - 1.5 * 3600 * 24,
			$ticket->getSaleEndForDateTime($time),
			'The correct end time is returned with a relative end date.'
		);
	}

}
<?php
/**
 * Represents a registration to an event.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistration extends DataObject {

	public static $db = array(
		'Name'   => 'Varchar(255)',
		'Email'  => 'Varchar(255)',
		'Status' => 'Enum("Unconfirmed, Valid, Canceled")',
		'Total'  => 'Money',
		'Token'  => 'Varchar(48)'
	);

	public static $has_one = array(
		'Time'   => 'RegisterableDateTime',
		'Member' => 'Member'
	);

	public static $many_many = array(
		'Tickets' => 'EventTicket'
	);

	public static $many_many_extraFields = array(
		'Tickets' => array('Quantity' => 'Int')
	);

	public static $summary_fields = array(
		'Name'            => 'Name',
		'Email'           => 'Email',
		'EventTitle'      => 'Event',
		'DateTimeSummary' => 'Dates And Times'
	);

	protected function onBeforeWrite() {
		if (!$this->isInDB()) {
			$generator = new RandomGenerator();
			$this->Token = $generator->generateHash('sha1');
		}

		parent::onBeforeWrite();
	}

	/**
	 * @return string
	 */
	public function EventTitle() {
		return $this->Time()->EventTitle();
	}

	/**
	 * @return string
	 */
	public function DateTimeSummary() {
		return $this->Time()->Summary();
	}

}
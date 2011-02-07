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

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('Tickets');
		$fields->removeByName('Total');
		$fields->removeByName('Token');
		$fields->removeByName('TimeID');

		$fields->addFieldsToTab('Root.Tickets', array(
			$tickets = new TableListField('Tickets', 'EventTicket', array(
				'Title'        => 'Ticket Title',
				'PriceSummary' => 'Price',
				'Quantity'     => 'Quantity'
			)),
			new ReadonlyField('TotalNice', 'Total', $this->Total->Nice())
		));
		$tickets->setCustomSourceItems($this->Tickets());

		return $fields;
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

	/**
	 * @return string
	 */
	public function Link() {
		return Controller::join_links(
			$this->Time()->Link(), 'registration', $this->ID, '?token=' . $this->Token
		);
	}

}
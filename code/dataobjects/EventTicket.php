<?php
/**
 * A ticket type that can be attached to a registerable event. Each ticket can
 * have a specific quantity available for each event time.
 *
 * @package silverstripe-eventmanagement
 */
class EventTicket extends DataObject {

	public static $db = array(
		'Title'       => 'Varchar(255)',
		'Type'        => 'Enum("Free, Price")',
		'Price'       => 'Money',
		'Description' => 'Text',
		'StartType'   => 'Enum("Date, TimeBefore")',
		'StartDate'   => 'SS_Datetime',
		'StartDays'   => 'Int',
		'StartHours'  => 'Int',
		'StartMins'   => 'Int',
		'EndType'     => 'Enum("Date, TimeBefore")',
		'EndDate'     => 'SS_Datetime',
		'EndDays'     => 'Int',
		'EndHours'    => 'Int',
		'EndMins'     => 'Int',
		'MinTickets'  => 'Int',
		'MaxTickets'  => 'Int'
	);

	public static $has_one = array(
		'Event' => 'RegisterableEvent'
	);

	public static $defaults = array(
		'MinTickets' => 1
	);

	public static $summary_fields = array(
		'Title'        => 'Title',
		'StartSummary' => 'Sales Start',
		'PriceSummary' => 'Price'
	);

	public static $searchable_fields = array(
		'Title',
		'Type'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('StartType');
		$fields->removeByName('StartDate');
		$fields->removeByName('StartDays');
		$fields->removeByName('StartHours');
		$fields->removeByName('StartMins');
		$fields->removeByName('EndType');
		$fields->removeByName('EndDate');
		$fields->removeByName('EndDays');
		$fields->removeByName('EndHours');
		$fields->removeByName('EndMins');

		$fields->dataFieldByName('Price')->setTitle('');
		$fields->insertBefore(new OptionSetField('Type', '', array(
			'Free'  => 'Free ticket',
			'Price' => 'Fixed price ticket'
		)), 'Price');

		foreach (array('Start', 'End') as $type) {
			$fields->addFieldsToTab('Root.Main', array(
				new OptionSetField("{$type}Type", "$type sales at", array(
					'Date'       => 'A specific date and time',
					'TimeBefore' => 'A time before the event starts'
				)),
				$datetime = new DatetimeField("{$type}Date", ''),
				$before = new FieldGroup(
					'',
					new NumericField("{$type}Days", 'Days'),
					new NumericField("{$type}Hours", 'Hours'),
					new NumericField("{$type}Mins", 'Minutes')
				)
			));

			$before->setTitle('');
			$datetime->getDateField()->setConfig('showcalendar', true);
			$datetime->getTimeField()->setConfig('showdropdown', true);
		}

		$fields->addFieldsToTab('Root.Advanced', array(
			new TextareaField('Description', 'Description'),
			new NumericField('MinTickets', 'Minimum tickets per order'),
			new NumericField('MaxTickets', 'Maximum tickets per order')
		));

		return $fields;
	}

	/**
	 * @return FieldSet
	 */
	public function getCMSExtraFields() {
		return new FieldSet(
			new ReadonlyField('Title', 'Title'),
			new NumericField('Available', 'Tickets available')
		);
	}

	/**
	 * @return RequiredFields
	 */
	public function getValidator() {
		return new RequiredFields('Title', 'Type', 'StartType', 'EndType');
	}

	public function getRequirementsForPopup() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('eventmanagement/javascript/EventTicketCms.js');
	}

	/**
	 * Returns the number of tickets available for an event time.
	 *
	 * @param  RegisterableDateTime $time
	 * @return array
	 */
	public function getAvailableForDateTime(RegisterableDateTime $time) {
		if ($this->StartType == 'Date') {
			$start = strtotime($this->StartDate);
		} else {
			$start = $time->getStartTimestamp();
			$start = sfTime::subtract($start, $this->StartDays, sfTime::DAY);
			$start = sfTime::subtract($start, $this->StartHours, sfTime::HOUR);
			$start = sfTime::subtract($start, $this->StartMins, sfTime::MINUTE);
		}

		if ($start >= time()) {
			return array(
				'available'    => false,
				'reason'       => 'Tickets are not yet available.',
				'available_at' => $start);
		}

		if ($this->EndType == 'Date') {
			$end = strtotime($this->EndType);
		} else {
			$end = $time->getStartTimestamp();
			$end = sfTime::subtract($end, $this->EndDays, sfTime::DAY);
			$end = sfTime::subtract($end, $this->EndHours, sfTime::HOUR);
			$end = sfTime::subtract($end, $this->EndMins, sfTime::MINUTE);
		}

		if (time() >= $end) {
			return array(
				'available' => false,
				'reason'    => 'Tickets are no longer available.');
		}

		if (!$quantity = $this->Available) {
			return array('available' => true);
		}

		$booked = new SQLQuery();
		$booked->select('SUM("Quantity")');
		$booked->from('EventRegistration_Tickets');
		$booked->leftJoin('EventRegistration', '"EventRegistration"."ID" = "EventRegistrationID"');
		$booked->where('"EventRegistration"."TimeID"', $time->ID);
		$booked = $booked->execute()->value();

		if ($booked < $quantity) {
			return array('available' => $quantity - $booked);
		} else {
			return array(
				'available' => false,
				'reason'    => 'All tickets have been booked.');
		}
	}

	/**
	 * @return string
	 */
	public function StartSummary() {
		if ($this->StartType == 'Date') {
			return $this->obj('StartDate')->Nice();
		} else {
			return sprintf(
				'%d days, %d hours and %d minutes before event',
				$this->StartDays,
				$this->StartHours,
				$this->StartMins);
		}
	}

	/**
	 * @return string
	 */
	public function PriceSummary() {
		switch ($this->Type) {
			case 'Free':  return 'Free';
			case 'Price': return $this->obj('Price')->Nice();
		}
	}

	/**
	 * @return string
	 */
	public function Summary() {
		$summary = "{$this->Title} ({$this->PriceSummary()})";
		return $summary . ($this->Available ? " ($this->Available available)" : '');
	}

}
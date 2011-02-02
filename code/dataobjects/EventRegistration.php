<?php
/**
 * Represents a registration to an event.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistration extends DataObject {

	public static $db = array(
		'Name'  => 'Varchar(255)',
		'Email' => 'Varchar(255)'
	);

	public static $has_one = array(
		'Time'   => 'RegisterableDateTime',
		'Event'  => 'RegisterableEvent',
		'Member' => 'Member'
	);

	public static $summary_fields = array(
		'Name'         => 'Name',
		'Email'        => 'Email',
		'Event.Title'  => 'Event',
		'DatesSummary' => 'Date(s)',
		'TimesSummary' => 'Time(s)'
	);

	/**
	 * @return FieldSet
	 */
	public function getRegistrationFields() {
		$fields = new FieldSet(
			new TextField('Name', _t('EventManagement.YOURNAME', 'Your Name')),
			new EmailField('Email', _t('EventManagement.EMAILADDR', 'Email Address'))
		);

		$this->extend('updateRegistrationFields', $fields);
		return $fields;
	}

	/**
	 * @return Validator
	 */
	public function getRegistrationValidator() {
		$validator = new RequiredFields('Name', 'Email');
		$this->extend('updateRegistrationValidator', $validator);

		return $validator;
	}

	/**
	 * @return string
	 */
	public function DatesSummary() {
		return $this->Time()->_Dates();
	}

	/**
	 * @return string
	 */
	public function TimesSummary() {
		return $this->Time()->_Times();
	}

}
<?php
/**
 * An instance of an event that a person can register to attend.
 *
 * @package silverstripe-eventmanagement
 */
class RegisterableDateTime extends CalendarDateTime {

	public static $has_many = array(
		'Registrations' => 'EventRegistration'
	);

	/**
	 * @return bool
	 */
	public function canRegister() {
		return true;
	}

	/**
	 * @return FieldSet
	 */
	public function getRegistrationFields() {
		return singleton('EventRegistration')->getRegistrationFields();
	}

	/**
	 * @return Validator
	 */
	public function getRegistrationValidator() {
		return singleton('EventRegistration')->getRegistrationValidator();
	}

}
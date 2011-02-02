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
		'Places' => 'Int'
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
		'Places'       => 'Places',
		'DatesSummary' => 'Date(s)',
		'TimesSummary' => 'Time(s)'
	);

	/**
	 * @return ValidationResult
	 */
	public function validate() {
		$result = new ValidationResult();

		$isMulti   = $this->Event()->MultiplePlaces;
		$maxPlaces = $this->Event()->MaxPlaces;

		if ($isMulti) {
			if (!$this->Places > 0) {
				$result->error(_t(
					'EventRegistration.MUSTSELECTPLACES',
					'You must enter a number of places to register for.'));
			}

			if ($this->Places > $maxPlaces) {
				$result->error(sprintf(_t(
					'EventRegistration.TOOMANYPLACES',
					'You cannot select more than %d places.'), $maxPlaces));
			}
		}

		return $result;
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
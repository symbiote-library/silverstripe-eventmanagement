<?php
/**
 * Represents a registration to an event.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistration extends DataObject {

	public static $db = array(
		'Name'      => 'Varchar(255)',
		'Email'     => 'Varchar(255)',
		'Places'    => 'Int',
		'Confirmed' => 'Boolean',
		'Token'     => 'Varchar(48)'
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

		$isLimited = $this->Event()->LimitedPlaces;
		$isMulti   = $this->Event()->MultiplePlaces;
		$maxPlaces = $this->Event()->MaxPlaces;

		if ($isLimited && $isMulti) {
			if (($this->Time()->getRemainingPlaces() - $this->Places) < 1) {
				$result->error(sprintf(_t(
					'EventRegistration.NOTENOUGHPLACES',
					'There are only %d places remaining, please select a lower number of places.'
					), $this->Time()->getRemainingPlaces()));
			}
		} elseif ($isLimited) {
			if (!$this->Time()->getRemainingPlaces()) {
				$result->error(_t(
					'EventRegistration.NOREMAININGPLACES',
					'There are no remaining places for this event.'));
			}
		}

		if ($isMulti) {
			if (!$this->Places > 0) {
				$result->error(_t(
					'EventRegistration.MUSTSELECTPLACES',
					'You must enter a number of places to register for.'));
			}

			if ($maxPlaces && $this->Places > $maxPlaces) {
				$result->error(sprintf(_t(
					'EventRegistration.TOOMANYPLACES',
					'You cannot select more than %d places.'), $maxPlaces));
			}
		}

		return $result;
	}

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
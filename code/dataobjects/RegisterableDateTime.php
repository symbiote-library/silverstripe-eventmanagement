<?php
/**
 * An instance of an event that a person can register to attend.
 *
 * @package silverstripe-eventmanagement
 */
class RegisterableDateTime extends CalendarDateTime {

	public static $db = array(
		'Capacity' => 'Int'
	);

	public static $has_many = array(
		'Registrations' => 'EventRegistration'
	);

	public static $many_many = array(
		'Tickets' => 'EventTicket'
	);

	public static $many_many_extraFields = array(
		'Tickets' => array('Available' => 'Int')
	);

	public function getDateTimeCMSFields() {
		$fields = parent::getDateTimeCMSFields();

		$fields->removeByName('Registrations');
		$fields->removeByName('Tickets');

		if (!$this->isInDB()) {
			$fields->addFieldToTab('Root.Registration', new LiteralField(
				'RegistrationNote', '<p>You can configure registration once ' .
				'you save for the first time.</p>'
			));
			return $fields;
		}

		$fields->addFieldsToTab('Root.Registration', array(
			new ManyManyPickerField(
				$this, 'Tickets', 'Available Tickets', array(
					'ShowPickedInSearch' => false,
					'ExtraFields'        => 'getCMSExtraFields',
					'ExtraFilter'        => '"EventID" = ' . $this->EventID,
					'Sortable'           => true
				)),
			new NumericField('Capacity', 'Overall event capacity')
		));

		return $fields;
	}

	public function getDateTimeTable($eventID) {
		$table = parent::getDateTimeTable($eventID);
		$table->setPopupSize(560, 650);
		return $table;
	}

	/**
	 * @return FieldSet
	 */
	public function getRegistrationFields() {
		$fields = new FieldSet(
			new TextField('Name', _t('EventManagement.YOURNAME', 'Your name')),
			new EmailField('Email', _t('EventManagement.EMAILADDR', 'Email address'))
		);

		if ($this->LimitedPlaces) {
			$entity    = _t('EventManagement.NPLACESREMAINING', 'There are currently %d places remaining.');
			$remaining = '<p id="PlacesRemaining">' . sprintf($entity, $this->getRemainingPlaces()) . '</p>';

			$fields->insertBefore(new LiteralField('PlacesRemaining', $remaining), 'Name');
		}

		if ($this->Event()->MultiplePlaces) {
			$title = _t('EventManagement.NUMPLACES', 'Number of places');

			if ($max = $this->Event()->MaxPlaces) {
				$range = ArrayLib::valuekey(range(1, $max));
				$fields->push(new DropdownField('Places', $title, $range, 1));
			} else {
				$fields->push(new NumericField('Places', $title, 1));
			}
		}

		if ($member = Member::currentUser()) {
			$fields->dataFieldByName('Name')->setValue($member->getName());
			$fields->makeFieldReadonly('Name');

			$fields->dataFieldByName('Email')->setValue($member->Email);
			$fields->makeFieldReadonly('Email');
		}

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
	 * Notifies the users of any changes made to the event.
	 */
	protected function onAfterWrite() {
		parent::onAfterWrite();

		$email   = $this->Event()->EmailNotifyChanges;
		$changed = $this->getChangedFields();
		$notify  = explode(',', $this->Event()->NotifyChangeFields);
		$notify  = array_intersect_key($changed, array_flip($notify));

		if (!$email || !$changed || !$notify) return;

		$emails = DB::query(sprintf(
			'SELECT "Email", "Name" FROM "EventRegistration" WHERE "TimeID" = %d GROUP BY "Email"',
			$this->ID
		));
		if (!$emails = $emails->map()) return;

		$changed = new DataObjectSet();
		foreach ($notify as $field => $data) {
			$changed->push(new ArrayData(array(
				'Label'  => singleton('EventRegistration')->fieldLabel($field),
				'Before' => $data['before'],
				'After'  => $data['after']
			)));
		}

		$email = new Email();
		$email->setSubject(
			sprintf('Event Details Changed For %s (%s)',
			$this->EventTitle(),
			SiteConfig::current_site_config()->Title));

		$email->setTemplate('EventRegistrationChangeEmail');
		$email->populateTemplate(array(
			'Time'       => $this,
			'SiteConfig' => SiteConfig::current_site_config(),
			'Changed'    => $changed
		));

		// We need to send the email for each registration individually.
		foreach ($emails as $address => $name) {
			$_email = clone $email;
			$_email->setTo($address);
			$_email->populateTemplate(array(
				'Name' => $name
			));

			$_email->send();
		}
	}

	/**
	 * @return string
	 */
	public function Summary() {
		$date = implode(' ', CalendarUtil::getDateString($this->StartDate, $this->EndDate));

		if ($this->is_all_day) {
			return sprintf(_t('EventManagement.DATEALLDAY', '%s (all day)'), $date);
		}

		if (!$this->StartTime) return $date;

		$time = $this->obj('StartTime')->Nice();
		if ($this->EndTime) $time .= ' - ' . $this->obj('EndTime')->Nice();

		return "$date $time";
	}

	/**
	 * @return string
	 */
	public function DetailsLink() {
		return Controller::join_links($this->Event()->Link(), 'details', $this->ID);
	}

}
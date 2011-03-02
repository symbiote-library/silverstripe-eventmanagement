<?php
/**
 * An instance of an event that a person can register to attend.
 *
 * @package silverstripe-eventmanagement
 */
class RegisterableDateTime extends CalendarDateTime {

	public static $db = array(
		'Capacity'      => 'Int',
		'EmailReminder' => 'Boolean',
		'RemindWeeks'   => 'Int',
		'RemindDays'    => 'Int'
	);

	public static $has_many = array(
		'Registrations' => 'EventRegistration'
	);

	public static $many_many = array(
		'Tickets' => 'EventTicket'
	);

	public static $many_many_extraFields = array(
		'Tickets' => array('Available' => 'Int', 'Sort' => 'Int')
	);

	public function getDateTimeCMSFields() {
		$fields = parent::getDateTimeCMSFields();

		$fields->removeByName('Capacity');
		$fields->removeByName('Registrations');
		$fields->removeByName('Tickets');
		$fields->removeByName('EmailReminder');
		$fields->removeByName('RemindWeeks');
		$fields->removeByName('RemindDays');
		$fields->removeByName('PaymentID');
		$fields->removeByName('ReminderJobID');

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
					'Sortable'           => true,
					'PopupHeight'        => 350
				)),
			new NumericField('Capacity', 'Overall event capacity (0 for unlimited)')
		));

		if (class_exists('AbstractQueuedJob')) {
			if ($this->ReminderJobID && $this->ReminderJob()->StepsProcessed) {
				$fields->addFieldToTab('Root.Reminder', new LiteralField(
					'RemindersAlreadySent',
					'<p>Reminder emails have already been sent out.</p>'
				));
			} else {
				$fields->addFieldsToTab('Root.Reminder', array(
					new CheckboxField(
						'EmailReminder',
						'Send registered atendeeds a reminder email?'),
					new FieldGroup(
						'Send the reminder email',
						new NumericField('RemindWeeks', 'Weeks'),
						new NumericField('RemindDays', 'Days'),
						new LiteralField('', 'before the event starts'))
				));
			}
		} else {
			$fields->addFieldsToTab('Root.Reminder', new LiteralField(
				'QueuedJobsReminderNote',
				'<p>Please install the queued jobs module to send reminder emails.</p>'
			));
		}

		return $fields;
	}

	public function getRequirementsForPopup() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('eventmanagement/javascript/RegisterableDateTimeCms.js');
		Requirements::css('eventmanagement/css/RegisterableDateTimeCms.css');
	}

	public function validate() {
		$result   = parent::validate();
		$currency = null;

		// Ensure that if we are sending a reminder email it has an interval
		// to send at.
		if ($this->EmailReminder && !$this->RemindWeeks && !$this->RemindDays) {
			$result->error('You must enter an interval to send the reminder email at.');
		}

		// Ensure that we only have tickets in one currency, since you can't
		// make a payment across currencies.
		foreach ($this->Tickets() as $ticket) {
			if ($ticket->Type == 'Price') {
				$ticketCurr = $ticket->Price->getCurrency();

				if ($ticketCurr && $currency && $ticketCurr != $currency) {
					$result->error(sprintf(
						'You cannot attach tickets with different currencies '
						. 'to one event. You have tickets in both "%s" and "%s".',
						$currency, $ticketCurr));
					return $result;
				}

				$currency = $ticketCurr;
			}
		}

		return $result;
	}

	/**
	 * If an email reminder is set, then this registers it in the queue.
	 */
	protected function onBeforeWrite() {
		parent::onBeforeWrite();

		// If an email reminder has been set then register it with the queued
		// jobs module.
		if (class_exists('AbstractQueuedJob') && $this->EmailReminder) {
			$hasJob       = $this->ReminderJobID;
			$changedStart = $this->isChanged('RemindWeeks') || $this->isChanged('RemindDays');

			if ($hasJob) {
				if (!$changedStart) {
					return;
				} else {
					$this->ReminderJob()->delete();
				}
			}

			$start = $this->getStartTimestamp();
			$start = sfTime::subtract($start, $this->RemindWeeks, sfTime::WEEK);
			$start = sfTime::subtract($start, $this->RemindDays, sfTime::DAY);

			$job = new EventReminderEmailJob($this);
			$srv = singleton('QueuedJobService');
			$this->ReminderJobID = $srv->queueJob($job, date('Y-m-d H:i:s', $start));
		}
	}

	/**
	 * Notifies the users of any changes made to the event.
	 */
	protected function onAfterWrite() {
		parent::onAfterWrite();

		// If any details have changed and the system is configured to send out
		// a notification email then do so.
		$email   = $this->Event()->EmailNotifyChanges;
		$changed = $this->getChangedFields(false, 2);
		$notify  = explode(',', $this->Event()->NotifyChangeFields);
		$notify  = array_intersect_key($changed, array_flip($notify));

		if (!$email || !$changed || !$notify) return;

		$emails = DB::query(sprintf(
			'SELECT "Email", "Name" FROM "EventRegistration" WHERE "TimeID" = %d '
			. 'AND "Status" = \'Valid\' GROUP BY "Email"',
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
			'Changed'    => $changed,
			'Link'       => $this->Link()
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
	 * Returns the overall number of places remaining at this event, TRUE if
	 * there are unlimited places or FALSE if they are all taken.
	 *
	 * @param  int $excludeId A registration ID to exclude from calculations.
	 * @return int|bool
	 */
	public function getRemainingCapacity($excludeId = null) {
		if (!$this->Capacity) return true;

		$taken = new SQLQuery();
		$taken->select('SUM("Quantity")');
		$taken->from('EventRegistration_Tickets');
		$taken->leftJoin('EventRegistration', '"EventRegistration"."ID" = "EventRegistrationID"');

		if ($excludeId) {
			$taken->where('"EventRegistration"."ID"', '<>', $excludeId);
		}

		$taken->where('"Status"', '<>', 'Canceled');
		$taken->where('"EventRegistration"."TimeID"', $this->ID);
		$taken = $taken->execute()->value();

		return ($this->Capacity >= $taken) ? $this->Capacity - $taken : false;
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
	public function Link() {
		return Controller::join_links($this->Event()->Link(), 'details', $this->ID);
	}

}
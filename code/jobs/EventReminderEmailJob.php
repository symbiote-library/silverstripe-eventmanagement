<?php
/**
 * Sends out a reminder email to all people registered to attent an event.
 *
 * @package silverstripe-eventmanagement
 */
class EventReminderEmailJob extends AbstractQueuedJob {

	public function __construct($datetime = null) {
		if ($datetime) {
			$this->datetimeID = $datetime->ID;
			$registrations    = $datetime->Registrations('"Status" = \'Valid\'');
			$this->emails     = $registrations->map('Email', 'Name');
			$this->totalSteps = count($this->emails);
		}
	}

	public function getTitle() {
		return 'Event Registration Reminder Email Job';
	}

	/**
	 * @return RegisterableDateTime
	 */
	public function getDatetime() {
		return DataObject::get_by_id('RegisterableDateTime', $this->datetimeID);
	}

	public function process() {
		$config   = SiteConfig::current_site_config();
		$datetime = $this->getDatetime();
		$emails   = $this->emails;

		if (!count($emails)) {
			$this->isComplete = true;
			return;
		}

		$email = new Email();
		$email->setSubject(sprintf(
			_t('EventManagement.EVENTREMINDERSUBJECT', 'Event Reminder For %s (%s)'),
			$datetime->EventTitle(), $config->Title
		));

		$email->setTemplate('EventReminderEmail');
		$email->populateTemplate(array(
			'SiteConfig' => $config,
			'Datetime'   => $datetime
		));

		foreach ($emails as $addr => $name) {
			$_email = clone $email;

			$_email->setTo($addr);
			$_email->populateTemplate(array('Name' => $name));
			$_email->send();

			unset($emails[$addr]);
			$this->emails = $emails;
			++$this->currentStep;
		}

		if (!count($emails)) {
			$this->isComplete = true;
		}
	}

}
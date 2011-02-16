<?php
/**
 * Adds a relationship between an event date/time and the job that is used to
 * send out reminder emails.
 *
 * @package silverstripe-eventmanagement
 */
class RegisterableDateTimeReminderExtension extends DataObjectDecorator {

	public function extraStatics() {
		return array('has_one' => array(
			'ReminderJob' => 'QueuedJobDescriptor'
		));
	}

}
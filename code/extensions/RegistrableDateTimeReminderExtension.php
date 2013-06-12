<?php
/**
 * Adds a relationship between an event date/time and the job that is used to
 * send out reminder emails.
 */
class RegistrableDateTimeReminderExtension extends DataExtension {

	private static $has_one = array(
		'ReminderJob' => 'QueuedJobDescriptor'
	);

}

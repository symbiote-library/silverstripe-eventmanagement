<?php
/**
 * @package silverstripe-eventmanagement
 */

// Since the payment module is an option dependency, we need to link
// registrations to payments via a conditional decorator.
if (class_exists('Payment')) {
	Object::add_extension('EventRegistration', 'EventRegistrationPaymentExtension');
}

if (class_exists('AbstractQueuedJob')) {
	Object::add_extension('RegisterableDateTime', 'RegisterableDateTimeReminderExtension');
}

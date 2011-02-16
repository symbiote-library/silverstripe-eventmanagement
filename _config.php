<?php
/**
 * @package silverstripe-eventmanagement
 */

if (!class_exists('Calendar')) {
	throw new Exception('The Event Management module requires the Event Calendar module.');
}

if (!class_exists('MultiForm')) {
	throw new Exception('The Event Management module requires the MultiForm module.');
}

if (!class_exists('ItemSetField')) {
	throw new Exception('The Event Management module requires the ItemSetField module.');
}

// Since the payment module is an option dependency, we need to link
// registrations to payments via a conditional decorator.
if (class_exists('Payment')) {
	Object::add_extension('EventRegistration', 'EventRegistrationPaymentExtension');
}

if (class_exists('AbstractQueuedJob')) {
	Object::add_extension('RegisterableDateTime', 'RegisterableDateTimeReminderExtension');
}
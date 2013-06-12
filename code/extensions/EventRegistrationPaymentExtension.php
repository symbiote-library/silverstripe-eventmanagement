<?php
/**
 * Adds a payment relationship to the event registration object.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistrationPaymentExtension extends DataExtension {

	private static $has_one = array(
		'Payment' => 'Payment'
	);

}
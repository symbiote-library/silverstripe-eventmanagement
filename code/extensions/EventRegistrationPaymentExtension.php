<?php
/**
 * Adds a payment relationship to the event registration object.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistrationPaymentExtension extends DataObjectDecorator {

	public function extraStatics() {
		return array('has_one' => array(
			'Payment' => 'Payment'
		));
	}

}
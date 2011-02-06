<?php
/**
 * Uses the payment module to allow the user to choose an option to pay for
 * their tickets, then validates the payment.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterPaymentStep extends MultiFormStep {

	public static $has_one = array(
		'Registration' => 'EventRegistration',
		'Payment'      => 'Payment'
	);

	public static $is_final_step = true;

	public function getTitle() {
		return 'Payment';
	}

	/**
	 * Returns this step's data merged with the tickets from the previous step.
	 *
	 * @return array
	 */
	public function loadData() {
		$data    = parent::loadData();
		$tickets = $this->getForm()->getSavedStepByClass('EventRegisterTicketsStep');
		$tickets = $tickets->loadData();

		$data['Tickets'] = $tickets['Tickets'];
		return $data;
	}

	public function getFields() {
		if (!class_exists('Payment')) throw new Exception(
			'Please install the Payment module to accept event payments.'
		);

		$datetime = $this->getForm()->getController()->getDateTime();
		$tickets  = $this->getForm()->getSavedStepByClass('EventRegisterTicketsStep');
		$total    = $tickets->getTotal();

		$table = new EventRegistrationTicketsTableField('Tickets', $datetime);
		$table->setReadonly(true);
		$table->setShowUnavailableTickets(false);
		$table->setShowUnselectedTickets(false);
		$table->setTotal($total);

		Requirements::customScript(Payment::combined_form_requirements());
		$payment = Payment::combined_form_fields($total->Nice());

		return new FieldSet(
			new LiteralField('ConfirmTicketsNote',
				'<p>Please confirm the tickets you wish to purchase:</p>'),
			$table,
			new FieldGroup($payment)
		);
	}

	public function getValidator() {
		return new RequiredFields('PaymentMethod');
	}

	public function validateStep($data, $form) {
		$payment = $data['PaymentMethod'];
		$tickets = $this->getForm()->getSavedStepByClass('EventRegisterTicketsStep');
		$total   = $tickets->getTotal();

		if (!is_subclass_of($payment, 'Payment')) {
			return false;
		}

		$payment = new $payment();
		$payment->Amount = $total;
		$payment->write();

		$result = $payment->processPayment($data, $form);

		if (!$result->isSuccess()) {
			$form->sessionMessage($result->getValue(), 'required');
			return false;
		}

		// Write an empty registration object so we have an ID to reference the
		// payment against. This will be populated in the form's finish() method.
		$registration = new EventRegistration();
		$registration->Status = 'Valid';
		$registration->write();

		$payment->PaidForClass = 'EventRegistration';
		$payment->PaidForID    = $registration->ID;
		$payment->write();

		$this->RegistrationID = $registration->ID;
		$this->PaymentID      = $payment->ID;

		return true;
	}

}
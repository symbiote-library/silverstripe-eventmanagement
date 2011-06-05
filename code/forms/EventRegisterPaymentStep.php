<?php
/**
 * Uses the payment module to allow the user to choose an option to pay for
 * their tickets, then validates the payment.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterPaymentStep extends MultiFormStep {

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
		$session  = $this->getForm()->getSession();
		$tickets  = $this->getForm()->getSavedStepByClass('EventRegisterTicketsStep');
		$total    = $tickets->getTotal();

		$table = new EventRegistrationTicketsTableField('Tickets', $datetime);
		$table->setReadonly(true);
		$table->setExcludedRegistrationId($session->RegistrationID);
		$table->setShowUnavailableTickets(false);
		$table->setShowUnselectedTickets(false);
		$table->setTotal($total);

		Requirements::customScript(Payment::combined_form_requirements());
		$payment = Payment::combined_form_fields($total->Nice());

		$fields = new FieldSet(
			new LiteralField('ConfirmTicketsNote',
				'<p>Please confirm the tickets you wish to purchase:</p>'),
			$table,
			new FieldGroup($payment)
		);

		$this->extend('updateFields', $fields);
		return $fields;
	}

	public function getValidator() {
		$validator = new RequiredFields('PaymentMethod');
		$this->extend('updateValidator', $validator);
		return $validator;
	}

	public function validateStep($data, $form) {
		Session::set("FormInfo.{$form->FormName()}.data", $form->getData());

		$payment = $data['PaymentMethod'];
		$tickets = $this->getForm()->getSavedStepByClass('EventRegisterTicketsStep');
		$total   = $tickets->getTotal();

		$registration = $this->form->getSession()->getRegistration();

		if (!is_subclass_of($payment, 'Payment')) {
			return false;
		}

		$payment = new $payment();
		$payment->Amount       = $total;
		$payment->PaidForClass = 'EventRegistration';
		$payment->PaidForID    = $registration->ID;
		$payment->PaidBy       = Member::currentUserID();
		$payment->write();

		$registration->PaymentID = $payment->ID;
		$registration->write();

		$result = $payment->processPayment($data, $form);

		if ($result->isProcessing()) {
			throw new SS_HTTPResponse_Exception($result->getValue());
		}

		if (!$result->isSuccess()) {
			$form->sessionMessage($result->getValue(), 'required');
			return false;
		}

		// Write an empty registration object so we have an ID to reference the
		// payment against. This will be populated in the form's finish() method.
		$registration->Status = 'Valid';
		$registration->write();

		Session::set(
			"EventRegistration.{$registration->ID}.message",
			strip_tags($payment->Message)
		);

		return true;
	}

}
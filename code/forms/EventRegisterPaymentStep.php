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
		
		// let's see if we're loading immediately after a payment, in which 
		// case we want to redirect straight out. 
		$registration = $this->form->getSession()->getRegistration();
		$paymentID = Session::get('PaymentID');
		
		if ($registration && $paymentID) {
			$payment = Payment::get()->byID($paymentID);
			if ($this->checkPayment($registration, $payment)) {
				Controller::curr()->redirect($registration->Link());
			}
		}

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

		// the following 2 methods are not in the updated payment module
		// so ignore them as we will be handling this in the extension anyway
		//Requirements::customScript(Payment::combined_form_requirements());

		// add the payment field via the extension
		//$payment = Payment::combined_form_fields($total->Nice());
		
		// we will replace this field in the extension
		$fields = new FieldList(
			new LiteralField('ConfirmTicketsNote',
				'<p>Please confirm the tickets you wish to purchase:</p>'),
			$table
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

		$data['Currency'] = $total->Currency;
		$data['Amount'] = $total->Amount;
		
		$processor = PaymentFactory::factory($payment);
		
		try {
			$link = $registration->Link();
			$link = $this->Link();
			// Set the url to redirect to after the payment is completed, e.g.
			$processor->setRedirectURL(Director::absoluteURL($link));
			// Process the payment 
			$processor->capture($data);
		} catch (Exception $e) {
			// Most likely due to connection cannot be extablished or validation fails
			return false;
		}

		$result = $processor->gateway->getValidationResult();
		$controller = Controller::curr();
		/* @var $controller Controller */
		if ($controller->redirectedTo()) {
			return true;
		}

		if (!$result->valid()) {
			$form->sessionMessage($result->message(), 'required');
			return false;
		}

		$this->checkPayment($registration);
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
	
	/**
	 * Check whether payment for the given registration succeeded. Returns true if so
	 * 
	 * @param type $registration
	 */
	public function checkPayment($registration, $payment = null) {
		if (!$payment) {
			$paymentID = Session::get('PaymentID');
			if ($paymentID) {
				$payment = Payment::get()->byID($paymentID);
			}
		}

		$paid = false;
		if ($payment) {
			$payment->PaidForClass = 'EventRegistration';
			$payment->PaidForID    = $registration->ID;
			$payment->PaidBy       = Member::currentUserID();
			$payment->write();

			$registration->PaymentID = $payment->ID;
			if ($payment->Status == PaymentGateway_Result::SUCCESS) {
				$registration->Status = 'Valid';
				$paid = true;
				$registration->extend('onPaymentConfirmed', $payment->Status);
				Session::clear('PaymentID');
			}
			$registration->write();
		}
		
		return $paid;
	}
}

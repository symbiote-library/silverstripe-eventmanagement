<?php
/**
 * A form step that gets the user to select the tickets they wish to purchase,
 * as well as enter their details.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterTicketsStep extends MultiFormStep {

	public function getTitle() {
		return 'Event Tickets';
	}

	/**
	 * @return string
	 */
	public function getNextStep() {
		if ($this->getTotal()->getAmount() > 0) {
			return 'EventRegisterPaymentStep';
		} else {
			return 'EventRegisterFreeConfirmationStep';
		}
	}

	/**
	 * Returns the total sum of all the tickets the user is purchasing.
	 *
	 * @return Money
	 */
	public function getTotal() {
		$amount   = 0;
		$currency = null;
		$data     = $this->loadData();

		if (isset($data['Tickets'])) {
			foreach ($data['Tickets'] as $id => $quantity) {
				$ticket = DataObject::get_by_id('EventTicket', $id);
				$price  = $ticket->obj('Price');

				if ($ticket->Type == 'Free' || !$quantity) {
					continue;
				}

				$amount  += $price->getAmount() * $quantity;
				$currency = $price->getCurrency();
			}
		}

		return DBField::create('Money', array(
			'Amount'   => $amount,
			'Currency' => $currency
		));
	}

	public function getFields() {
		$datetime = $this->getForm()->getController()->getDateTime();
		$session  = $this->getForm()->getSession();

		$fields = new FieldSet(
			$tickets = new EventRegistrationTicketsTableField('Tickets', $datetime)
		);
		$tickets->setExcludedRegistrationId($session->RegistrationID);

		if ($member = Member::currentUser()) {
			$fields->push(new ReadonlyField('Name', 'Your name', $member->getName()));
			$fields->push(new ReadonlyField('Email', 'Email address', $member->Email));
		} else {
			$fields->push(new TextField('Name', 'Your name'));
			$fields->push(new EmailField('Email', 'Email address'));
		}

		$this->extend('updateFields', $fields);
		return $fields;
	}

	public function getValidator() {
		$validator = new RequiredFields('Name', 'Email');
		$this->extend('updateValidator', $validator);
		return $validator;
	}

	public function validateStep($data, Form $form) {
		Session::set("FormInfo.{$form->FormName()}.data", $form->getData());

		$datetime = $this->getForm()->getController()->getDateTime();
		$session  = $this->getForm()->getSession();
		$data     = $form->getData();
		$has      = false;

		if ($datetime->Event()->OneRegPerEmail) {
			if (Member::currentUserID()) {
				$email = Member::currentUser()->Email;
			} else {
				$email = $data['Email'];
			}

			$existing = DataObject::get_one('EventRegistration', sprintf(
				'"Email" = \'%s\' AND "Status" <> \'Canceled\' AND "TimeID" = %d',
				Convert::raw2sql($email), $datetime->ID
			));

			if ($existing) {
				$form->addErrorMessage(
					'Email',
					'A registration for this email address already exists',
					'required');
				return false;
			}
		}

		// Ensure that the entered ticket data is valid.
		if (!$this->form->validateTickets($data['Tickets'], $form)) {
			return false;
		}

		// Finally add the tickets to the actual registration.
		$registration = $this->form->getSession()->getRegistration();
		$hasLimit     = (bool) $this->form->getController()->getDateTime()->Event()->RegistrationTimeLimit;

		if ($hasLimit && !$registration->isInDB()) {
			$registration->write();
		}

		$total = $this->getTotal();
		$registration->Total->setCurrency($total->getCurrency());
		$registration->Total->setAmount($total->getAmount());

		$registration->Name  = $data['Name'];
		$registration->Email = $data['Email'];
		$registration->write();

		$registration->Tickets()->removeAll();

		foreach ($data['Tickets'] as $id => $quantity) {
			if ($quantity) {
				$registration->Tickets()->add($id, array('Quantity' => $quantity));
			}
		}

		return true;
	}

}
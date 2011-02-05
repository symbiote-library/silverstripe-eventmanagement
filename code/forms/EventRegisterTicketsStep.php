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
			return 'EventRegisterSummaryStep';
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

				if (!$price->hasValue()) continue;

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

		$fields = new FieldSet(
			new EventRegistrationTicketsTableField('Tickets', $datetime)
		);

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
		$datetime = $this->getForm()->getController()->getDateTime();
		$data     = $form->getData();
		$tickets  = $data['Tickets'];
		$has      = false;

		foreach ($tickets as $id => $quantity) {
			if (!$quantity) {
				continue;
			}

			if (!ctype_digit($quantity)) {
				$form->addErrorMessage(
					'Tickets',
					'Please only enter numerical amounts for ticket quantities.',
					'bad');
				return false;
			}

			$ticket = $datetime->Tickets('"EventTicket"."ID" = ' . (int) $id);

			if (!$ticket = $ticket->First()) {
				$form->addErrorMessage(
					'Tickets', 'An invalid ticket ID was entered.', 'bad');
				return false;
			}

			$avail = $ticket->getAvailableForDateTime($datetime);
			$avail = $avail['available'];

			if (!$avail) {
				$form->addErrorMessage(
					'Tickets',
					sprintf('%s is currently not available.', $ticket->Title),
					'bad');
				return false;
			}

			if (is_int($avail) && $avail < $quantity) {
				$form->addErrorMessage(
					'Tickets',
					sprintf('There are only %d of "%s" available.', $avail, $ticket->Title),
					'bad');
				return false;
			}

			if ($ticket->MinTickets && $quantity < $ticket->MinTickets) {
				$form->addErrorMessage('Tickets',sprintf(
					'You must purchase at least %d of "%s".',
					$ticket->MinTickets, $ticket->Title), 'bad');
				return false;
			}

			if ($ticket->MaxTickets && $quantity > $ticket->MaxTickets) {
				$form->addErrorMessage('Tickets', sprintf(
					'You can only purchase at most %d of "%s".',
					$ticket->MaxTickets, $ticket->Title), 'bad');
				return false;
			}

			$has = true;
		}

		if (!$has) {
			$form->addErrorMessage(
				'Tickets', 'Please select at least one ticket to purchase.', 'bad');
			return false;
		}

		return true;
	}

}
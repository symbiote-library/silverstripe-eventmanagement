<?php
/**
 * A form for registering for events which collects the desired tickets and
 * basic user details, then requires the user to pay if needed, then finally
 * displays a registration summary.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterForm extends MultiForm {

	public static $start_step = 'EventRegisterTicketsStep';

	public function __construct($controller, $name) {
		$this->controller = $controller;
		$this->name       = $name;

		parent::__construct($controller, $name);

		if ($expires = $this->getExpiryDateTime()) {
			Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
			Requirements::add_i18n_javascript('eventmanagement/javascript/lang');
			Requirements::javascript('eventmanagement/javascript/EventRegisterForm.js');

			$message = _t('EventManagement.PLEASECOMPLETEREGWITHIN',
				'Please complete your registration within %s. If you do not,'
				. ' the places that are on hold for you will be released to'
				. ' others. You have %s remaining');

			$remain = strtotime($expires->getValue()) - time();
			$hours  = floor($remain / 3600);
			$mins   = floor(($remain - $hours * 3600) / 60);
			$secs   = $remain - $hours * 3600 - $mins * 60;

			$remaining = sprintf(
				'<span id="registration-countdown">%s:%s:%s</span>',
				str_pad($hours, 2, '0', STR_PAD_LEFT),
				str_pad($mins, 2, '0', STR_PAD_LEFT),
				str_pad($secs, 2, '0', STR_PAD_LEFT)
			);

			$field = new LiteralField('CompleteRegistrationWithin', sprintf(
				"<p id=\"complete-registration-within\">$message</p>",
				$expires->TimeDiff(), $remaining));

			$this->fields->insertAfter($field, 'Tickets');
		}
	}

	/**
	 * @return SS_Datetime
	 */
	public function getExpiryDateTime() {
		if ($this->getSession()->RegistrationID) {
			$created = strtotime($this->getSession()->Registration()->Created);
			$limit   = $this->controller->getDateTime()->Event()->RegistrationTimeLimit;

			if ($limit) return DBField::create('SS_Datetime', $created + $limit);
		}
	}

	/**
	 * Handles validating the final step and writing the tickets data to the
	 * registration object.
	 */
	public function finish($data, $form) {
		parent::finish($data, $form);

		$step         = $this->getCurrentStep();
		$datetime     = $this->getController()->getDateTime();
		$registration = $this->session->getRegistration();
		$ticketsStep  = $this->getSavedStepByClass('EventRegisterTicketsStep');
		$tickets      = $ticketsStep->loadData();

		// Check that the requested tickets are still available.
		if (!$this->validateTickets($tickets['Tickets'], $form)) {
			Session::set("FormInfo.{$form->FormName()}.data", $form->getData());
			Director::redirectBack();
			return false;
		}

		// Validate the final step.
		if (!$step->validateStep($data, $form)) {
			Session::set("FormInfo.{$form->FormName()}.data", $form->getData());
			Director::redirectBack();
			return false;
		}

		// Reload the first step fields into a form, then save it into the
		// registration object.
		$ticketsStep->setForm($form);
		$fields = $ticketsStep->getFields();

		$form = new Form($this, '', $fields, new FieldSet());
		$form->loadDataFrom($tickets);
		$form->saveInto($registration);

		if ($member = Member::currentUser()) {
			$registration->Name  = $member->getName();
			$registration->Email = $member->Email;
		}

		$registration->TimeID   = $datetime->ID;
		$registration->MemberID = Member::currentUserID();

		$total = $ticketsStep->getTotal();
		$registration->Total->setCurrency($total->getCurrency());
		$registration->Total->setAmount($total->getAmount());

		foreach ($tickets['Tickets'] as $id => $quantity) {
			if ($quantity) {
				$registration->Tickets()->add($id, array('Quantity' => $quantity));
			}
		}

		$registration->write();
		$this->session->delete();

		// If the registrations is already valid, then send a details email.
		if ($registration->Status == 'Valid') {
			EventRegistrationDetailsEmail::factory($registration)->send();
		}

		$this->extend('onRegistrationComplete', $registration);

		return Director::redirect(Controller::join_links(
			$datetime->Event()->Link(),
			'registration',
			$registration->ID,
			'?token=' . $registration->Token));
	}

	/**
	 * Validates that the tickets requested are available and valid.
	 *
	 * @param  array $tickets A map of ticket ID to quantity.
	 * @param  Form  $form
	 * @return bool
	 */
	public function validateTickets($tickets, $form) {
		$datetime = $this->controller->getDateTime();
		$session  = $this->getSession();

		// First check we have at least one ticket.
		if (!array_sum($tickets)) {
			$form->addErrorMessage(
				'Tickets',
				'Please select at least one ticket to purchase.',
				'required');
			return false;
		}

		// Loop through each ticket and check that the data entered is valid
		// and they are available.
		foreach ($tickets as $id => $quantity) {
			if (!$quantity) {
				continue;
			}

			if (!is_int($quantity) && !ctype_digit($quantity)) {
				$form->addErrorMessage(
					'Tickets',
					'Please only enter numerical amounts for ticket quantities.',
					'required');
				return false;
			}

			$ticket = $datetime->Tickets('"EventTicket"."ID" = ' . (int) $id);

			if (!$ticket = $ticket->First()) {
				$form->addErrorMessage(
					'Tickets', 'An invalid ticket ID was entered.', 'required');
				return false;
			}

			$avail = $ticket->getAvailableForDateTime($datetime, $session->RegistrationID);
			$avail = $avail['available'];

			if (!$avail) {
				$form->addErrorMessage(
					'Tickets',
					sprintf('%s is currently not available.', $ticket->Title),
					'required');
				return false;
			}

			if (is_int($avail) && $avail < $quantity) {
				$form->addErrorMessage(
					'Tickets',
					sprintf('There are only %d of "%s" available.', $avail, $ticket->Title),
					'required');
				return false;
			}

			if ($ticket->MinTickets && $quantity < $ticket->MinTickets) {
				$form->addErrorMessage('Tickets',sprintf(
					'You must purchase at least %d of "%s".',
					$ticket->MinTickets, $ticket->Title), 'required');
				return false;
			}

			if ($ticket->MaxTickets && $quantity > $ticket->MaxTickets) {
				$form->addErrorMessage('Tickets', sprintf(
					'You can only purchase at most %d of "%s".',
					$ticket->MaxTickets, $ticket->Title), 'required');
				return false;
			}
		}

		// Then check the sum of the quantities does not exceed the overall
		// event capacity.
		if ($datetime->Capacity) {
			$avail   = $datetime->getRemainingCapacity($session->RegistrationID);
			$request = array_sum($tickets);

			if ($request > $avail) {
				$message = sprintf(
					'The event only has %d overall places remaining, but you '
					. 'have requested a total of %d places. Please select a '
					. 'lower number.',
					$avail, $request
				);
				$form->addErrorMessage('Tickets', $message, 'required');
				return false;
			}
		}

		return true;
	}

	protected function setSession() {
		$this->session = $this->getCurrentSession();

		// If there was no session found, create a new one instead
		if(!$this->session) {
			$this->session = new EventRegisterFormSession();
			$this->session->setForm($this);
			$this->session->write();
		} else {
			$this->session->setForm($this);
		}

		// Create encrypted identification to the session instance if it doesn't exist
		if(!$this->session->Hash) {
			$this->session->Hash = sha1($this->session->ID . '-' . microtime());
			$this->session->write();
		}
	}

}

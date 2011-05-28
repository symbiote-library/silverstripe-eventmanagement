<?php
/**
 * Gets the user to confirm that their ticket details are correct, and sends
 * a validation email if it is required.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterFreeConfirmationStep extends MultiFormStep {

	public static $is_final_step = true;

	public function getTitle() {
		return 'Confirmation';
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
		$datetime = $this->getForm()->getController()->getDateTime();
		$session  = $this->getForm()->getSession();
		$tickets  = $this->getForm()->getSavedStepByClass('EventRegisterTicketsStep');
		$total    = $tickets->getTotal();

		$table = new EventRegistrationTicketsTableField('Tickets', $datetime);
		$table->setReadonly(true);
		$table->setExcludedRegistrationId($session->RegistrationID);
		$table->setShowUnavailableTickets(false);
		$table->setShowUnselectedTickets(false);
		$table->setForceTotalRow(true);
		$table->setTotal($total);

		$fields = new FieldSet(
			new LiteralField('ConfirmTicketsNote',
				'<p>Please confirm the tickets you wish to register for:</p>'),
			$table
		);

		$this->extend('updateFields', $fields);
		return $fields;
	}

	public function getValidator() {
		$validator = new RequiredFields();
		$this->extend('updateValidator', $validator);
		return $validator;
	}

	/**
	 * This does not actually perform any validation, but just creates the
	 * initial registration object.
	 */
	public function validateStep($data, $form) {
		$form         = $this->getForm();
		$datetime     = $form->getController()->getDateTime();
		$confirmation = $datetime->Event()->RegEmailConfirm;
		$registration = $this->getForm()->getSession()->getRegistration();

		// If we require email validation for free registrations, then send
		// out the email and mark the registration. Otherwise immediately
		// mark it as valid.
		if ($confirmation) {
			$email   = new Email();
			$config  = SiteConfig::current_site_config();

			$registration->TimeID = $datetime->ID;
			$registration->Status = 'Unconfirmed';
			$registration->write();

			if (Member::currentUserID()) {
				$details = array(
					'Name'  => Member::currentUser()->getName(),
					'Email' => Member::currentUser()->Email
				);
			} else {
				$details = $form->getSavedStepByClass('EventRegisterTicketsStep');
				$details = $details->loadData();
			}

			$link = Controller::join_links(
				$this->getForm()->getController()->Link(),
				'confirm', $registration->ID, '?token=' . $registration->Token
			);

			$regLink = Controller::join_links(
				$datetime->Event()->Link(), 'registration', $registration->ID,
				'?token=' . $registration->Token
			);

			$email->setTo($details['Email']);
			$email->setSubject(sprintf(
				'Confirm Registration For %s (%s)', $datetime->EventTitle(), $config->Title
			));

			$email->setTemplate('EventRegistrationConfirmationEmail');
			$email->populateTemplate(array(
				'Name'         => $details['Name'],
				'Registration' => $registration,
				'RegLink'      => $regLink,
				'Time'         => $datetime,
				'SiteConfig'   => $config,
				'ConfirmLink'  => Director::absoluteURL($link)
			));

			$email->send();

			Session::set(
				"EventRegistration.{$registration->ID}.message",
				$datetime->Event()->EmailConfirmMessage
			);
		} else {
			$registration->Status = 'Valid';
			$registration->write();
		}

		return true;
	}

}
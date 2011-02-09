<?php
/**
 * A multiform session that has a registration object attached to it, which is
 * written to the database if place holding during the registration process
 * is enabled.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegisterFormSession extends MultiFormSession {

	public static $has_one = array(
		'Registration' => 'EventRegistration'
	);

	protected $form;
	protected $registration;

	/**
	 * @param EventRegisterForm $form
	 */
	public function setForm(EventRegisterForm $form) {
		$this->form = $form;
	}

	/**
	 * @return EventRegistration
	 */
	public function getRegistration() {
		if ($this->registration) {
			return $this->registration;
		}

		if ($this->RegistrationID) {
			return $this->registration = $this->Registration();
		} else {
			$this->registration = new EventRegistration();
			$this->registration->TimeID = $this->form->getController()->getDateTime()->ID;
			$this->registration->Status = 'Unsubmitted';

			return $this->registration;
		}
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();

		if (!$this->form->getController()->getDateTime()->Event()->RegistrationTimeLimit) {
			return;
		}

		$isInDb     = $this->getRegistration()->isInDB();
		$hasTickets = (bool) count($this->getRegistration()->Tickets());

		if ($isInDb || $hasTickets) {
			$this->getRegistration()->write();
		}

		$this->RegistrationID = $this->getRegistration()->ID;
	}

}
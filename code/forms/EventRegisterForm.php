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
	}

	/**
	 * Handles validating the final step and writing the tickets data to the
	 * registration object.
	 */
	public function finish($data, $form) {
		parent::finish($data, $form);

		$step     = $this->getCurrentStep();
		$datetime = $this->getController()->getDateTime();

		// First validate the final step.
		if (!$step->validateStep($data, $form)) {
			Session::set("FormInfo.{$form->FormName()}.data", $form->getData());
			Director::redirectBack();
			return false;
		}

		$registration = $step->getRegistration();
		$ticketsStep  = $this->getSavedStepByClass('EventRegisterTicketsStep');
		$tickets      = $ticketsStep->loadData();

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
			$registration->Tickets()->add($id, array('Quantity' => $quantity));
		}

		$registration->write();
		$this->session->delete();

		return Director::redirect(Controller::join_links(
			$datetime->Event()->Link(),
			'registration',
			$registration->ID,
			'?token=' . $registration->Token));
	}

}
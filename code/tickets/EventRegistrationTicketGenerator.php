<?php
/**
 * A ticket generator takes a registration and generates a single ticket file
 * for it, which is attached to the email and the user can download/print.
 *
 * @package silverstripe-eventmanagement
 */
interface EventRegistrationTicketGenerator {

	/**
	 * Returns a human-readable name for the ticket generator.
	 *
	 * @return string
	 */
	public function getGeneratorTitle();

	/**
	 * Generates a ticket file for a registration, and returns the path to the
	 * ticket.
	 *
	 * @param  EventRegistration $registration
	 * @return string The path
	 */
	public function generateTicketFileFor(EventRegistration $registration);

}
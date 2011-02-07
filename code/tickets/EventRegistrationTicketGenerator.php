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
	 * Returns the file name the generated ticket file should have.
	 *
	 * @param  EventRegistration $registration
	 * @return string
	 */
	public function getTicketFilenameFor(EventRegistration $registration);

	/**
	 * Returns the mime type that the generated ticket file for a registration
	 * should have.
	 *
	 * @param  EventRegistration $registration
	 * @return string
	 */
	public function getTicketMimeTypeFor(EventRegistration $registration);

	/**
	 * Generates a ticket file for a registration, and returns the path to the
	 * ticket.
	 *
	 * NOTE: The ticket generator is responsible for caching the result.
	 *
	 * @param  EventRegistration $registration
	 * @return string The path to the generated file.
	 */
	public function generateTicketFileFor(EventRegistration $registration);

}
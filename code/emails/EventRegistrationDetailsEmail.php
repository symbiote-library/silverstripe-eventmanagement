<?php
/**
 * An email that contains the details for an event registration.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistrationDetailsEmail extends Email {

	protected $ss_template = 'EventRegistrationDetailsEmail';

	/**
	 * Creates an email instance from a registration object.
	 *
	 * @param  EventRegistration $registration
	 * @return EventRegistrationDetailsEmail
	 */
	public static function factory(EventRegistration $registration) {
		$email      = new self();
		$siteconfig = SiteConfig::current_site_config();

		$email->setTo($registration->Email);
		$email->setSubject(sprintf(
			'Registration Details For %s (%s)',
			$registration->Time()->EventTitle(),
			$siteconfig->Title));

		$email->populateTemplate(array(
			'Registration' => $registration,
			'SiteConfig'   => $siteconfig
		));

		return $email;
	}

}
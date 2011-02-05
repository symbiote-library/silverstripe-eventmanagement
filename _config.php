<?php
/**
 * @package silverstripe-eventmanagement
 */

if (!class_exists('Calendar')) {
	throw new Exception('The Event Management module requires the Event Calendar module.');
}

if (!class_exists('MultiForm')) {
	throw new Exception('The Event Management module requires the MultiForm module.');
}

if (!class_exists('ItemSetField')) {
	throw new Exception('The Event Management module requires the ItemSetField module.');
}
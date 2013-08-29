<?php

namespace Sulu\Bundle\TranslateBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\TranslateBundle\Entity\Code;

class TranslationsController extends FOSRestController
{
	public function getTranslationAction($slug)
	{

	}

	public function getTranslationsAction($slug)
	{
		// find codes by catalogueID
		$codes = $this->getDoctrine()
			->getRepository('SuluTranslateBundle:Code')
			->findByCatalogue($slug);

		// construct response array
		$translations = array();
		for ($i = 0; $i < sizeof($codes); $i++) {
			/** @var Code $code */
			$code = $codes[$i];

			// if no translation available set value null
			$value = '';
			if (is_array($code['translations']) && sizeof($code['translations']) > 0) {
				$value = $code['translations'][0]['value'];
			}

			$translations[] = array(
				'id' => $code['id'],
				'value' => $value,
				'code' => array(
					'id' => $code['id'],
					'code' => $code['code'],
					'backend' => $code['backend'],
					'frontend' => $code['frontend'],
					'length' => $code['length']
				)
			);
		}

		$response = array(
			'total' => sizeof($translations),
			'items' => $translations
		);
		$view = $this->view($response, 200);

		return $this->handleView($view);
	}
}

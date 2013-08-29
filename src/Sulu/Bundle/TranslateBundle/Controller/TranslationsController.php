<?php

namespace Sulu\Bundle\TranslateBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\CoreBundle\Controller\RestController;
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Code;
use Sulu\Bundle\TranslateBundle\Entity\Translation;
use Sulu\Bundle\TranslateBundle\Entity\TranslationRepository;
use Symfony\Component\HttpFoundation\Request;

class TranslationsController extends RestController implements ClassResourceInterface
{
	protected $entityName = 'SuluTranslateBundle:Translation';

	public function cgetAction($slug)
	{
		// find codes by catalogueID
		$codes = $this->getDoctrine()
			->getRepository('SuluTranslateBundle:Code')
			->findByCatalogue($slug);

		// construct response array
		$translations = array();
		for ($i = 0; $i < sizeof($codes); $i++) {
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

	public function patchAction($slug)
	{
		/** @var Request $request */
		$request = $this->getRequest();
		$i = 0;
		while ($item = $request->get($i)) {
			$this->saveTranslation($slug, $item);
			$i++;
		}
		$this->getDoctrine()->getManager()->flush();
		$view = $this->view(null, 204);

		return $this->handleView($view);
	}

	private function saveTranslation($catalogueId, $item)
	{
		/** @var TranslationRepository $repository */
		$repository = $this->getDoctrine()
			->getRepository($this->entityName);

		if (isset($item['id'])) {
			// code exists
			/** @var Translation $translation */
			$translation = $repository->getTranslation($item['id'], $catalogueId);
			if ($translation == null) {
				$this->newTranslation($catalogueId, $item);
			} else {
				$translation->setValue($item['value']);
				$translation->getCode()->setCode($item['code']['code']);
				$translation->getCode()->setFrontend($item['code']['frontend']);
				$translation->getCode()->setBackend($item['code']['backend']);
				$translation->getCode()->setLength($item['code']['length']);
			}
		} else {
			// new code
			$this->newCode($catalogueId, $item);
		}
	}

	private function newTranslation($catalogueId, $item)
	{
		/** @var Code $code */
		$code = $this->getDoctrine()
			->getRepository('SuluTranslateBundle:Code')
			->find($item['id']);
		/** @var Catalogue $catalogue */
		$catalogue = $this->getDoctrine()
			->getRepository('SuluTranslateBundle:Catalogue')
			->find($catalogueId);

		$translation = new Translation();
		$translation->setCode($code);
		$translation->setCatalogue($catalogue);
		$translation->setValue($item['value']);

		$this->getDoctrine()
			->getManager()
			->persist($translation);
	}

	private function newCode($catalogueId, $item)
	{
		/** @var Catalogue $catalogue */
		$catalogue = $this->getDoctrine()
			->getRepository('SuluTranslateBundle:Catalogue')
			->find($catalogueId);

		$code = new Code();
		$code->setCode($item['code']['code']);
		$code->setBackend($item['code']['backend']);
		$code->setFrontend($item['code']['frontend']);
		$code->setLength($item['code']['length']);
		$code->setPackage($catalogue->getPackage());

		$this->getDoctrine()
			->getManager()
			->persist($code);
		$this->getDoctrine()
			->getManager()
			->flush();

		$translation = new Translation();
		$translation->setValue($item['value']);
		$translation->setCode($code);
		$translation->setCatalogue($catalogue);

		$this->getDoctrine()
			->getManager()
			->persist($translation);
	}
}

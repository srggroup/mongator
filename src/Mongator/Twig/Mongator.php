<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Twig;

use Mongator\Id\IdGeneratorContainer;
use Mongator\Type\Container as TypeContainer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * The "Mongator" extension for twig (used in the Core Mondator extension).
 */
class Mongator extends AbstractExtension {


	public function getFilters() {
		return [
			'ucfirst'    => new TwigFilter('ucfirst', 'ucfirst'),
			'var_export' => new TwigFilter('var_export', static function ($string) {
				return var_export($string, true);
			}),
		];
	}


	public function getFunctions() {
		return [
			'Mongator_id_generator'          => new TwigFunction('Mongator_id_generator', [$this, 'mongatorIdGenerator']),
			'Mongator_id_generator_to_mongo' => new TwigFunction('Mongator_id_generator_to_mongo', [$this, 'mongatorIdGeneratorToMongo']),
			'Mongator_id_generator_to_php'   => new TwigFunction('Mongator_id_generator_to_php', [$this, 'mongatorIdGeneratorToPHP']),
			'Mongator_type_to_mongo'         => new TwigFunction('Mongator_type_to_mongo', [$this, 'mongatorTypeToMongo']),
			'Mongator_type_to_php'           => new TwigFunction('Mongator_type_to_php', [$this, 'mongatorTypeToPHP']),
		];
	}


	public function mongatorIdGenerator($configClass, $id, $indent = 8) {
		$idGenerator = IdGeneratorContainer::get($configClass['idGenerator']['name']);
		$code = $idGenerator->getCode($configClass['idGenerator']['options']);
		$code = str_replace('%id%', $id, $code);

		return static::indentCode($code, $indent);
	}


	public function mongatorIdGeneratorToMongo($configClass, $id, $indent = 8) {
		$idGenerator = IdGeneratorContainer::get($configClass['idGenerator']['name']);
		$code = $idGenerator->getToMongoCode();
		$code = str_replace('%id%', $id, $code);

		return static::indentCode($code, $indent);
	}


	public function mongatorIdGeneratorToPHP($configClass, $id, $indent = 8) {
		$idGenerator = IdGeneratorContainer::get($configClass['idGenerator']['name']);
		$code = $idGenerator->getToPHPCode();
		$code = str_replace('%id%', $id, $code);

		return static::indentCode($code, $indent);
	}


	public function mongatorTypeToMongo($type, $from, $to) {
		return strtr(TypeContainer::get($type)->toMongoInString(), [
			'%from%' => $from,
			'%to%'   => $to,
		]);
	}


	public function mongatorTypeToPHP($type, $from, $to) {
		return strtr(TypeContainer::get($type)->toPHPInString(), [
			'%from%' => $from,
			'%to%'   => $to,
		]);
	}


	public function getName() {
		return 'Mongator';
	}


	private static function indentCode($code, $indent) {
		return str_replace("\n", "\n" . str_repeat(' ', $indent), $code);
	}


}

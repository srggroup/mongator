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

/**
 * The "Mongator" extension for twig (used in the Core Mondator extension).
 *
 * @author Pablo Díez <pablodip@gmail.com>
 */
class Mongator extends \Twig_Extension
{
	public function getFilters()
	{
		return array(
			new \Twig_Filter('ucfirst', function($string){
				return ucfirst($string);
			}),
			new \Twig_Filter('var_export', function($string) {
				return var_export($string, true);
			})

		);
	}

	public function getFunctions()
	{
		return array(
			new \Twig_Function('Mongator_id_generator', array($this, 'MongatorIdGenerator')),
			new \Twig_Function('Mongator_id_generator_to_mongo', array($this, 'MongatorIdGeneratorToMongo')),
			new \Twig_Function('Mongator_id_generator_to_php', array($this, 'MongatorIdGeneratorToPHP')),
			new \Twig_Function('Mongator_type_to_mongo', array($this, 'MongatorTypeToMongo')),
			new \Twig_Function('Mongator_type_to_php', array($this, 'MongatorTypeToPHP')),
		);
	}

    public function MongatorIdGenerator($configClass, $id, $indent = 8)
    {
        $idGenerator = IdGeneratorContainer::get($configClass['idGenerator']['name']);
        $code = $idGenerator->getCode($configClass['idGenerator']['options']);
        $code = str_replace('%id%', $id, $code);
        $code = static::indentCode($code, $indent);

        return $code;
    }

    public function MongatorIdGeneratorToMongo($configClass, $id, $indent = 8)
    {
        $idGenerator = IdGeneratorContainer::get($configClass['idGenerator']['name']);
        $code = $idGenerator->getToMongoCode();
        $code = str_replace('%id%', $id, $code);
        $code = static::indentCode($code, $indent);

        return $code;
    }

    public function MongatorIdGeneratorToPHP($configClass, $id, $indent = 8)
    {
        $idGenerator = IdGeneratorContainer::get($configClass['idGenerator']['name']);
        $code = $idGenerator->getToPHPCode();
        $code = str_replace('%id%', $id, $code);
        $code = static::indentCode($code, $indent);

        return $code;
    }

    public function MongatorTypeToMongo($type, $from, $to)
    {
        return strtr(TypeContainer::get($type)->toMongoInString(), array(
            '%from%' => $from,
            '%to%'   => $to,
        ));
    }

    public function MongatorTypeToPHP($type, $from, $to)
    {
        return strtr(TypeContainer::get($type)->toPHPInString(), array(
            '%from%' => $from,
            '%to%'   => $to,
        ));
    }

    public function getName()
    {
        return 'Mongator';
    }

    private static function indentCode($code, $indent)
    {
        return str_replace("\n", "\n".str_repeat(' ', $indent), $code);
    }

}

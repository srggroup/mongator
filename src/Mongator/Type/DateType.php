<?php

/*
 * This file is part of Mongator.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mongator\Type;

use DateTime;
use DateTimeZone;
use MongoDB\BSON\UTCDateTime;

/**
 * DateType.
 */
class DateType extends Type {


	public function toMongo($value) {
		if (is_string($value)) {
			$value = new DateTime($value);
		}

		return new UTCDateTime($value);
	}


	public function toPHP($value) {
		$date = new DateTime();
		$date->setTimestamp($value->sec)->setTimeZone(new DateTimeZone(date_default_timezone_get()));

		return $date;
	}


	public function toMongoInString() {
		return "%to% = %from%; if (is_string(%from%)) { %to% = new \\DateTime(%from% ?: date('r',0)); } %to% = new \\MongoDB\\BSON\\UTCDateTime(%to%); ";
	}


	public function toPHPInString() {
		return '%to% = %from%->toDateTime()->setTimeZone(new \DateTimeZone( date_default_timezone_get()));';
	}


}

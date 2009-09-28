<?php
require(dirname(__FILE__).'/LJ.php');

$accounts = array(
	array(
		'login' => '????????',
		'hpassword' => md5('????????'),
		'community' => '????????',
		'stopwords'	=> array('??????'),
	),
);


foreach ($accounts as $acc) {
	try {
		$res = LJ::getlastevents(
			$acc['login'],
			$acc['hpassword'],
			$acc['community'],
			5
		);
		
		foreach ($res['events'] as $event) {
			/**
			 * Тут можно вставить любую логику проверки
			 */
			foreach ($acc['stopwords'] as $stop)
				if (strpos($event['event'], $stop) !== false)
					LJ::delevent(
						$acc['login'],
						$acc['hpassword'],
						$acc['community'],
						$event['itemid'],
						$event['anum']
					);
		}
	} catch (Exception $e) {
		echo 'LJ Cleaner error: '.$e->getMessage()."\n";
		error_log('LJ Cleaner error: '.$e->getMessage());
	}
}

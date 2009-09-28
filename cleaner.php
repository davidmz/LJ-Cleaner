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

// Автоматически банить пользователя в коммьюнити после удаления записи
$AUTO_BAN = true;
// Автоматически банить пользователя во всех коммьюнити, даже если он написал только в одно
$AUTO_BAN_ALL = true;

/***************/

$ban_list = array();
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
				if (strpos($event['event'], $stop) !== false) {
					LJ::delevent(
						$acc['login'],
						$acc['hpassword'],
						$acc['community'],
						$event['itemid'],
						$event['anum']
					);
					
					if ($AUTO_BAN_ALL) $ban_list[] = $event['poster'];
					elseif ($AUTO_BAN)
						LJ::consolecommand(
							$acc['login'],
							$acc['hpassword'],
							"ban_set {$event['poster']} from {$acc['community']}"
						);
				}
		}
	} catch (Exception $e) {
		echo 'LJ Cleaner error: '.$e->getMessage()."\n";
		error_log('LJ Cleaner error: '.$e->getMessage());
	}
}

if ($AUTO_BAN_ALL and !empty($ban_list)) {
	
	foreach ($accounts as $acc) {
		try {
			
			foreach ($ban_list as $poster)
				LJ::consolecommand(
					$acc['login'],
					$acc['hpassword'],
					"ban_set {$poster} from {$acc['community']}"
				);
			
		} catch (Exception $e) {
			echo 'LJ Cleaner error: '.$e->getMessage()."\n";
			error_log('LJ Cleaner error: '.$e->getMessage());
		}
	}

}
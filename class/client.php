<?php

abstract class Client
{
	static $client_list = array();
	// Flag for ensuring method is only invoked once in page request
	static $bool_client_list = false;
	
	static function showClientList()
	{
		Time::getPeriod();
		if ( ! ( self::$bool_client_list ) )
		{
			$period = $_SESSION['period'];
			$begin = strtotime($period);
			$end = strtotime('+1 month', $begin);

			$sql = 'SELECT count(DISTINCT L.postID) as primarySort, count(DISTINCT L2.postID) as secondarySort, C.first, C.last, C.rate, C.email, C.clientID FROM clients C
		LEFT JOIN lookup L on C.clientID = L.clientID AND L.date >= ' . $begin . ' AND L.date < ' . $end . '

		LEFT JOIN lookup L2 on C.clientID = L2.clientID 

		WHERE C.userID = ' . $_SESSION['loggedIn']['id'] . ' 
		GROUP BY C.clientID
		ORDER BY primarySort DESC, secondarySort DESC, last, first';
			$core = Core::getInstance(); 
			$s = $core->pdo->query($sql); 
			$data = $s->fetchAll();
			self::$client_list = $data;
			self::$bool_client_list = true;
		}
		return self::$client_list;
	}

	static function replaceClient()
	{
		if (isset($_POST['delete']))
		{
			self::removeClient($_POST['clientID']);
		}
		else
		{
			$sql = 'INSERT INTO clients SET clientID = :clientID, userID = :userID, first = :first, last = :last, email = :email, rate=:rate ON DUPLICATE KEY UPDATE first = :first, last = :last, email = :email, rate = :rate';
			$core = Core::getInstance();
			$s = $core->pdo->prepare($sql);
			$clientID = isset($_POST['clientID']) ? $_POST['clientID'] : substr(md5(microtime()), 0, 10);
			$s->bindValue('clientID', $clientID);
			$s->bindValue('userID', $_SESSION['loggedIn']['id']);
			$s->bindValue('first', $_POST['first']);
			$s->bindValue('last', $_POST['last']);
			$s->bindValue('email', $_POST['email']);
			$s->bindValue('rate', $_POST['rate']);
			$s->execute();
		}
		header('Location: /time/');
		exit;
	}

	static function removeClient($id)
	{		
	
		$sql = 'DELETE C, T, L, E FROM clients C LEFT JOIN lookup L on L.clientID = C.clientID LEFT JOIN times T on T.id = L.postID LEFT JOIN expenses E on E.id = L.postID WHERE C.clientID = :clientID';
		$core = Core::getInstance();
		$s = $core->pdo->prepare($sql);
		$s->bindValue('clientID', $id);
		$s->execute();
	}

	static function retrieveClient($id, $getTimes = false)
	{
		if ($getTimes)
		{
			$sql = 'SELECT * FROM clients C LEFT JOIN times T on T.clientID = C.clientID WHERE C.clientID = :clientID ORDER BY T.date';
		}
		else
		{
			$sql = 'SELECT * FROM clients C WHERE C.clientID = :clientID';
		}

		$core = Core::getInstance();
		$s = $core->pdo->prepare($sql);
		$s->bindValue('clientID', $id);
		$s->execute();
		return $s->fetchALL();
	}
}

<?php

abstract class Task
{
	static function showTaskList( $show_all = false, $limit_result_to_first_row = false, $client_id = '')
	{

		Time::getPeriod();
		$clientList = array();
		$period = $_SESSION['period'];
		$begin = strtotime($period);
		$end = strtotime('+1 month', $begin);

		$sql = 'SELECT count(DISTINCT L.post_id) as primarySort, count(DISTINCT L2.post_id) as secondarySort, taskName, T.clientID, TA.taskID, TA.active FROM tasks TA LEFT JOIN times T ON TA.taskID = T.taskID 
		LEFT JOIN lookup L on T.lid = L.post_id AND L.date >= ' . $begin . ' AND L.date < ' . $end . ' 
		LEFT JOIN lookup L2 on T.lid = L2.post_id
		WHERE TA.userID = ' . $_SESSION['loggedIn']['userID'];
		if ( ! $show_all )
		{
			$sql .= ' AND TA.active = 1 ';
		}
		if ( ! empty( $client_id ) )
		{
			$sql .= ' AND T.clientID = ' . $client_id;
		}

		$sql .= '
		GROUP BY TA.taskID 
		ORDER BY count(L.post_id) DESC, count(L2.post_id) DESC, taskName';
		if ( $limit_result_to_first_row === true ) // for ajax
		{
			$sql .= ' LIMIT 1';
		}
		$core = Core::getInstance(); 
		$s = $core->pdo->query($sql);
		return $s->fetchAll();
	}

	static function activate( $taskID )
	{
		$sql = 'UPDATE tasks SET active="1" WHERE taskID = :taskID';
		$core = Core::getInstance();
		$s = $core->pdo->prepare($sql);
		$s->bindValue('taskID', $taskID);
		$s->execute();
	}

	static function deactivate( $taskID )
	{
		$sql = 'UPDATE tasks SET active="0" WHERE taskID = :taskID';
		$core = Core::getInstance();
		$s = $core->pdo->prepare($sql);
		$s->bindValue('taskID', $taskID);
		$s->execute();
	}

	static function replaceTask()
	{
		// Are they trying to delete
		if (isset($_POST['delete']))
		{
			self::removeTask($_POST['taskID']);
		}

		else
		{
			$core = Core::getInstance();

			// Are they trying to update?
			if (isset($_POST['taskID']))
			{
				$sql = 'UPDATE tasks SET taskName = :taskName WHERE taskID = :taskID AND userID = :userID';
				$s = $core->pdo->prepare($sql);
				$s->bindValue('taskName', $_POST['taskName']);
				$s->bindValue('userID', $_SESSION['loggedIn']['userID']);
				$s->bindValue('taskID', $_POST['taskID']);
				//$s->bindValue('taskRate', $_POST['taskRate']);
			}
			else
			{
				$sql = 'INSERT INTO tasks SET taskName = :taskName, userID = :userID';
				$s = $core->pdo->prepare($sql);
				$s->bindValue('userID', $_SESSION['loggedIn']['userID']);
				$s->bindValue('taskName', $_POST['taskName']);
				//$s->bindValue('taskRate', $_POST['taskRate']);
			}
			
			$s->execute();
		}
		header('Location: /tasks');
		exit;
	}

	static function removeTask($id)
	{		
	
		$sql = 'DELETE tasks, times, lookup FROM tasks 
			LEFT JOIN times on times.taskID = tasks.taskID AND tasks.taskID = :taskID AND times.userID = :userID
			LEFT JOIN lookup on times.lid = lookup.post_id AND lookup.postType="time" AND tasks.userID = :userID
			WHERE tasks.taskID = :taskID AND tasks.userID = :userID';
		$core = Core::getInstance();
		$s = $core->pdo->prepare($sql);
		$s->bindValue('taskID', $id);
		$s->bindValue('userID', $_SESSION['loggedIn']['userID']);
		$s->execute();
	}

	static function retrieveTask($id)
	{
		$sql = 'SELECT * FROM tasks T WHERE T.taskID = :taskID';
		$core = Core::getInstance();
		$s = $core->pdo->prepare($sql);
		$s->bindValue('taskID', $id);
		$s->execute();
		return $s->Fetch();
	}

	static function history($id)
	{
		// Based on 1 year chunks
		$begin = strtotime( 'Jan 1, ' . substr($_SESSION['period'], -4) );
		$end = strtotime( "+1 year", $begin );
		$sql = 'SELECT T.taskName, C.rate, TI.clientID, TI.lid, TI.timeAmount, L.date FROM tasks T 
		 INNER JOIN times TI ON T.taskID = TI.taskID
INNER JOIN lookup L ON TI.lid = L.post_id
INNER JOIN clients C On C.clientID = L.clientID
WHERE T.taskID = :taskID AND T.userID = :userID AND L.date >= ' . $begin . ' AND L.date < ' . $end . ' ORDER BY L.date ASC';
		$core = Core::getInstance();
		$s = $core->pdo->prepare($sql);
		$s->bindValue('taskID', $id);
		$s->bindValue('userID', $_SESSION['loggedIn']['userID']);
		$s->execute();
		return $s->fetchAll();
	}
}

?>

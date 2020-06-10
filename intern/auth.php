<?php
include_once 'config.php';

if (isset($_GET['mode']) and ($_GET['mode'] == 'DEMO')) {
	if (isset($_COOKIE['RHG_calendar'])) {
		//print "Cookie found ...";
		$_SESSION = unserialize(base64_decode($_COOKIE['RHG_calendar']));
		if ((!isset($_SESSION['level'])) or ( $_SESSION['level'] == 0)) {
			setcookie("shipment", '', time()-28800);
		} else {
			//print "and initialized";
			//print_r($_SESSION);
		}

	} else {
		ini_set('display_errors', '0');
		$_SESSION['typ'] = 'ro';
		$_SESSION['level'] = '0';
		$_SESSION['user'] = 'Readonly';
		$_SESSION['name'] = '<B>Zum Bearbeiten bitte mit Factodaten einloggen!</B>';
		$_SESSION['uid'] = 0;
		$_SESSION['PENR'] = 0;
	}
	
} elseif (( !isset($_SESSION['user']) or (strlen($_SESSION['user']) == 0)) and ( !isset($_POST['loginuser']) or (strlen($_POST['loginuser']) == 0)))
{
	if (isset($_COOKIE['RHG_calendar'])) {
		//print "Cookie found ....";
		$_SESSION = unserialize(base64_decode($_COOKIE['RHG_calendar']));
		if ((!isset($_SESSION['level'])) or ( $_SESSION['level'] == 0)) {
			setcookie("shipment", '', time()-28800);
		} else {
			//print "and initialized";

		}
	} else {

		print <<<END
    
			<div id="main">
			<br><br><br><h2>Bitte geben Sie zum Login Ihrer FACTO-Daten an:<h2><BR><BR>
			<FORM ACTION = "#" METHOD = "POST" TARGET=_top>
			<table id = "tab2" height="100" width="500">
			<tr height="25">
			<td>Name: </td>
			<td><INPUT TYPE = "TEXT" NAME ="loginuser"></td>
			</tr>
			<tr height="25">
			<td>Passwort: </td>
			<td><INPUT TYPE = "PASSWORD" NAME ="password"></td>
			</tr>
			<tr height="25">
			<td></td>
			<td><INPUT TYPE = "SUBMIT" VALUE = "Login"></td>
			</tr>
			</table><BR>
			</FORM>
			</div>
			</body></html>
END;
		exit;
	}
}

elseif (isset($_POST['loginuser']) and strlen($_POST['loginuser']) > 0)
{

    //$pg_connect = pg_connect($pwhost);
	//pg_query($pg_connect, "set SCHEMA 'public'");
	$pw_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
	
	if ( is_numeric($_POST['loginuser']) ) {
		$qry  = 'select penr, qna1, qna2, qgrp, pusr, pcod,ptat,qkkz  from public.per_0 where penr = :personal';
	} else {
		$qry  = 'select penr, qna1, qna2, qgrp, pusr, pcod,ptat,qkkz  from public.per_0 where lower(pusr) = lower(:personal)';
	}
	$pw_qry = $pw_pdo->prepare($qry);
	$pw_qry->bindValue(':personal', $_POST['loginuser']);
	$pw_qry->execute() or die (print_r($pw_qry->errorInfo()));;
	$pw_row = $pw_qry->fetch( PDO::FETCH_ASSOC );
//	$pgqry = pg_query_params($pg_connect, $qry, array($_POST['loginuser']));	
//	$fqry = pg_fetch_assoc($pgqry);
	
   //$userdata = $stmt->fetchObject();
	//print $pw_row['pcod'];
	$fpcod = explode('#',$pw_row['pcod']);
	if ($fpcod[2] == strtoupper(sha1($_POST['password']))) {
		$_SESSION['user'] = $_POST['loginuser'];
		$_SESSION['uid'] = $pw_row['penr'];
		$_SESSION['PENR'] = $pw_row['penr'];
        $_SESSION['name'] = $pw_row['qna1'];
		$_SESSION['pickId'] = 0;
		if (in_array($pw_row['qgrp'],array(999,78)) ) {
			$_SESSION['typ'] = 'root';
			$_SESSION['level'] = '9';
		} elseif (in_array($pw_row['qgrp'],array(12,29)) ) {
			$_SESSION['typ'] = 'user';
			$_SESSION['level'] = '6';
		} else {
			$_SESSION['typ'] = 'user';
			$_SESSION['level'] = '1';
		}
		//QuickMySQL(NULL,'insert ignore into user (PENR, Name) VALUES (:user, :name)', array(':user'=> $fqry['penr'],':name'=>$fqry['qna1']));
		
		if (setcookie("RHG_calendar", base64_encode(serialize($_SESSION)), time()+28800)) {
			print "Keks erfolgreich gespeichert!";
		}
		include 'config.php';
        Proto($_SESSION['user']."eingeloggt");	
	} else {
		print <<<END
    
    <div id="main">
    <br><br>Falsche Zugangsdaten!<br><br>
    <FORM ACTION = "#" METHOD = "POST" TARGET=_top>
    <table id = "tab2" height="100" width="500">
    <tr height="25">
	<td>Name:</td>
	<td><INPUT TYPE = "TEXT" NAME ="loginuser"></td>
    </tr>
    <tr height="25">
	<td>Passwort:</td>
	<td><INPUT TYPE = "PASSWORD" NAME ="password"></td>
    </tr>
    <tr height="25">
	<td></td>
	<td><INPUT TYPE = "SUBMIT" VALUE = "Login"></td>
    </tr>
    </table><BR>
    </center>
    </FORM>                            	    	        	                                
    
    <br><br><br><br>
    <div id="main-ende"> </div>
    </div>
    </body></html>
END;
    exit;
    
    }

 }

?>

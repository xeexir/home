<?php
//Session management.

session_start();

//Files configuration
$fileAlias = array();
$fileNames = array();
$filePermissions = array();
$fileMaxUserLevel = array();

//Databases configuration
$dbAlias = array();
$dbNames = array();

//MySQL, ODBC & SQL SERVER configuration
$dbServerNames = array();
$dbUserNames = array();
$dbPasswords = array();

//SQL SERVER and ODBC configuration
$dbConnectionStrings = array();

//Users database configuration
$usersDb="";
//Only if Users DB is MySQL
$usersDbServerName="";
$usersDbUserName="";
$usersDbPassword="";
//Only if Users DB is SQL Server or ODBC
$usersDbConnectionString="";

//SQL's configuration
$sqlAlias = array();
$sqlQuerys = array();
$sqlMaxUserLevel = array();


//neoPHP plugin configuration file.
require 'config.php';

$errorMsg = Array();
$errorMsg[0]="Permission denied.";
$errorMsg[1]="No function called";
$errorMsg[2]="User already exists";
$errorMsg[3]="Not found";
$errorMsg[4]="Wrong user name or password";
$errorMsg[5]="File does not exists";
$errorMsg[6]="Database does not exists or not valid query";

//Get all posted params.
if (isset($_POST['funcname'])){
	$funcname=$_POST["funcname"];
	$fields = array();
	$values = array();
	foreach($_POST as $field => $value) {
		$fields[] = $field;
		$values[] = $value;
	}
}else{
	die($errorMsg[1]);
}

//Init session
if(!isset($_SESSION["userid"] )){
  $userdata = new stdClass();
  $userdata->id = -1;
	$userdata->name = "";
	$userdata->email = "";
	$userdata->level = 1000;
	$userdata->error = $errorMsg[3];
	$_SESSION["userid"] = $userdata->id;
	$_SESSION["username"] = $userdata->name;
	$_SESSION["useremail"] = $userdata->email;
	$_SESSION["userlevel"] = $userdata->level;
}

switch ($funcname) {
    case "login":
        $funcname($values[1],$values[2]);
        break;
    case "logout":
        $funcname();
        break;
	case "adduser":
	    $funcname($values[1],$values[2],$values[3],$values[4]);
		break;
	case "eraseuser":
	    $funcname($values[1]);
		break;
	case "changeuserpassword":
	    $funcname($values[1],$values[2],$values[3]);
		break;
	case "filecopy":
        $funcname($values[1],$values[2]);
        break;
	case "fileerase":
	    $funcname($values[1]);
        break;
    case "filewrite":
        $funcname($values[1],$values[2],$values[3]);
        break;
	case "filewritefrombase64":
        $funcname($values[1],$values[2]);
        break;
	case "filetovar":
	    $funcname($values[1]);
		break;
	case "execsql":
		$funcname($values[1],$values[2],$values[3]);
		break;
	case "neotableupdate":
	    //name,value,id,containerid
	    $funcname($values[0],$values[1],$values[2],$values[3],$values[4],$values[5]);
		break;
}

function neotableupdate($name,$value,$id,$dbName,$tableName,$idfield){
	global $errorMsg,$dbServerNames,$dbUserNames,$dbPasswords,$dbConnectionStrings;
	if(is_string($value)){
	   $value=htmlentities($value, ENT_QUOTES);
	}
	if($dbName==""){
		die(json_encode($errorMsg[6]));
	}
	
	if(is_array($value)){
		$finalvalue="";
		foreach($value as $data) {
			$finalvalue = $finalvalue.$data.",";
		}
		$finalvalue = rtrim($finalvalue, ',');
		$value=$finalvalue;
	}
	$realDbName=checkDb($dbName);
	$arrayNumber=checkDbArrayNumber($dbName);
	$serverName=$dbServerNames[$arrayNumber];
	$dbuserName=$dbUserNames[$arrayNumber];
	$dbpassword=$dbPasswords[$arrayNumber];
	$dbconnectiontring=$dbConnectionStrings[$arrayNumber];
	
	if((!empty($serverName) || !empty($dbconnectiontring)) && !empty($dbuserName) && !empty($dbpassword)){
		try {
			if(!empty($dbconnectiontring)){
			  //SQL Server or ODBC
			  $mydb = new PDO($dbconnectiontring, $dbuserName, $dbpassword);
			}else{
			  //MySQL
			  $mydb = new PDO("mysql:host=$serverName;dbname=$realDbName;charset=utf8mb4", $dbuserName, $dbpassword);
			  $mydb->exec("set names utf8");
			}
		}
		catch(PDOException $e){
			echo "Connection failed: " . $e->getMessage();
			die();
		}
		$mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if($id=='undefined'){
			$sql="INSERT INTO $tableName ($idfield,$name) VALUES (NULL,'".$value."')";
			$result = $mydb->prepare($sql); 
			$result->execute();
			$rowid = $mydb->lastInsertId();
			die($rowid);
		}
		
		$realQuery="UPDATE $tableName SET $name = '".$value."' WHERE $idfield = $id";
		$stmt = $mydb->query($realQuery);
		if(stripos($realQuery,"select")>-1){
				$jsonquerydata = json_encode($querydata);
				print($jsonquerydata);
		}else{
				print(json_encode("True"));
		}
	}else{
		//SQLite
		$mydb = new PDO("sqlite:".$realDbName);
		$mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if($id=='undefined'){
			$sql="INSERT INTO $tableName ($idfield,$name) VALUES (NULL,'".$value."')";
			$result = $mydb->prepare($sql); 
			$result->execute();
			$rowid = $mydb->lastInsertId();
			die($rowid);
		}
		
		$realQuery="UPDATE $tableName SET $name = '".$value."' WHERE $idfield = $id";
		$stmt = $mydb->query($realQuery);
		if(stripos($realQuery,"select")>-1){
				$jsonquerydata = json_encode($querydata);
				print($jsonquerydata);
		}else{
				print(json_encode("True"));
		}
	}
}

function execsql($dbName,$dbQuery,$params){
	global $errorMsg,$dbServerNames,$dbUserNames,$dbPasswords,$dbConnectionStrings;
	$realDbName=checkDb($dbName);
	$realQuery=checkQuery($dbQuery);
	$arrayNumber=checkDbArrayNumber($dbName);
	$parameters=array();
	$parameters=explode("::",$params);
	$userLevel=checkMaxQueryUserLevel($dbQuery);
	if(($_SESSION["userlevel"] <= $userLevel || $userLevel==-1) && $realQuery!=""){

          if(!empty($dbServerNames[$arrayNumber])){
			  $serverName=$dbServerNames[$arrayNumber]; 
		  }else{
		      $serverName="";
		  }
		  if(!empty($dbUserNames[$arrayNumber])){
			  $dbuserName=$dbUserNames[$arrayNumber]; 
		  }else{
		      $dbuserName="";
		  }
		  if(!empty($dbPasswords[$arrayNumber])){
			  $dbpassword=$dbPasswords[$arrayNumber]; 
		  }else{
		      $dbpassword="";
		  }
		  if(!empty($dbConnectionStrings[$arrayNumber])){
		     $dbconnectiontring=$dbConnectionStrings[$arrayNumber];
	      }else{
		     $dbconnectiontring="";
		  } 
		  
		if((!empty($serverName) || !empty($dbconnectiontring)) && !empty($dbuserName)){
			try {
				if(!empty($dbconnectiontring)){
				  //SQL Server or ODBC
				  $mydb = new PDO($dbconnectiontring, $dbuserName, $dbpassword);
				}else{
				  //MySQL
				  $mydb = new PDO("mysql:host=$serverName;dbname=$realDbName;charset=utf8mb4", $dbuserName, $dbpassword);
				  $mydb->exec("set names utf8");
				}
			}
			catch(PDOException $e){
				echo "Connection failed: " . $e->getMessage();
				die();
			}
			$mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$stmt = $mydb->prepare($realQuery);
			$paramNum=count($parameters);
			
			if($paramNum>0 && $params!=""){
				for($x=1;$x<($paramNum+1);$x++){
					$stmt->bindValue($x, $parameters[$x-1]);
				}
			}
			if(stripos($realQuery,"select")>-1){
				$stmt->execute();
				$querydata = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$jsonquerydata = json_encode($querydata);
				print($jsonquerydata);
				die();
			}else{
				$stmt->execute();
				print(json_encode("True"));
				die();
			}
		}else{
			//SQLite
			$mydb = new PDO("sqlite:".$realDbName);
			$mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  		    $stmt = $mydb->prepare($realQuery);
			$paramNum=count($parameters);
			if($paramNum>0 && $params!=""){
				for($x=1;$x<($paramNum+1);$x++){
					$stmt->bindValue($x, $parameters[$x-1]);
				}
			}
			if(stripos($realQuery,"select")>-1){
				$stmt->execute();
				$querydata = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$jsonquerydata = json_encode($querydata);
				print($jsonquerydata);
				die();
			}else{
				$stmt->execute();
				print(json_encode("True"));
				die();
			}
		}
	}else{
		print(json_encode($errorMsg[6]));
	}
}
function login($userName,$password){
	global $adminName, $adminPass, $usersDb, $errorMsg, $usersDbServerName, $usersDbUserName, $usersDbPassword, $usersDbConnectionString;
    $userdata = new stdClass();
	if($userName=="" || $password==""){
		$userdata->error = $errorMsg[0];
		$jsonuserdata = json_encode($userdata);
	    print($jsonuserdata);
		return;
	}
	//Check if is main admin.
	if($userName==$adminName && $password==$adminPass){
		$userdata->id = 0;
		$userdata->name = "Admin";
		$userdata->email = "";
		$userdata->level = 0;
		$userdata->error = "";
		
		$_SESSION["userid"] = $userdata->id;
		$_SESSION["username"] = $userdata->name;
		$_SESSION["useremail"] = $userdata->email;
		$_SESSION["userlevel"] = $userdata->level;
		
	}else{
		//Clear session user data
		$userdata->id = -1;
		$userdata->name = "";
	    $userdata->email = "";
		$userdata->level = 1000;
		$userdata->error = $errorMsg[3];
		$_SESSION["userid"] = $userdata->id;
		$_SESSION["username"] = $userdata->name;
		$_SESSION["useremail"] = $userdata->email;
		$_SESSION["userlevel"] = $userdata->level;
		
		//Search for user in de database
		$realDbName = $usersDb;
		
		if($realDbName!="" && $usersDbServerName==""){
			//SQLite
			createUsersTable();
			$mydb = new PDO("sqlite:".$realDbName);
			$mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$stmt = $mydb->prepare('SELECT * FROM neousers WHERE username=?');
			$stmt->execute([$userName]);

			foreach ($stmt as $row){
				if (password_verify($password,$row['password'])){
					
					//Get all neouser fields
					$keys = array_keys($row);
					$arraySize = count($row);
					for( $i=0; $i < $arraySize; $i++ ){
						$userdata->{$keys[$i]} = $row[$keys[$i]];
					}
					
					$userdata->error = "";
					$_SESSION["userid"] = $userdata->id;
					$_SESSION["username"] = $userdata->name;
					$_SESSION["useremail"] = $userdata->email;
					$_SESSION["userlevel"] = $userdata->level;
					break;
				}else{
					$userdata->error = $errorMsg[4];
				}
			}
		}else{
			//MySQL
			createUsersTableMySQL();
			$serverName=$usersDbServerName;
			$dbuserName=$usersDbUserName;
			$dbpassword=$usersDbPassword;
			$dbconnectiontring=$usersDbConnectionString;
	
			if((!empty($serverName) || !empty($dbconnectiontring)) && !empty($dbuserName) && !empty($dbpassword)){
				try {
					if(!empty($dbconnectiontring)){
					  //SQL Server or ODBC
					  $mydb = new PDO($dbconnectiontring, $dbuserName, $dbpassword);
					}else{
					  //MySQL
					  $mydb = new PDO("mysql:host=$serverName;dbname=$realDbName;charset=utf8mb4", $dbuserName, $dbpassword);
					  $mydb->exec("set names utf8");
					}
				}
				catch(PDOException $e){
					echo "Connection failed: " . $e->getMessage();
					die();
				}
				$mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$stmt = $mydb->prepare('SELECT * FROM neousers WHERE username=?');
				$stmt->execute([$userName]);

				foreach ($stmt as $row){
					if (password_verify($password,$row['password'])){
						
						//Get all neouser fields
						$keys = array_keys($row);
						$arraySize = count($row);
						for( $i=0; $i < $arraySize; $i++ ){
							$userdata->{$keys[$i]} = $row[$keys[$i]];
						}
						
						$userdata->error = "";
						$_SESSION["userid"] = $userdata->id;
						$_SESSION["username"] = $userdata->name;
						$_SESSION["useremail"] = $userdata->email;
						$_SESSION["userlevel"] = $userdata->level;
						break;
					}else{
						$userdata->error = $errorMsg[4];
					}
				}
			}
		}
	}

	$jsonuserdata = json_encode($userdata);
	print($jsonuserdata);
}

function logout(){
	$userdata->id = -1;
	$userdata->name = "";
	$userdata->email = "";
	$userdata->level = 1000;
	$userdata->error = "";
	$_SESSION["userid"] = $userdata->id;
	$_SESSION["username"] = $userdata->name;
	$_SESSION["useremail"] = $userdata->email;
	$_SESSION["userlevel"] = $userdata->level;
	print("True");
}
function adduser($theUserName,$theUserEmail,$theUserPassword,$theUserLevel){
	//Inserts a new user in the database.
	global $errorMsg,$usersDb, $usersDbServerName, $usersDbUserName, $usersDbPassword, $allowSelfRegistration, $selfRegistrationLevel, $usersDbConnectionString;
	if($_SESSION["userlevel"] !=0){
		if($allowSelfRegistration){
			$theUserLevel=$selfRegistrationLevel;
		}else{
			die($errorMsg[0]);
		}
	}
	$realDbName = $usersDb;
	if($realDbName!="" && $usersDbServerName==""){
		//SQLite
		createUsersTable();
		$mydb = new PDO("sqlite:".$realDbName);
		$mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "SELECT * FROM neousers WHERE username='".$theUserName."'";
		$stmt = $mydb->query($sql);
		$nRows=0;
		foreach ($stmt as $row){
			$nRows=$nRows+1;
		}			
		if($nRows==0){
			$theUserPassword=password_hash($theUserPassword, PASSWORD_DEFAULT);
			$sentencia = $mydb->prepare("INSERT INTO neousers(username, email, password, level) VALUES('$theUserName', '$theUserEmail', '$theUserPassword', $theUserLevel);");
			$resultado = $sentencia->execute();
			if($resultado === true){
				echo "True";
			}else{
				echo "Error";
			}
		}else{
			echo $errorMsg[2]; 
		}
	}else{
		//MySQL
		createUsersTableMySQL();
		$serverName=$usersDbServerName;
		$dbuserName=$usersDbUserName;
		$dbpassword=$usersDbPassword;
		$dbconnectiontring=$usersDbConnectionString;
	
		if((!empty($serverName) || !empty($dbconnectiontring)) && !empty($dbuserName) && !empty($dbpassword)){
			try {
				if(!empty($dbconnectiontring)){
				  //SQL Server or ODBC
				  $mydb = new PDO($dbconnectiontring, $dbuserName, $dbpassword);
				}else{
				  //MySQL
				  $mydb = new PDO("mysql:host=$serverName;dbname=$realDbName;charset=utf8mb4", $dbuserName, $dbpassword);
				  $mydb->exec("set names utf8");
				}
			}
			catch(PDOException $e){
				echo "Connection failed: " . $e->getMessage();
				die();
			}
			$mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$sql = "SELECT * FROM neousers WHERE username='".$theUserName."'";
			$stmt = $mydb->query($sql);
			$nRows=0;
			foreach ($stmt as $row){
				$nRows=$nRows+1;
			}			
			if($nRows==0){
				$theUserPassword=password_hash($theUserPassword, PASSWORD_DEFAULT);
				$sentencia = $mydb->prepare("INSERT INTO neousers(username, email, password, level) VALUES('$theUserName', '$theUserEmail', '$theUserPassword', $theUserLevel);");
				$resultado = $sentencia->execute();
				if($resultado === true){
					echo "True";
				}else{
					echo "Error";
				}
			}else{
				echo $errorMsg[2]; 
			}
		}
	}
}
function eraseuser($userId){
	//Deletes a user in the database.
	global $errorMsg,$usersDb,$usersDbServerName,$usersDbUserName,$usersDbPassword,$usersDbConnectionString;
	if($_SESSION["userlevel"] !=0){
		print($errorMsg[0]);
	}else{
		$realDbName = $usersDb;
		if($realDbName!="" && $usersDbServerName==""){
			//SQLite
			createUsersTable();
			$mydb = new PDO("sqlite:".$realDbName);
			$mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$sql = "SELECT * FROM neousers WHERE id='".$userId."'";
			$stmt = $mydb->query($sql);
            $nRows=0;
			foreach ($stmt as $row){
				$nRows=$nRows+1;
			}			
			if($nRows==1){
				$sentencia = $mydb->prepare("DELETE FROM neousers WHERE id='".$userId."'");
				$resultado = $sentencia->execute();
				if($resultado === true){
					echo "True";
				}else{
					echo "Error";
				}
			}else{
				echo "Error";
			}
		}else{
			//MySQL
			createUsersTableMySQL();
			$serverName=$usersDbServerName;
			$dbuserName=$usersDbUserName;
			$dbpassword=$usersDbPassword;
			$dbconnectiontring=$usersDbConnectionString;
	
			if((!empty($serverName) || !empty($dbconnectiontring)) && !empty($dbuserName) && !empty($dbpassword)){
				try {
					if(!empty($dbconnectiontring)){
					  //SQL Server or ODBC
					  $mydb = new PDO($dbconnectiontring, $dbuserName, $dbpassword);
					}else{
					  //MySQL
					  $mydb = new PDO("mysql:host=$serverName;dbname=$realDbName;charset=utf8mb4", $dbuserName, $dbpassword);
					  $mydb->exec("set names utf8");
					}
				}
				catch(PDOException $e){
					echo "Connection failed: " . $e->getMessage();
					die();
				}
				$sql = "SELECT * FROM neousers WHERE id='".$userId."'";
				$stmt = $mydb->query($sql);
				$nRows=0;
				foreach ($stmt as $row){
					$nRows=$nRows+1;
				}			
				if($nRows==1){
					$sentencia = $mydb->prepare("DELETE FROM neousers WHERE id='".$userId."'");
					$resultado = $sentencia->execute();
					if($resultado === true){
						echo "True";
					}else{
						echo "Error";
					}
				}else{
					echo "Error";
				}
			}
		}
	}
}
function changeuserpassword($userId,$oldPassword,$newPassword){
	//Allows a user to change his own password.
	global $errorMsg,$usersDb,$usersDbServerName,$usersDbUserName,$usersDbPassword,$usersDbConnectionString;
	$realDbName = $usersDb;
	if($realDbName!="" && $usersDbServerName==""){
		//SQLite
		$mydb = new PDO("sqlite:".$realDbName);
		$mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "SELECT * FROM neousers WHERE id='".$userId."'";
		$stmt = $mydb->query($sql);
		$nRows=0;
		
		foreach ($stmt as $row){
				if (password_verify($oldPassword,$row['password'])){
					$nRows=$nRows+1;
					break;
				}else{
					die($errorMsg[4]); 
				}
		}
		if($nRows==1){
			$newPassword=password_hash($newPassword, PASSWORD_DEFAULT);
			$sentencia = $mydb->prepare("UPDATE neousers SET password = '$newPassword' WHERE id='".$userId."'");
			$resultado = $sentencia->execute();
			if($resultado === true){
				echo "True";
			}else{
				echo "Error";
			}
		}else{
			echo "Error";
		}
	}else{
		//MySQL
		$serverName=$usersDbServerName;
		$dbuserName=$usersDbUserName;
		$dbpassword=$usersDbPassword;
		$dbconnectiontring=$usersDbConnectionString;
		if((!empty($serverName) || !empty($dbconnectiontring)) && !empty($dbuserName) && !empty($dbpassword)){
			try {
				if(!empty($dbconnectiontring)){
				  //SQL Server or ODBC
				  $mydb = new PDO($dbconnectiontring, $dbuserName, $dbpassword);
				}else{
				  //MySQL
				  $mydb = new PDO("mysql:host=$serverName;dbname=$realDbName;charset=utf8mb4", $dbuserName, $dbpassword);
				  $mydb->exec("set names utf8");
				}
			}
			catch(PDOException $e){
				echo "Connection failed: " . $e->getMessage();
				die();
			}
			$sql = "SELECT * FROM neousers WHERE id='".$userId."'";
			$stmt = $mydb->query($sql);
			$nRows=0;
			
			foreach ($stmt as $row){
					if (password_verify($oldPassword,$row['password'])){
						$nRows=$nRows+1;
						break;
					}else{
						die($errorMsg[4]); 
					}
			}
			if($nRows==1){
				$newPassword=password_hash($newPassword, PASSWORD_DEFAULT);
				$sentencia = $mydb->prepare("UPDATE neousers SET password = '$newPassword' WHERE id='".$userId."'");
				$resultado = $sentencia->execute();
				if($resultado === true){
					echo "True";
				}else{
					echo "Error";
				}
			}else{
				echo "Error";
			}
		}
	}
}
function createUsersTable(){
	 //Create database and users table if not exists.
	 global $usersDb;
	 $mydb = new PDO("sqlite:".$usersDb);
	 $mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	 $tableDef = "CREATE TABLE IF NOT EXISTS neousers(
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		username TEXT NOT NULL,
		password TEXT NOT NULL,
		email TEXT,
		level INTEGER NOT NULL
	 );";
	 $resultado = $mydb->exec($tableDef);
}
function createUsersTableMySQL(){
	//Create database and users table if not exists.
	global $usersDb, $usersDbServerName, $usersDbUserName, $usersDbPassword, $usersDbConnectionString;
	$serverName=$usersDbServerName;
	$dbuserName=$usersDbUserName;
	$dbpassword=$usersDbPassword;
	$dbconnectiontring=$usersDbConnectionString;
	
	if((!empty($serverName) || !empty($dbconnectiontring)) && !empty($dbuserName) && !empty($dbpassword)){
		try {
			if(!empty($dbconnectiontring)){
			  //SQL Server or ODBC
			  $mydb = new PDO($dbconnectiontring, $dbuserName, $dbpassword);
			}else{
			  //MySQL
			  $mydb = new PDO("mysql:host=$serverName;dbname=$usersDb;charset=utf8mb4", $dbuserName, $dbpassword);
			  $mydb->exec("set names utf8");
			}
		}
		catch(PDOException $e){
			echo "Connection failed: " . $e->getMessage();
			die();
		}
		$mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$tableDef = "CREATE TABLE IF NOT EXISTS neousers(
			id int(11) NOT NULL AUTO_INCREMENT,
			username varchar(200) NOT NULL,
			password varchar(200) NOT NULL,
			email varchar(200) DEFAULT NULL,
			level int(11) NOT NULL,
			PRIMARY KEY (id)
		);";
		$resultado = $mydb->exec($tableDef);
	}
}

//Check if database name is an alias.
function checkDb($dbName){
	global $dbAlias, $dbNames, $dbServerNames;
	$arrlength = count($dbAlias);
	for($x = 0; $x < $arrlength; $x++) {
		if($dbName==$dbAlias[$x]){
			if(file_exists($dbNames[$x]) || !empty($dbServerNames[$x])){
				return $dbNames[$x];
			}else{
				return "";
			}
		}
	}
	//Not found
	return "";
}

//Check if query name is an alias.
function checkQuery($queryName){
	global $sqlAlias, $sqlQuerys;
	$arrlength = count($sqlAlias);
	for($x = 0; $x < $arrlength; $x++) {
		if($queryName==$sqlAlias[$x]){
				return $sqlQuerys[$x];
		}
	}
	//Not found
	return "";
}

//Check the database configuration array number from config.php.
function checkDbArrayNumber($dbName){
	global $dbAlias, $dbNames;
	$arrlength = count($dbAlias);
	for($x = 0; $x < $arrlength; $x++) {
		if($dbName==$dbAlias[$x]){
				return $x;
		}
	}
	//Not found
	return -1;
}

//Check the maximun user level allowed to perform a query
function checkMaxQueryUserLevel($queryName){
	global $sqlAlias, $sqlQuerys, $sqlMaxUserLevel;
	$arrlength = count($sqlAlias);
	for($x = 0; $x < $arrlength; $x++) {
		if($queryName==$sqlAlias[$x]){
				return $sqlMaxUserLevel[$x];
		}
	}
	//Not found
	return "";
}

//copy a file
function filecopy($sourceFileName,$destinationFileName){
	global $errorMsg;
	$realFileNameSource = checkReadFilename($sourceFileName);
	$realFileNameDestination = checkWriteFilename($destinationFileName);
	$userLevel=checkMaxFileUserLevel($destinationFileName);
	if(($_SESSION["userlevel"] <= $userLevel || $userLevel==-1) && $realFileNameSource!="" && $realFileNameDestination!=""){
		if(file_exists($realFileNameSource)){
			if(copy($realFileNameSource,$realFileNameDestination)){
				print("True");
			}else{
				print("Error");
			}
		}else{
			print($errorMsg[5]);
		}
	}else{
		print($errorMsg[0]);
	}
}

//erase a file
function fileerase($filename){
	global $errorMsg;
	$realFileName = checkWriteFilename($filename);
	$userLevel=checkMaxWriteFileUserLevel($filename);
	if(($_SESSION["userlevel"] <= $userLevel || $userLevel==-1) && $realFileName!=""){
		if(file_exists($realFileName)){
			if(!unlink($realFileName)){
			  print("Error");
			}else{
			  print("True");
			}
		}else{
			print($errorMsg[5]);
		}
	}else{
		print($errorMsg[0]);
	}
}
function filewrite($filename,$data,$append){
	global $errorMsg;
	$realFileName = checkWriteFilename($filename);
	$userLevel=checkMaxWriteFileUserLevel($filename);
	if(($_SESSION["userlevel"] <= $userLevel || $userLevel==-1) && $realFileName!=""){
		if($append=="True"){
			$fp = fopen($realFileName,"a");
		}else{
			$fp = fopen($realFileName,"wb");
		}
		fwrite($fp,$data);
		fclose($fp);
		print("True");
	}else{
		print($errorMsg[0]);
	}
}

function filewritefrombase64($filename,$data){
	global $errorMsg;
	$realFileName = checkWriteFilename($filename);
	$userLevel=checkMaxWriteFileUserLevel($filename);
	if(($_SESSION["userlevel"] <= $userLevel || $userLevel==-1) && $realFileName!=""){
		$ifp = fopen( $filename, 'wb' ); 
        $finaldata = explode( ',', $data );
        fwrite( $ifp, base64_decode( $finaldata[ 1 ] ) );
        fclose( $ifp );
		print("True");
	}else{
		print($errorMsg[0]);
	}
}
function filetovar($filename){
	global $errorMsg;
	$realFileName = checkReadFilename($filename);
	$userLevel=checkMaxReadFileUserLevel($filename);
	if(($_SESSION["userlevel"] <= $userLevel || $userLevel==-1) && $realFileName!=""){
	  if(file_exists($realFileName)){
		  print(file_get_contents($realFileName));
	  }else{
		  print("Error"); 
	  }
	}else{
	  print("Error");
	}
}

//Check the maximun user level allowed to perform read operations on file
function checkMaxReadFileUserLevel($filename){
	global $fileAlias,$fileNames,$fileMaxUserLevel,$filePermissions;
	$type1=pathinfo($filename, PATHINFO_EXTENSION);
	$arrlength = count($fileAlias);
	for($x = 0; $x < $arrlength; $x++) {
		$type2=pathinfo($fileAlias[$x], PATHINFO_EXTENSION);
		$name2=pathinfo($fileAlias[$x], PATHINFO_FILENAME);
		if($filename==$fileAlias[$x] && ($type2!="*" && $name2!="*")){
		   if($filePermissions[$x]=="r" || $filePermissions[$x]=="rw"){
				 return $fileMaxUserLevel[$x];
			 }
		}
		//Search for *.ext
		if(($type1==$type2) && $name2=="*"){
			if($filePermissions[$x]=="r" || $filePermissions[$x]=="rw"){
			   return $fileMaxUserLevel[$x];
			}
		}
	}
	//Not found
	return "";
}

//Check the maximun user level allowed to perform write operations on file
function checkMaxWriteFileUserLevel($filename){
	global $fileAlias,$fileNames,$fileMaxUserLevel,$filePermissions;
	$type1=pathinfo($filename, PATHINFO_EXTENSION);
	$arrlength = count($fileAlias);
	for($x = 0; $x < $arrlength; $x++) {
		$type2=pathinfo($fileAlias[$x], PATHINFO_EXTENSION);
		$name2=pathinfo($fileAlias[$x], PATHINFO_FILENAME);
		if($filename==$fileAlias[$x] && ($type2!="*" && $name2!="*")){
		   if($filePermissions[$x]=="w" || $filePermissions[$x]=="rw"){
				 return $fileMaxUserLevel[$x];
			 }
		}
		//Search for *.ext
		if(($type1==$type2) && $name2=="*"){
			if($filePermissions[$x]=="w" || $filePermissions[$x]=="rw"){
			   return $fileMaxUserLevel[$x];
			}
		}
	}
	//Not found
	return "";
}

//Check if file name is an alias and has permission for reading.
function checkReadFilename($filename){
	global $fileAlias,$fileNames,$filePermissions;
	$type1=pathinfo($filename, PATHINFO_EXTENSION);
	$arrlength = count($fileAlias);
	for($x = 0; $x < $arrlength; $x++) {
		$type2=pathinfo($fileAlias[$x], PATHINFO_EXTENSION);
		$name2=pathinfo($fileAlias[$x], PATHINFO_FILENAME);
		if($filename==$fileAlias[$x] && ($type2!="*" && $name2!="*")){
			if($filePermissions[$x]=="r" || $filePermissions[$x]=="rw"){
				return $fileNames[$x];
			}
		}
		//Search for *.ext
		if(($type1==$type2) && $name2=="*"){
			if($filePermissions[$x]=="r" || $filePermissions[$x]=="rw"){
			   return $filename;
			}
		}
	}
	//Not found
	return "";
}

//Check if file name is an alias and has permission for writing.
function checkWriteFilename($filename){
	global $fileAlias,$fileNames,$filePermissions;
	$type1=pathinfo($filename, PATHINFO_EXTENSION);
	$arrlength = count($fileAlias);
	for($x = 0; $x < $arrlength; $x++) {
		$type2=pathinfo($fileAlias[$x], PATHINFO_EXTENSION);
		$name2=pathinfo($fileAlias[$x], PATHINFO_FILENAME);
		if($filename==$fileAlias[$x] && ($type2!="*" && $name2!="*")){
			if($filePermissions[$x]=="w" || $filePermissions[$x]=="rw"){
				return $fileNames[$x];
			}
		}
		//Search for *.ext
		if(($type1==$type2) && $name2=="*"){
			if($filePermissions[$x]=="w" || $filePermissions[$x]=="rw"){
			   return $filename;
			}
		}
	}
	//Not found
	return "";
}
?>
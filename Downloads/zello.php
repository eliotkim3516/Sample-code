<?php
/**
* ZelloWork server API sandbox
*/
error_reporting( E_ALL ); 

$query = "SELECT QUERY"; //master query 
                                 
$query2 = "SELECT GROUP"; //for groups

$query3 = "SELECT TEACHER"; //for teachers

$master_array = Database::select($query);
$group_names = Database::select($query2, null,true); //index by groupid
$teacher_names = Database::select($query3,null, true); //index by teacherid
$existing_channels = array();
$full_erase = ($_GET['full'] == 'yes'); //parameter to wipe everything.


// echo "<pre>";
// echo print_r($master_array);
// echo "<pre>";

// echo "<pre>";
// echo print_r($group_names);
// echo "<pre>";

// echo "<pre>";
// echo print_r($teacher_names);
// echo "<pre>";
//exit;


/////////////////////////////////////////////
//////////////////ZELLO FUNCTIONS//////////////

//authenication 
require("./zello_server_api.class.php"); // ZelloWork API wrapper
$host = "HOSTNAME";	// your ZelloWork network URL hostname
$apikey = "APIKEY"; // your API key
$ltapi = new ZelloServerAPI($host, $apikey);
if (!$ltapi) {
	die("Failed to create API wrapper instance");
}
// See if we preserved Session ID through GET parameter. Use it if we did
$sid = isset($_GET["sid"]) ? $_GET["sid"] : '';
if ($sid) {
	$ltapi->sid = $sid;
	echo("Session ID was provided, use it and skip login authentication. ");
	echo('Session ID is <a href="?sid='.$ltapi->sid.'">'.$ltapi->sid.'</a>');
// No Session ID -- authenticate using username / password
} else {
	if (!$ltapi->auth("USER", "PASSWORD")) {
		echo("auth error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
		echo("<br/>request: ".$ltapi->lastUrl);
	} else {
		echo('auth successful. Session ID is <a href="?sid='.$ltapi->sid.'">'.$ltapi->sid.'</a>');
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////
//code starts here.

//IF YOU ARE GOING TO WIPE EVERYTHING YOU SHOULD MANUALLY CREATE THE ADMIN ACCOUNTS FOR JOE AND JAKE//////////////

if($full_erase){//option to wipe everything
	//get all users
	if (!$ltapi->getUsers()) {
		echo("<br/>getUsers error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
		echo("<br/>request: ".$ltapi->lastUrl);
	} else {
		$get_users = $ltapi->data["users"];
	}
	$delete_users = array();
	foreach($get_users as $k => $v){
		$delete_users[]= $v['name'];
	}
	//wipe all users
	if (!$ltapi->deleteUsers($delete_users)) {
		echo("<br/>deleteUsers error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
		echo("<br/>request: ".$ltapi->lastUrl);
	} else {
		echo("<br/>Users removed");
	}
	//get all channels
	if (!$ltapi->getChannels()) {
		echo("<br/>getChannels error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
		echo("<br/>request: ".$ltapi->lastUrl);
	} else {
		$get_channels = $ltapi->data["channels"];
	}
	$delete_channels = array();
	foreach($get_channels as $k => $v){ 
		$delete_channels[] = $v['name'];
	}
	//wipe all channels
	if (!$ltapi->deleteChannels($delete_channels)) {
		echo("<br/>deleteChannels error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
		echo("<br/>request: ".$ltapi->lastUrl);
	} else {
		echo("<br/>Channels removed");
	}
}

//get all the channels that already exist.
if (!$ltapi->getChannels()) {
	echo("<br/>getChannels error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
	echo("<br/>request: ".$ltapi->lastUrl);
} else {
	//echo("<br/>Channels list:");
	//arrayout($ltapi->data["channels"]);
	$get_channels = $ltapi->data["channels"];
	foreach($get_channels as $k => $v){ //indexed by channel name.
		$existing_channels[$v['name']] = $v;
	}
}

foreach($master_array as $k => $v){
		///////FOR USERNAME CREATE AND UPDATE////////////
		if($v['teacher_id'] != NULL){ //is a teacher
			if (!$ltapi->saveUser(array(
				"name" => $v['device_id'],
				"password" => md5($v['device_id']),
				"email" => "",
				"full_name" => $teacher_names[$v['teacher_id']], // UTF-8 is fully supported 
				"job" => "linx"
			))) {
				echo("<br/>saveUser error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
				echo("<br/>request: ".$ltapi->lastUrl);
			} else {
				//echo("<br/>User added or updated");
				}
			$temp_array=array('role_name'=>$v['role'], 'area'>$v['area'], 'location'=>$v['location']); //all channels this user needs to be added to.
			foreach($temp_array as $name => $channel){
				if(!isset($existing_channels[$channel])){ //if the channel doesnt exist, create and add the user to it.
					if (!$ltapi->addChannel($channel)) {
						echo("<br/>addChannel error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
						echo("<br/>request: ".$ltapi->lastUrl);
					} else {
						echo("<br/>Channel added");
					}
					//add user to the channel
					if (!$ltapi->addToChannel((string)$channel, $v['device_id'])) {
						echo("<br/>addToChannel error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
						echo("<br/>request: ".$ltapi->lastUrl);
					} else {
						echo("<br/>User added to a channel");
					}
					//update existing channel array
					$existing_channels[$channel] = $channel;
				}
				//channel already exists so just add user to it
				elseif(!$ltapi->addToChannel((string)$channel, $v['device_id'])) {
					echo("<br/>addToChannel error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
					echo("<br/>request: ".$ltapi->lastUrl);
				} else {
					echo("<br/>User added to a channel");
				}
			}
		}//FOR GROUPS 
		elseif($v['key'] != NULL){ //is a group
			if (!$ltapi->saveUser(array(
				"name" => $v['device_id'],
				"password" => md5($v['device_id']),
				"email" => "",
				"full_name" => $group_names[$v['key']], // UTF-8 is fully supported 
				"job" => "linx"
			))) {
				echo("<br/>saveUser error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
				echo("<br/>request: ".$ltapi->lastUrl);
			} else {
				echo("<br/>User added or updated");
				}
			$temp_array=array('channel_name'=>$v['channel_name'], 'area'>$v['area'], 'location'=>$v['location']); //all channels this user needs to be added to.
			foreach($temp_array as $name => $channel){
				if(!isset($existing_channels[$channel])){ //if the channel doesnt exist, create and add the user to it.
					if (!$ltapi->addChannel($channel)) {
						echo("<br/>addChannel error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
						echo("<br/>request: ".$ltapi->lastUrl);
					} else {
						echo("<br/>Channel added");
					}
					//add user to the channel
					if (!$ltapi->addToChannel((string)$channel, $v['device_id'])) {
						echo("<br/>addToChannel error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
						echo("<br/>request: ".$ltapi->lastUrl);
					} else {
						echo("<br/>User added to a channel");
					}
					//update existing channel array.
					$existing_channels[$channel] = $channel;

				}
				//channel already exists so just add user to it
				elseif(!$ltapi->addToChannel((string)$channel, $v['device_id'])) {
					echo("<br/>addToChannel error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
					echo("<br/>request: ".$ltapi->lastUrl);
				} else {
					echo("<br/>User added to a channel");
				}
			}
		}//add everyone to 'everyone' channel
		if (!$ltapi->addToChannel("Everyone", $v['device_id'])) {
		echo("<br/>addToChannel error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
		echo("<br/>request: ".$ltapi->lastUrl);
		} else {
		echo("<br/>User added to a channel");
		}
}//foreach master loop



//List users again -- look the new user is there
if (!$ltapi->getUsers()) {
	echo("<br/>getUsers error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
	echo("<br/>request: ".$ltapi->lastUrl);
} else {
	echo("<br/>Users list:");
	arrayOut($ltapi->data["users"]);
}

// if (!$ltapi->getChannels()) {
// 	echo("<br/>getChannels error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
// 	echo("<br/>request: ".$ltapi->lastUrl);
// } else {
// 	echo("<br/>Channels list:");
// 	arrayOut($ltapi->data["channels"]);
// }


// // Add channel
// if (!$ltapi->addChannel($v)) {
// 	echo("<br/>addChannel error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
// 	echo("<br/>request: ".$ltapi->lastUrl);
// } else {
// 	echo("<br/>Channel added");
// }

// // Add user to a channel
// if (!$ltapi->addToChannel("Test channel", array("ltapi_test"))) {
// 	echo("<br/>addToChannel error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
// 	echo("<br/>request: ".$ltapi->lastUrl);
// } else {
// 	echo("<br/>User added to a channel");
// }



// // Create channel role
// if (!$ltapi->saveChannelRole("Test channel", "Dispatcher", array(
// 	"listen_only" => false, 
// 	"no_disconnect" => true, 
// 	"allow_alerts" => false, 
// 	"to" => array()
// ))) {
// 	echo("<br/>saveChannelRole error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
// 	echo("<br/>request: ".$ltapi->lastUrl);
// } else {
// 	echo("<br/>Created a Dispatcher channel role");
// }
// if (!$ltapi->saveChannelRole("Test channel", "Driver", '{"listen_only":false, "no_disconnect":false, "allow_alerts": true, "to":["Dispatcher"]}')) {
//         echo("<br/>saveChannelRole error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
//         echo("<br/>request: ".$ltapi->lastUrl);
// } else {
//         echo("<br/>Created a Driver channel role");
// }


// // List channel roles
// if (!$ltapi->getChannelsRoles("Test channel")) {
// 	echo("<br/>getChannelsRoles error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
// 	echo("<br/>request: ".$ltapi->lastUrl);
// } else {
// 	echo("<br/>Roles defined in Test channel:");
// 	arrayOut($ltapi->data["roles"]);
// }

// // Remove the channel
// if (!$ltapi->deleteChannels(array("Test channel"))) {
// 	echo("<br/>deleteChannels error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
// 	echo("<br/>request: ".$ltapi->lastUrl);
// } else {
// 	echo("<br/>Channels removed");
// }
// // Delete the user we just added
// if (!$ltapi->deleteUsers(array("ltapi_test"))) {
// 	echo("<br/>deleteUsers error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
// 	echo("<br/>request: ".$ltapi->lastUrl);
// } else {
// 	echo("<br/>Users removed");
// }
// // List users one last time -- the new user is gone
// if (!$ltapi->getUsers()) {
// 	echo("<br/>getUsers error: ".$ltapi->errorCode." ".$ltapi->errorDescription);
// 	echo("<br/>request: ".$ltapi->lastUrl);
// } else {
// 	echo("<br/>Users list:");
// 	arrayOut($ltapi->data["users"]);
// }
/*
 * A simple helper function to aid data output in this example 
 */
function arrayOut($arr) {
	echo '<table border="1">';
	echo "<tr><td>".implode("</td><td>", array_keys($arr[0]))."</td></tr>";
	foreach ($arr as $row) {
		echo "<tr><td>".implode("</td><td>", array_map("printValue", $row))."</td></tr>";
	}
	echo "</table>";
}
function printValue($val){
	if (is_array($val)) {
		if (function_exists("json_encode")) {
			return json_encode($val);
		} else { 
			return implode(", ", $val);
		}
	} else {
		return $val;
	}
}
<?php

/**
 * PHP simple mailer controller
 * ============================
 * Author: Francisco Igor (franciscoigor@gmail.com)
 * 
 * This script reads a request from a HTML form (method="post") with: 
 *   - name (Sender name)
 *   - email (Sender email) 
 *   - text (Message content) 
 * 
 * Also, it can be possible to specify:
 *   - apikey (API code to access this service, POST or GET)
 *   - redir (Absolute URL of a redirection page. If the message was sent successfully, redirects to this site )
 *   - error (Absolute URL to redirect in case of error. If no error page is set, redir will be used)
 * 
 * Return 
 *   - If no "redir" is configured, this scripts returns a JSON result 
 *      - With { "ok" : "Message sent" } in successful execution
 *      - With { "error" : "Error message" } in case of error
 */

$date=date("d-m-Y H:i:s");

// result message
$message=[];

// get variables (only POST)
$from=$_POST["email"];
$name=strip_tags($_POST["name"]);
$text=strip_tags($_POST["text"]);
// this variables could be GET or POST 
$redir=$_REQUEST["redir"];
$error=$_REQUEST["error"]?:$redir;
$apikey=$_REQUEST["apikey"];


$referer=$_SERVER["HTTP_REFERER"];
$ipaddr=$_SERVER["REMOTE_ADDR"];
$host=$_SERVER["HTTP_HOST"];

// get the configuration rules
$config=require("config.php");

// get the recipient address
$recipient=$config["apikey"][$ipaddr];
$recipient=$recipient?:$config["ip"][$referer];
$recipient=$recipient?:$config["referer"][$referer];
$recipient=$recipient?:$config["host"][$host];


if ($recipient){
	//recipient found
	if (filter_var($from, FILTER_VALIDATE_EMAIL)){
		$headers="Reply-to: $from". "\r\n";
		$message="Message from $name ($from):\n\n $text\n\n Date: $date\n\n Ip: $ipaddr\n\n Reference: $referer";
		//$message.="\n\nDebug:$server",$headers);
		$server=json_encode($_SERVER);
		$result=mail($recipient,"Message from $host ($date)",$message,$headers);
		if (!$result){
			$message["error"]="Send error";
		}else{
			$message["ok"]="Message sent";
		}
	}else{
		//email not valid
		header("HTTP/1.0 400 Bad request");
		$message["error"]="Invalid email";
	}

}else{
	//recipient not found
	header("HTTP/1.0 403 Prohibited");
	$message["error"]="Not autorized";
}


if ($redir){
	if ($message["ok"]){
		header("Location: {$redir}");
	}else{
		header("Location: {$error}");		
	}
	die();
}

echo json_encode($message);
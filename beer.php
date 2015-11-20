<?php

require ("untappdPHP.php");

// Function for Vowels
function checkifvowel($string) {

	$v = strtolower($string[0]);

	$beer_name = explode(" ", $string);

	if (sizeof($beer_name) > 1 && strtolower($beer_name[0]) == "the")
	{
		return "";
	}
	else if ($v == "a" || $v == "e" || $v == "i" || $v == "o" || $v == "u")
	{
		return "an";
	}
	else
	{
		return "a";
	}
}

$client_id = "";
$client_secret = "";
$slack_token = "";  //loose slack-token auth
$ut = new UntappdPHP($client_id, $client_secret, "");

header("Content-type: application/json");


$data = array();

if (isset($_POST)) {

	$event_json = json_decode(json_encode($_POST));

	if (sizeof($event_json) == 0) {
		$data["text"] = "Post is not empty, but has no results";

	} else {
		if (isset($event_json) && isset($event_json->text) && $event_json->token == $slack_token) {
			
			$commands = explode(' ', $event_json->text);
			while(strtolower($commands[0]) == "untappd"){
				arr_shift($commands);
			}
			
			$command = $commands[0];
			
			if($command == "user"){
				$user = join(" ",array_slice($commands,1));
				
				$result = $ut->get("/user/checkins/" . $user, array("limit"=>0));
				
				if($result->meta->code == 200){
					
					if($result->response->checkins->count > 0){
					$last = $result->response->checkins->items[0];
					
					$text = "*". $last->user->user_name . "* last drank a *" . $last->beer->beer_name . "* at " . (count($last->venue) ==0?"an undisclosed location ":$last->venue->venue_name) . ",  " . $last->created_at;
					}else{
						$text = "*" . $user . "* isn't very fun. :(";
					}
					$data["text"] = $text;
					
					//echo json_encode($data);
				}
				
			}elseif($command=="help"){
				$text = "## /untappd Beer Bot \n";
				$text .= "* Get last Checkin for user /untappd user [username] \n";
				$text .= "> /untappd user bsurma \n";
				$text .= "* Get Beer info \n";
				$text .= "> /untappd beer Manny's \n"; 
				$data["text"] = $text;
			}else {
			
			
				$beer_name = explode("beer", $event_json->text);
				if ( ! array_key_exists('1', $beer_name) ){
					$beer_name =  $event_json->text;
				}
				
				
	
				if ( empty($beer_name ) ){
					$data["text"] = "You didn't search for anything! Please try again!";
					echo json_encode($data);
					exit;
					}
				
				$data['beer_name'] = $beer_name;
	
				$real_beer_name = trim($beer_name);
	
				$result = $ut->get("/search/beer", array("q" => $real_beer_name));
	
				if ($result->meta->code == 200) {
	
					if ($result->response->beers->count == 0) {
						$data["text"] = "No results found for *".$real_beer_name."*";
					} else {
						$beer_id = $result->response->beers->items[0]->beer->bid;
	
						$data["beer_id"] = $beer_id;
	
						$result = $ut->get("/beer/info/".$beer_id);
	
						if ($result->meta->code == 200) {
							$beer = $result->response->beer;
							$data["parse"] = "full";
							$data["text"] = "*".$beer->beer_name . "* by *" . $beer->brewery->brewery_name . "* is ".checkifvowel($beer->beer_style)." ".$beer->beer_style ." at ".$beer->beer_abv."% with a rating of *" . round($beer->rating_score, 3) . "* - http://untappd.com/beer/".$beer->bid;
						} else {
							$data["text"] = $result->meta->code . " error on Beer Lookup - please try again! Error - ". $result->meta->error_detail;
						}
					}
				} else {
					$data["url"] = $url;
					$data["text"] = $result->meta->code . " error on Beer Search - please try again! Error - ". $result->meta->developer_friendly;
					$data["search_term"] = $real_beer_name;
				}
			}
		}
		else {
			$v = json_encode($_POST);
			$data["text"] = "Empty post response - " . $v;
		}
	}
} else {
	$data["text"] = "Post is empty";
}


$data["response_type"] = "in_channel";
echo json_encode($data);


?>

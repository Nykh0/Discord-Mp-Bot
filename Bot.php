<?php
/*
 * Discord bot for maniaplanet
 * Display the rank of a player in his continent and other fun commands !
 * V 1.1
 * Made by Nykho, 22016
 * Credit to Kleis Auke#5462 for the help!
 * Thx also to Nerpson#1996 and HYPE#4144 for their help too !
 */

ini_set('memory_limit', '-1'); // REMOVING PHP MEMORY LIMIT

include __DIR__ . '/vendor/autoload.php';

use Discord\Bot\CommandBot;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Voice\VoiceClient;
use Maniaplanet\WebServices;
use Manialib\Formatting\String;

$MpBot = new CommandBot([
	'bot-token' => getenv('DISCORD_BOT_TOKEN'),
	'name' => 'MpBot'
]);
$MpBot->on('ready', function ($config, $discord, CommandBot $MpBot) {
	
    $MpBot->getLogger()->addInfo('Bot is running.', [
        'user' => "{$discord->username}#{$discord->discriminator}",
        'prefix' => $config['prefix'],
    ]);
    $MpBot->getLogger()->addNotice("Connected to " . count($discord->guilds) . " servers");
});

$MpBot->addCommand('coin', function ($params, Message $message, CommandBot $bot, Discord $discord) {
    $images = ["heads.png", "tails.png"];
    $result = $images[rand(0, 1)];

    $message->channel->sendFile("image/coin/{$result}", $result)->then(function ($response) use ($bot) {
        $bot->getLogger()->addInfo("The file was sent!");
    })->otherwise(function (\Exception $e) use ($bot) {
        $bot->getLogger()->addInfo("There was an error sending the file: {$e->getMessage()}");
    });
});
$MpBot->addCommand('rank', function ($params, Message $message, CommandBot $bot, Discord $discord) {
	if (count($params) == 1) {
					$login = $params[0];
					$nickname = ReturnNickname($login);
					$rankS = ReturnRank($login,'SMStorm'); //Seeking the rank of the player, function at the bottom of this script
					$rankT = ReturnRank($login,'TMStadium'); //Seeking the rank of the player, function at the bottom of this script
					if (is_numeric($rankS) && is_numeric($rankT)) {
					$message->channel->sendMessage("The rank of $nickname in Storm is : $rankS and the rank in Stadium is : $rankT",false);
					}
					else {
					$message->channel->sendMessage("This login does not exist !",false);
					}

				}
				elseif (count($params) == 2) {
					$login = $params[0];
					$title = $params[1];
					$titleId = ReturnTitle($title);
					$title = ucfirst(strtolower($title));
					$nickname = ReturnNickname($login);
					$rank = ReturnRank($login,$titleId); //Seeking the rank of the player, function at the bottom of this script

					if ($rank != -1) {
					if (is_numeric($rank)) {
					$message->channel->sendMessage("The rank of $nickname in $title is : $rank",false);
					}
					else {
						$message->channel->sendMessage("This login does not exist !",false);
					}
				}
				else {
					$message->channel->sendMessage("This Title does not exist !",false);

				}
				}
				else {
					$message->reply('incorrect use of !rank ! See **!help** for more information about ;)');
				}
				});

$MpBot->addCommand('help', function ($params, Message $message, CommandBot $bot, Discord $discord) {
	$msg = 'Here some help for my commands !

**!rank** (Display the rank of a maniaplanet login). Use : !rank <login> <title> (default title = Storm)
Title usable : Storm, Elite, Royal, Stadium, Canyon, Valley, Combo, Obstacle, Esltm, Galaxy, Rpg, Speedball, Lagoon
Send a PM to Nykho#8970 if you want to add your title pack to the list !
***Note : login are case sensitive, not the titlepack***

**!kappa** (Display a beautiful face of our lord Kappa.

**!ping** (Guess ? ;) )

**!yo** (An absolutly basic welcome message :) 

**!hey** (What\'s up ?)

**!how** (Ask if you\'re doing well :D)

**!coin** (Flip the coin of the discord logo)
**!hylis** (Display a beautiful mixed face between kappa and Hylis (thx xrayjay !)';
					$message->reply($msg,false);
});

$MpBot->addCommand('yo', function ($params, Message $message, CommandBot $bot, Discord $discord) {
	$message->reply("welcome ! Type !help to know what I can do ;)",false);
});

$MpBot->addCommand('hey', function ($params, Message $message, CommandBot $bot, Discord $discord) {
	$message->channel->sendMessage("What's up ?",false);
});

$MpBot->addCommand('kappa', function ($params, Message $message, CommandBot $bot, Discord $discord) {
	$message->channel->sendFile('image/kappa.png', 'kappa.png')->then(function ($response) {
							echo "The file was sent!";
					})->otherwise(function (\Exception $e){
							echo "There was an error sending the file: {$e->getMessage()}";
					});
					//$message->reply("http://bit.do/kappaimg",false); //While the function is broken, using a link to send the kappa
});

$MpBot->addCommand('how', function ($params, Message $message, CommandBot $bot, Discord $discord) {
	$message->channel->sendMessage("How you're doing ?",false);
});

$MpBot->addCommand('ping', function ($params, Message $message, CommandBot $bot, Discord $discord) {
	$message->reply('pong!');
});

$MpBot->addCommand('hylis', function ($params, Message $message, CommandBot $bot, Discord $discord) {
	$message->channel->sendFile("image/hyliskappa.png", $result)->then(function ($response) use ($bot) {
        $bot->getLogger()->addInfo("The file was sent!");
    })->otherwise(function (\Exception $e) use ($bot) {
        $bot->getLogger()->addInfo("There was an error sending the file: {$e->getMessage()}");
    });
});

$MpBot->start();

//////////////////////////////////////////////////////////////////
/////// Function used in this script are placed here /////////////
//////////////////////////////////////////////////////////////////

//Return the rank of a given player on a giver title
function ReturnRank($login,$titleId) {
					if ($titleId == "Error ! Title not recognized") {
						return -1;
					}
					$username = 'nicolas1001|SMDiscord';
					$password = 'ShootmaniaDiscord';
					$players = new \Maniaplanet\WebServices\Players($username, $password);
					$rankings = new \Maniaplanet\WebServices\Rankings($username, $password);
					try {
					$player = $players->get($login);
					/*$test = json_encode(($rankings->getMultiplayerPlayer($titleId,$login)),true);
					$params = explode("u'", $test);
					$reconstruction = explode("\"", $params[0]); // To get out the rank :D
					$params = $reconstruction;
					$nbCase = count($params);
					return $params[37];*/
										
					$obj = $rankings->getMultiplayerPlayer($titleId,$login);
					$rank = $obj->ranks[1]->rank;
					return $rank;
					}
					catch(Exception $e) {
						return "Error ! Login incorrect !";
					}
}
//Return the title sheme of maniaplanet by giving a title general name
function ReturnTitle($titleId) {
	$titleId = ucfirst(strtolower($titleId));
	switch ($titleId) {
	case "Storm" : return('SMStorm');
	case "Lagoon" : return('TMLagoon');
	case "Canyon" : return('TMCanyon');
	case "Speedball": return ('SpeedBall@steeffeen');
	case "Royal" : return('SMStormRoyal@nadeolabs');
	case "Stadium" : return('TMStadium');
	case "Elite" : return('SMStormElite@nadeolabs');
	case "Valley" : return('TMValley');
	case "Combo" : return('SMStormCombo@nadeolabs');
	case "Siege" : return('SMStormSiege@nadeolabs');
	case "Obstacle" : return('obstacle@steeffeen');
	case "Esltm" : return('esl_comp@lt_forever');
	case "Galaxy" : return('GalaxyTitles@domino54');
	case "Rpg" : return('RPG@tmrpg');
	case "Tmplus" : return('TMPLUS@redix');
	default : return('Error ! Title not recognized');
	}
}

// Return the nickname in game of a given string login
function ReturnNickname($login){
					$username = 'nicolas1001|SMDiscord';
					$password = 'ShootmaniaDiscord';
					$players = new \Maniaplanet\WebServices\Players($username, $password);
					$rankings = new \Maniaplanet\WebServices\Rankings($username, $password);
					try {
					$player = $players->get($login)->nickname;
					$string = new \Manialib\Formatting\String($player);
					$nicknameFinal = $string->stripAll();
					return $nicknameFinal;
					}
					catch(Exception $e) {
						return "Error ! Login incorrect !";
					}
}

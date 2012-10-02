<?php
//require_once('/wp-content/php/dbdefs.php');
//require_once('/wp-content/php/topicselection.class.php');

require_once(ABSPATH . 'wp-content/php/dbdefs.php');
require_once(ABSPATH . 'wp-content/php/topicselection.class.php'); 



error_reporting(E_ERROR | E_WARNING | E_PARSE);

//$topic=$_GET["topic"];
$topic = get_the_title();
$current_user = wp_get_current_user();
$wp_user_id = $current_user->ID;
$initial_state = $_GET["state"];
$submitMyScorecard = $_POST["submitMyScorecard"];

$con = mysql_connect(DBHOST, DBUSER, DBPASSWORD);
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }

$introSelection = -1;
$articleSelection = -1;
$scoreCardSelection = -1;
$linksSelection = -1;
$numberOfResources = 0;

mysql_select_db(DBSCHEMA, $con);
if ($submitMyScorecard)
{
	//foreach ($_POST as $key => $value){
	//	echo "key: " . $key . "   value: " . $value;
	//}

	for ($i=1; $i<=8; $i++)
	{
		$scoreItem = new ScoreItem();
		$scoreItem->id = $_POST["myscorecard_record" . $i];
		$scoreItem->originalScoreId = $_POST["myscorecard_scoreid" . $i];
		$scoreItem->score = $_POST["myscorecard_userrating" . $i];
		$scoreItem->description = $_POST["myscorecard_description" . $i];
		$arrScoreItems[$i-1] = $scoreItem;
		//echo "Scorecard item " . $i . " is " . $arrScoreItems[$i-1]->id . " " . $arrScoreItems[$i-1]->originalScoreId . " " . $arrScoreItems[$i-1]->score . " " . $arrScoreItems[$i-1]->description . "<br/>";
		if (!empty($scoreItem->id) )
		{
			if ($scoreItem->id == -1)
			{
				$sql = "Insert into user_score (user_id, score_id, user_rating, description, approval_status) values ('" . $wp_user_id . "', '" . $scoreItem->originalScoreId .
					"', '" . $scoreItem->score . "', '" . mysql_real_escape_string($scoreItem->description) . "', '1')";
			}
			else
			{
				$sql = "Update user_score set user_rating = '" . $scoreItem->score . "', description = '" . mysql_real_escape_string($scoreItem->description) .
					"' where id = " . $scoreItem->id;
			}
			$result = mysql_query($sql);
			if ($result != 1)
			{
				echo "Error inserting user_score --- Result = " . $result . "  error: " . mysql_error();
				echo "SQL is " . $sql;
				exit();
			}

		}
	}
}

$sql = "Select * from topic where topic.short_name = '".$topic ."'";
//echo $sql;
$result = mysql_query($sql);
$row = mysql_fetch_array($result);
$title = $row['title'];
//echo "Title is " . $title;
$sql="SELECT * FROM selection inner join topic on selection.topic=topic.id WHERE topic.short_name = '".$topic."'";

$result = mysql_query($sql);
$selectionIndex = 0;
while($row = mysql_fetch_array($result))
  {
  	$aSelection = new Selection();
	// row[0] yields id of selection
	$aSelection->id = $row[0];
	$aSelection->type = $row['type'];
	$aSelection->article = convertUtf8(nl2br($row['article_text']));
	$aSelection->thumbnail = $row['thumbnail_image'];
	$aSelection->image = $row['graphic_image'];
	$aSelection->breadcrumb = convertUtf8(nl2br($row['breadcrumb']));
	$aSelection->videoEmbedCode = $row['video_embed_code'];
	$aSelection->videoURL = $row['video_url'];

	$topicSelections[$selectionIndex] = $aSelection;
	switch ($aSelection->type)
	{
	case Selection::INTRO:
		$introSelection = $selectionIndex;
		break;
	case Selection::ARTICLE:
		$articleSelection = $selectionIndex;
		break;
	case Selection::VIDEO:
		$resourceSelections[$numberOfResources] = $selectionIndex;
		$resourceHtml[$numberOfResources] = $aSelection->videoEmbedCode;
		//$resourceHtml[$numberOfResources] = str_replace("\"", "\\\"", $resourceHtml[$numberOfResources]);
		$numberOfResources = $numberOfResources + 1;
		break;
	case Selection::GRAPHIC:
		$resourceSelections[$numberOfResources] = $selectionIndex;
		$resourceHtml[$numberOfResources] = "<img src='" . ABSURL . "wp-content/images/" . $topic . "/" . $aSelection->image . "' border='0' />";
		$resourceHtml[$numberOfResources] = str_replace("\"", "\\\"", $resourceHtml[$numberOfResources]);
		$numberOfResources = $numberOfResources + 1;
		break;
	case Selection::SCORECARD:
		$scoreCardSelection = $selectionIndex;
		break;
	case Selection::LINKS:
		$linksSelection = $selectionIndex;
		break;
	default:
		break;
	}
	$selectionIndex = $selectionIndex + 1;
  }
  $numberOfSelections = $selectionIndex;

  $numberOfScores = 0;
  $numberOfUserScores = 0;

  if ($scoreCardSelection != -1)
  {
	$sql = "SELECT * from score where score.selection = " . $topicSelections[$scoreCardSelection]->id;
	//echo $sql;

	$result = mysql_query($sql);
	while($row = mysql_fetch_array($result))
	  {
		$aScore = new ScoreItem();
		$aScore->id = $row['id'];
		$aScore->who = $row['who'];
		$aScore->score = $row['score'];
		$aScore->description = convertUtf8($row['description']);
		$scoreCardItems[$numberOfScores] = $aScore;
		$numberOfScores = $numberOfScores + 1;
	  }
	if (isset($wp_user_id) && $wp_user_id > 0)
	{
		$sql = "select us.id, sc.id, us.user_id, sc.who, us.user_rating, us.description, us.approval_status from user_score us " .
			"inner join score sc on us.score_id=sc.id where us.user_id=" . $wp_user_id . " and sc.selection= " . $topicSelections[$scoreCardSelection]->id;
		//echo $sql;
		$result = mysql_query($sql);
		//echo "<br/>After sql error is " . mysql_error();
		while($row = mysql_fetch_array($result))
		{
			$aScore = new ScoreItem();
			$aScore->id = $row[0];
			$aScore->originalScoreId = $row[1];
			$aScore->who = $row['who'];
			$aScore->score = $row['user_rating'];
			$aScore->description = $row['description'];
			$userScoreCardItems[$numberOfUserScores] = $aScore;
			$numberOfUserScores = $numberOfUserScores + 1;
		}
		//echo "Number of user scores is " . $numberOfUserScores;
		//if ($numberOfUserScores > 0)
		//	echo " user score card item 0 id is: " . $userScoreCardItems[0]->id;
		
		// set up user scores but have them as blank
		// system will still see them as not entered
	}
	if ($numberOfUserScores == 0)
	{
		while ($numberOfUserScores < $numberOfScores)
		{
			$aScore = new ScoreItem();
			$aScore->id = -1;
			$aScore->originalScoreId = $scoreCardItems[$numberOfUserScores]->id;
			$aScore->who = $scoreCardItems[$numberOfUserScores]->who;
			$aScore->score = -1;
			$aScore->description = "";
			$userScoreCardItems[$numberOfUserScores] = $aScore;
			$numberOfUserScores = $numberOfUserScores + 1;
		}
	}
	//echo "Number of user scores is " . $numberOfUserScores;
	//if ($numberOfUserScores > 0)
	//{
	//	echo " user score card item 0 id is: " . $userScoreCardItems[0]->id;
	//	echo " originalScoreId: " . $userScoreCardItems[0]->originalScoreId;
	//	echo " scoreCardItems id is " . $scoreCardItems[0]->id;
	//}
	//exit();
  }

mysql_close($con);

function userScoresAreEntered()
{
	global $userScoreCardItems, $numberOfUserScores;
	//echo "numberOfUserScores is " . $numberOfUserScores;
	//echo "userScoreCardItems[0]->id is " . $userScoreCardItems[0]->id;
	if ($numberOfUserScores == 0 || $userScoreCardItems[0]->id == -1)
	{
		return false;
	}
	else
	{
		return true;
	}
}

function convertUtf8($text)
{
$quotes = array(
    "\xC2\xAB"     => '"', // « (U+00AB) in UTF-8
    "\xC2\xBB"     => '"', // » (U+00BB) in UTF-8
    "\xE2\x80\x98" => "'", // ‘ (U+2018) in UTF-8
    "\xE2\x80\x99" => "'", // ’ (U+2019) in UTF-8
    "\xE2\x80\x9A" => "'", // ‚ (U+201A) in UTF-8
    "\xE2\x80\x9B" => "'", // ? (U+201B) in UTF-8
    "\xE2\x80\x9C" => '"', // “ (U+201C) in UTF-8
    "\xE2\x80\x9D" => '"', // ” (U+201D) in UTF-8
    "\xE2\x80\x9E" => '"', // „ (U+201E) in UTF-8
    "\xE2\x80\x9F" => '"', // ? (U+201F) in UTF-8
    "\xE2\x80\xB9" => "'", // ‹ (U+2039) in UTF-8
    "\xE2\x80\xBA" => "'", // › (U+203A) in UTF-8
    "\xC3\xA2\xE2\x82\xAC\xE2\x84\xA2" => "'",
);
$text = strtr($text, $quotes);
//echo $str;
//$str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
//$text = str_replace(chr(130), ',', $text);    // baseline single quote
//$text = str_replace(chr(132), '"', $text);    // baseline double quote
//$text = str_replace(chr(133), '...', $text);  // ellipsis
//$text = str_replace(chr(145), "'", $text);    // left single quote
//$text = str_replace(chr(146), "'", $text);    // right single quote
//$text = str_replace(chr(147), '"', $text);    // left double quote
//$text = str_replace(chr(148), '"', $text);    // right double quote

//$text = mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8');

// First, replace UTF-8 characters.
//$text = str_replace(
//array("â€™", "\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
//array("'", "'", "'", '"', '"', '-', '--', '...'),
//$text);
return $text;
}


function strToHex($string)
{
    $hex='';
    for ($i=0; $i < strlen($string); $i++)
    {
        $hex .= " " . dechex(ord($string[$i]));
    }
    return $hex;
}

?>

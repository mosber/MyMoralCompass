<?php 


include(ABSPATH . 'wp-content/php/dbdefs.php');
include(ABSPATH . 'wp-content/php/topic.class.php');
include(ABSPATH . 'wp-content/php/topictimeperiod.class.php');

// Report simple running errors
error_reporting(E_ERROR | E_WARNING | E_PARSE);

$con = mysql_connect(DBHOST, DBUSER, DBPASSWORD);
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }
mysql_select_db(DBSCHEMA, $con);

$lstTimePeriods = array();
$numberOfTimePeriods = 0;
  
$sql = "SELECT time_period.id, time_period.name, topic.id, topic.short_name, topic.title, topic.description
FROM topic INNER JOIN time_period ON topic.time_period = time_period.id
ORDER BY time_period.id, topic.id";
//echo $sql;
$result = mysql_query($sql);
while($row = mysql_fetch_array($result))
{
  	//echo "time period: " . $row['name'] . "  topic: " . $row['title'] . "<br/>";
  	$timePeriod = timePeriodPresent($lstTimePeriods, $row[0]);
  	if ($timePeriod == null)
  	{
  		$timePeriod = new TopicTimePeriod();
  		$timePeriod->id = $row[0];
  		$timePeriod->timePeriodName = $row[1];
  		$lstTimePeriods[$numberOfTimePeriods] = $timePeriod;
  		$numberOfTimePeriods++;
  	}
  	$topic = new TopicSpec();
  	$topic->id = $row[2];
  	$topic->shortName = $row['short_name'];
  	$topic->title = $row['title'];
  	$topic->description = $row['description'];
  	array_push($timePeriod->topics, $topic);
}

mysql_close($con);

function timePeriodPresent($lstTimePeriods, $timePeriodId)
{
	foreach ($lstTimePeriods as $timePeriod)
	{
		if ($timePeriod->id == $timePeriodId)
			return $timePeriod;
	}
	return null;
}

?>
<aside class="sidebar articles-index left">
	<?php 
	foreach ($lstTimePeriods as $timePeriod)
	{
	?>
    <section class=article-group>
        <h6 class="group-title padded-title"><?php echo $timePeriod->timePeriodName?></h6>
        <ul class=group-list>
        <?php foreach ($timePeriod->topics as $topic)
        {
        ?>
            <li><a href="index.php/<?php echo $topic->shortName ?>" title="<?php echo $topic->title ?>" data-excerpt="<?php echo $topic->description?>"><?php echo $topic->title ?></a></li>
        <?php
        }
        ?>
        </ul>
    </section>
    <?php
	} 
    ?>

</aside>
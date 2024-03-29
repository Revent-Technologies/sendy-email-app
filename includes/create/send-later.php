<?php include('../functions.php');?>
<?php include('../login/auth.php');?>
<?php include('../helpers/short.php');?>
<?php

	//get POST variables
	$timezone = mysqli_real_escape_string($mysqli, $_POST['timezone']);
	date_default_timezone_set($timezone);//set timezone
	$campaign_id = isset($_POST['campaign_id']) && is_numeric($_POST['campaign_id']) ? mysqli_real_escape_string($mysqli, (int)$_POST['campaign_id']) : exit;
	$email_lists = mysqli_real_escape_string($mysqli, $_POST['email_lists']);
	$email_lists_excl = mysqli_real_escape_string($mysqli, $_POST['email_lists_excl']);
	$email_lists_segs = mysqli_real_escape_string($mysqli, $_POST['email_lists_segs']);
	$email_lists_segs_excl = mysqli_real_escape_string($mysqli, $_POST['email_lists_segs_excl']);
	$app = isset($_POST['app']) && is_numeric($_POST['app']) ? mysqli_real_escape_string($mysqli, (int)$_POST['app']) : exit;
	$send_date = mysqli_real_escape_string($mysqli, $_POST['send_date']);
	$total_recipients = empty($_POST['total_recipients2']) ? 0 : (int)trim($_POST['total_recipients2']);
	$hour = mysqli_real_escape_string($mysqli, $_POST['hour']);
	$min = mysqli_real_escape_string($mysqli, $_POST['min']);
	$ampm = mysqli_real_escape_string($mysqli, $_POST['ampm']);
	$the_date = strtotime("$send_date $hour.$min$ampm");
	
	//Check if monthly quota needs to be updated
	$q = 'SELECT allocated_quota, current_quota FROM apps WHERE id = '.$app;
	$r = mysqli_query($mysqli, $q);
	if($r) 
	{
		while($row = mysqli_fetch_array($r)) 
		{
			$allocated_quota = $row['allocated_quota'];
			$current_quota = $row['current_quota'];
		}
	}
	//Update quota if a monthly limit was set
	if($allocated_quota!=-1)
	{
		//Get the existing number of quota_deducted
		$q = 'SELECT quota_deducted FROM campaigns WHERE id = '.$campaign_id;
		$r = mysqli_query($mysqli, $q);
		if ($r) 
		{
			while($row = mysqli_fetch_array($r)) 
			{
				$current_quota_deducted = $row['quota_deducted']=='' ? 0 : $row['quota_deducted'];
			}
			$updated_quota = ($current_quota + $total_recipients) - $current_quota_deducted;
		}
		
		//if so, update quota
		$q = 'UPDATE apps SET current_quota = '.$updated_quota.' WHERE id = '.$app;
		mysqli_query($mysqli, $q);
	}
	
	//Schedule the campaign
	$q = 'UPDATE campaigns SET send_date = "'.$the_date.'", lists = "'.$email_lists.'", lists_excl = "'.$email_lists_excl.'", segs = "'.$email_lists_segs.'", segs_excl = "'.$email_lists_segs_excl.'", timezone = "'.$timezone.'", quota_deducted = '.$total_recipients.' WHERE id = '.$campaign_id;
	$r = mysqli_query($mysqli, $q);
	if ($r) header("Location: ".get_app_info('path')."/app?i=".$app);
	else echo 'Error: Unable to schedule campaign.';
?>

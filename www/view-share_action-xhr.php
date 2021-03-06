<? if (empty($script_code)): exit; endif;

// Check if we are creating something new
if ($_POST['content_status'] == "uncreated"):

	$share_info = [
		"share_id" => null,
		"author_id" => $login_status['user_id'],
		"relationship_type" => $_POST['relationship_type'],
		"relationship_to" => $_POST['relationship_to'] ?? (integer)null,
		"content_status" => "draft",
		];

	if (empty($share_info['relationship_to'])): unset($share_info['relationship_to']); endif;

	// We will check for duplicates to ensure the share is uniquely identified
	$share_id_temp = $count_temp = null;
	while (empty($share_id_temp)):

		$count_temp++;
		if ($count_temp > 5): json_output("failure", "Share not unique."); endif;

		$share_id_temp = random_number(9);

		// This statement comes from index.php
		$result_temp = pg_execute($database_connection, "check_share_id_statement", ["share_id"=>$share_id_temp]);
		if (database_result($result_temp) !== "success"): json_output("failure", "Database #177."); endif;
		while ($row_temp = pg_fetch_assoc($result_temp)):
			$share_id_temp = null;
			continue 2; endwhile;
		
		endwhile;

	$share_info['share_id'] = $share_id_temp;

	// Prepare share insert statement
	$statement_temp = database_insert_statement("shares_main", $share_info, "share_id");
	$result_temp = pg_prepare($database_connection, "create_share_statement", $statement_temp);
	if (database_result($result_temp) !== "success"): json_output("failure", "Database #178."); endif;

	// Insert into the database
	$result_temp = pg_execute($database_connection, "create_share_statement", $share_info);
	if (database_result($result_temp) !== "success"): json_output("failure", "Database #179."); endif;

	// Check the button wasn't used many times
//	if (empty($_POST['create_ticket']) || empty($_COOKIE[$_POST['create_ticket']])): json_output("failure", "Pressed already."); endif;
//	setcookie($_POST['create_ticket'], null, time()-3600);

	$redirect_url = "/?view=share&parameter=".$share_info['share_id']."&action=edit";
	json_output("redirect", "<a href='". $redirect_url ."'>". $translatable_elements['click-here-if-you-are-not-redirected'][$language_request] ."</a>", $redirect_url);

	endif;

// Check if share id exists
$share_id = $_POST['share_id'] ?? null;
if (empty($share_id)): json_output("failure", $translatable_elements['not-found'][$language_request]); endif;

// Check content status
$content_status_array = [ "draft", "published", "pending", "review" ];
$content_status = $_POST['content_status'] ?? null;
if (!(empty($content_status)) && !(in_array($content_status, $content_status_array))): json_output("failure", $translatable_elements['invalid-status'][$language_request]); endif;

// We are not creating something new, so make sure it has content
$content_draft = $_POST['content_draft'] ?? null;
$content_draft = trim($content_draft);
if (empty($content_draft)): json_output("failure", $translatable_elements['empty-content'][$language_request]); endif;

// Prepare archive insert statement
$archive_temp = [
	"content_archive_id" => null,
	"user_id" => null,
	"change_field" => null,
	"change_value" => null,
	"change_time" => null,
	];
$statement_temp = database_insert_statement("shares_archive", $archive_temp, "content_archive_id");
$result_temp = pg_prepare($database_connection, "archive_insert_statement", $statement_temp);
if (database_result($result_temp) !== "success"): json_output("failure", "Database #180."); endif;

// If a change has happened to the draft...
if ($content_draft !== $share_info['content_draft']):

	if ( ($share_info['content_status'] == "pending") && ( ($login_status['user_id'] == $share_info['author_id']) || ($login_status['level'] !== "administrator") ) ):
		json_output("failure", "Pending review.");
		endif;

	// Draft values
	$draft_temp = [
		"share_id" => $share_info['share_id'],
		"content_draft" => $content_draft,
		];

	// Prepare draft update statement
	$statement_temp = database_insert_statement("shares_main", $draft_temp, "share_id");
	$result_temp = pg_prepare($database_connection, "update_share_draft_statement", $statement_temp);
	if (database_result($result_temp) !== "success"): json_output("failure", "Database #181."); endif;

	// Update draft
	$result_temp = pg_execute($database_connection, "update_share_draft_statement", $draft_temp);
	if (database_result($result_temp) !== "success"): json_output("failure", "Database #182."); endif;

	// Also, archive the draft
	$archive_temp = [
		"content_archive_id" => random_number(9),
		"user_id" => $login_status['user_id'],
		"change_field" => "content_draft",
		"change_value" => $content_draft,
		"change_time" => time(),
		];
	$result_temp = pg_execute($database_connection, "archive_insert_statement", $archive_temp);
	if (database_result($result_temp) !== "success"): json_output("failure", "Database #183."); endif;

	endif;

// If there is an administrator AND not the author AND we publish...
if ( ($login_status['level'] == "administrator") && ($login_status['user_id'] !== $share_info['author_id']) && ($content_status == "published") ):

	$published_temp = [
		"share_id" => $share_info['share_id'],
		"content_published" => $content_draft,
		"content_status" => "published",
		];

	// Add time if it's never been published before
	$published_time = null;
	if (empty($share_info['published_time'])):
		$published_time = $_POST['published_time'] ?? time();
		$published_temp['published_time'] = $published_time;
		endif;

	// Prepare published update statement
	$statement_temp = database_insert_statement("shares_main", $published_temp, "share_id");
	$result_temp = pg_prepare($database_connection, "update_share_published_statement", $statement_temp);
	if (database_result($result_temp) !== "success"): json_output("failure", "Database #184."); endif;

	// Update published part
	$result_temp = pg_execute($database_connection, "update_share_published_statement", $published_temp);
	if (database_result($result_temp) !== "success"): json_output("failure", "Database #185."); endif;

	// Also, add the content to archive
	$archive_temp = [
		"content_archive_id" => random_number(9),
		"user_id" => $login_status['user_id'],
		"change_field" => "content_published",
		"change_value" => $content_draft,
		"change_time" => time(),
		];
	$result_temp = pg_execute($database_connection, "archive_insert_statement", $archive_temp);
	if (database_result($result_temp) !== "success"): json_output("failure", "Database #186."); endif;

	// Also, add the content_status to archive
	$archive_temp = [
		"content_archive_id" => random_number(9),
		"user_id" => $login_status['user_id'],
		"change_field" => "content_status",
		"change_value" => "published",
		"change_time" => time(),
		];
	$result_temp = pg_execute($database_connection, "archive_insert_statement", $archive_temp);
	if (database_result($result_temp) !== "success"): json_output("failure", "Database #187."); endif;

	// Also, add the published time to the archive too, if it's been updated
	if (!(empty($published_time))):
		$archive_temp = [
			"content_archive_id" => random_number(9),
			"user_id" => $login_status['user_id'],
			"change_field" => "published_time",
			"change_value" => $published_time,
			"change_time" => time(),
			];
		$result_temp = pg_execute($database_connection, "archive_insert_statement", $archive_temp);
		if (database_result($result_temp) !== "success"): json_output("failure", "Database #188."); endif;
		endif;

	endif;


// When you submit it for publication then mark it 'pending' and freeze changes
if (in_array($content_status, [ "pending", "review" ])):	
	
	$status_temp = [
		"share_id" => $share_info['share_id'],
		"content_status" => $content_status,
		];
			
	// Prepare published update statement
	$statement_temp = database_insert_statement("shares_main", $status_temp, "share_id");
	$result_temp = pg_prepare($database_connection, "update_share_pending_statement", $statement_temp);
	if (database_result($result_temp) !== "success"): json_output("failure", "Database #189."); endif;

	// Execute new content status
	$result_temp = pg_execute($database_connection, "update_share_pending_statement", $status_temp);
	if (database_result($result_temp) !== "success"): json_output("failure", "Database #190."); endif;

	// Also, add to archive
	$archive_temp = [
		"content_archive_id" => random_number(9),
		"user_id" => $login_status['user_id'],
		"change_field" => "content_status",
		"change_value" => $content_status,
		"change_time" => time(),
		];
	$result_temp = pg_execute($database_connection, "archive_insert_statement", $archive_temp);
	if (database_result($result_temp) !== "success"): json_output("failure", "Database #191."); endif;

	$redirect_url = "/?view=share&parameter=". $share_info['share_id'] ."&action=edit&language_request=".$language_request;
	json_output("redirect", "<a href='". $redirect_url ."'>". $translatable_elements['click-here-if-you-are-not-redirected'][$language_request] ."</a>", $redirect_url);

	endif;

json_output("success", "Share saved."); ?>

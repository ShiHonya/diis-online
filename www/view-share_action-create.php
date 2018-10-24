<? if (empty($script_code)): exit; endif;

// Statements for inspiration
$create_inspiration_array = [
	$translatable_elements['be-daring'][$language_request],
	$translatable_elements['smash-the-fucking-patriarchy'][$language_request],
	$translatable_elements['show-the-truth'][$language_request],
	$translatable_elements['destroy-racist-ignorance'][$language_request],
	$translatable_elements['tell-your-story'][$language_request],
	$translatable_elements['reclaim-your-voice'][$language_request],
	$translatable_elements['speak-directly-to-the-world'][$language_request],
	];

echo "<h1>".$translatable_elements['create-a-share'][$language_request]."</h1>";

echo "<form id='create-window-form' target='_top' action-xhr='https://diis.online/?view=share&action=xhr&language=".$language_request."' method='post'>";

// Define the content status
echo "<input type='hidden' name='content_status' value='uncreated'>";

// $action_request can be create-standalone, create-reply, or create-translate
if ( ($action_request == "create-reply") && !(empty($share_info['share_id'])) ):
	$relationship_type = "reply";
	echo "<p>This will live as a reply to ____ by ___</p>";
elseif ( ($action_request == "create-translation") && !(empty($share_info['share_id'])) ):
	$relationship_type = "translation";
	echo "<p>This will live as a translation of ____ by ___</p>";
elseif ($action_request == "create-standalone"):
	$relationship_type = "standalone";
	echo "<p>". $translatable_elements['need-ideas'][$language_request] ."<br>";
	echo $create_inspiration_array[array_rand($create_inspiration_array)] ."</p>";
	endif;

// Post the relationship type and to
echo "<input type='hidden' name='relationship_type' value='". $relationship_type ."'>";
echo "<input type='hidden' name='relationship_to' value='". $share_info['share_id'] ."'>"; // This is null for standalone shares

// The submit button
echo "<span id='create-window-button' role='button' tabindex='0' on='tap:edit-window-form-submission-alert-empty-state.hide,create-window-form.submit'>". $translatable_elements['create-now'][$language_request] ."</span>";

// The submission results
echo "<div id='edit-window-form-submission-alert-success' submit-success><template type='amp-mustache'>". $translatable_elements['success'][$language_request] ." {{{message}}}</template></div>";
echo "<div id='edit-window-form-submission-alert-failure' submit-error><template type='amp-mustache'>". $translatable_elements['not-saved'][$language_request] ." {{{message}}}</template></div>";
echo "<div submitting>". $translatable_elements['sending-to-server'][$language_request] ."</div>";

echo "</form>"; ?>

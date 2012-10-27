<?php
function hitPageSSL($page, $cookieString="", $referer=ROOT_URL) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
	curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
	curl_setopt($ch, CURLOPT_USERAGENT, "TagETI");
	curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
	curl_setopt($ch, CURLOPT_URL, $page);
	curl_setopt($ch, CURLOPT_REFERER, $referer);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
  $ret = curl_exec($ch);
	if (curl_error($ch)) {
		//display_curl_error($ch);
		curl_close($ch);
		return False;
	} else {
		curl_close($ch);
		return $ret;
	}
}

function hitFormSSL($url, $formFields, $cookieString="", $referer=ROOT_URL) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
	curl_setopt($ch, CURLOPT_USERAGENT, "TagETI");
	curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_REFERER, $referer);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $formFields);
	curl_setopt($ch, CURLOPT_URL, $url);
	$ret = curl_exec($ch);
	if (curl_error($ch)) {
		curl_close($ch);
		return False;
	} else {
    curl_close($ch);
    return $ret;
	}
}

function get_enclosed_string($haystack, $needle1, $needle2="", $offset=0) {
	if ($needle1 == "") {
		$needle1_pos = 0;
	} else {
		$needle1_pos = strpos($haystack, $needle1, $offset) + strlen($needle1);
		if ($needle1_pos === FALSE || ($needle1_pos != 0 && !$needle1_pos) || $needle1_pos > strlen($haystack)) {
			return false;
		}
	}
	if ($needle2 == "") {
		$needle2_pos = strlen($haystack);
	} else {
		$needle2_pos = strpos($haystack, $needle2, $needle1_pos);
		if ($needle2_pos === FALSE || !$needle2_pos) {
			return false;
		}
	}
	if ($needle1_pos > $needle2_pos || $needle1_pos < 0 || $needle2_pos < 0 || $needle1_pos > strlen($haystack) || $needle2_pos > strlen($haystack)) {
		return false;
	}
	
    $enclosed_string = substr($haystack, $needle1_pos, $needle2_pos - $needle1_pos);
    return $enclosed_string;
}

function get_last_enclosed_string($haystack, $needle1, $needle2="") {
	//this is the last, smallest possible enclosed string.
	//position of first needle is as close to the end of the haystack as possible
	//position of second needle is as close to the first needle as possible
	if ($needle2 == "") {
		$needle2_pos = strlen($haystack);
	} else {
		$needle2_pos = strrpos($haystack, $needle2);
		if ($needle2_pos === FALSE) {
			return false;
		}
	}
	if ($needle1 == "") {
		$needle1_pos = 0;
	} else {
		$needle1_pos = strrpos(substr($haystack, 0, $needle2_pos), $needle1) + strlen($needle1);
		if ($needle1_pos === FALSE) {
			return false;
		}
	}
	if ($needle2 != "") {
		$needle2_pos = strpos($haystack, $needle2, $needle1_pos);
		if ($needle2_pos === FALSE) {
			return false;
		}
	}
    $enclosed_string = substr($haystack, $needle1_pos, $needle2_pos - $needle1_pos);
    return $enclosed_string;
}

function get_biggest_enclosed_string($haystack, $needle1, $needle2="") {
	//this is the largest possible enclosed string.
	//position of last needle is as close to the end of the haystack as possible.
	
	if ($needle1 == "") {
		$needle1_pos = 0;
	} else {
		$needle1_pos = strpos($haystack, $needle1) + strlen($needle1);
		if ($needle1_pos === FALSE) {
			return false;
		}
	}
	if ($needle2 == "") {
		$needle2_pos = strlen($haystack);
	} else {
		$needle2_pos = strrpos($haystack, $needle2, $needle1_pos);
		if ($needle2_pos === FALSE) {
			return false;
		}
	}
    $enclosed_string = substr($haystack, $needle1_pos, $needle2_pos - $needle1_pos);
    return $enclosed_string;
}

function getETILoginCookie() {
	// Grabs the cookie header string from a given file.
	$cookieString = file_get_contents(COOKIE_STRING_FILE);
	return $cookieString;
}

function getTagPublicInfo($cookieString, $name, $num=0) {
	$etiTagPage = hitPageSSL("https://boards.endoftheinter.net/async-tag-query.php?e&q=".urlencode($name), $cookieString);
	if (!$etiTagPage) {
		return False;
	}
	$jsonPage = json_decode(substr($etiTagPage, 1, strlen($etiTagPage)-1));
	if (!$jsonPage || count($jsonPage) < 1) {
		return False;
	}

	$tag = parseTagPublicInfo($etiTagPage, $num);
	if (!$tag) {
		return False;
	}
	return $tag;
}

function parseTagPublicInfo($etiTagPage, $num=0) {
	// fetches a tag's info from the public ajax interface on ETI.
	$parsedTags = json_decode(substr($etiTagPage, 1, strlen($etiTagPage)-1));
	if (!$parsedTags || count($parsedTags) < 1) {
		return False;
	}
	$tag = array();
	$tag['name'] = $parsedTags[$num][0];
	$moderators = get_enclosed_string($parsedTags[$num][1][0], "<b>Moderators: </b>", "<br /><b>Administrators:");
	$tag['moderators'] = array();
	if ($moderators) {
		$moderatorTags = explode(", ", $moderators);
		foreach ($moderatorTags as $moderator) {
			$tag['moderators'][] = array('username' => get_enclosed_string($moderator, '">', "</a>"), 
											'id' => intval(get_enclosed_string($moderator, "?user=", '">')));
		}
		$description_end_tag = "<br /><b>Moderators:";
	} else {
		$description_end_tag = "<br /><b>Administrators:";
	}
	$administrators = get_enclosed_string($parsedTags[$num][1][0], "<br /><b>Administrators: </b>");
	$tag['administrators'] = array();
	if ($administrators) {
		$adminTags = explode(", ", $administrators);
		foreach ($adminTags as $admin) {
			$tag['administrators'][] = array('username' => get_enclosed_string($admin, '">', "</a>"), 
											'id' => intval(get_enclosed_string($admin, "?user=", '">')));
		}
	}
	$tag['staff'] = array();
	foreach ($tag['administrators'] as $admin) {
		$tag['staff'][] = array('id' => intval($admin['id']), 'role' => 3);
	}
	foreach ($tag['moderators'] as $moderator) {
		$tag['staff'][] = array('id' => intval($moderator['id']), 'role' => 2);
	}
	$tag['description'] = get_last_enclosed_string($parsedTags[$num][1][0], ":</b> ", "<br /><b>Moderators:");
	$relatedTags = $parsedTags[$num][1][1]->{2};
	$tags['related_tags'] = ($relatedTags) ? array_keys(get_object_vars($relatedTags)) : array();
	$forbiddenTags = $parsedTags[$num][1][1]->{0};
	$tag['forbidden_tags'] = ($forbiddenTags) ? array_keys(get_object_vars($forbiddenTags)) : array();
	$mandatoryTags = $parsedTags[$num][1][1]->{1};
	$tag['dependency_tags'] = ($mandatoryTags) ? array_keys(get_object_vars($mandatoryTags)) : array();

	return $tag;
}

function getTagPrivateInfo($cookieString, $name) {
	// Hits the ETI tag administration page for the given tag name.
	// Returns an associative array of the tag's info if successful, False if not.
	$etiTagPage = hitPageSSL("https://endoftheinter.net/tag.php?tag=".urlencode($name), $cookieString);
	if (!$etiTagPage) {
		return False;
	}
	$tag = parseTagPrivateInfo($etiTagPage);
	if (!$tag) {
		return False;
	}
	return $tag;
}

function parseTagPrivateInfo($pageHTML) {
	// Parses the information on an ETI tag administration page.
	// Returns an associative array if successful, False if not.

	$page = new DOMDocument();
	$page->loadHTML('<?xml encoding="UTF-8">' . $pageHTML);

	// dirty fix
	foreach ($page->childNodes as $item)
	    if ($item->nodeType == XML_PI_NODE)
	        $page->removeChild($item); // remove hack
	$page->encoding = 'UTF-8'; // insert proper
	$xpath = new DOMXPath($page);

	$form = $page->getElementsByTagName("form")->item(0);
	$formFields = $form->getElementsByTagName("fieldset");

	$name = $page->getElementsByTagName("h2")->item(0)->nodeValue;

	$descriptionFields = $formFields->item(0);
	$description = $xpath->query("textarea[@name='description']", $descriptionFields)->item(0)->nodeValue;

	$accessFields = $formFields->item(1);
	$accessInputs = $xpath->query("label/input[@type='radio']", $accessFields);
	foreach ($accessInputs as $input) {
		if ($input->getAttribute("checked") != '') {
			$access = intval($input->getAttribute("value"));
		}
	}
	$access_users = array_filter(explode(",", $xpath->query("input[@name='access_users']", $accessFields)->item(0)->getAttribute("value")));
	
	$participationInputs = $xpath->query("label/input[@type='radio']", $formFields->item(2));
	foreach ($participationInputs as $input) {
		if ($input->getAttribute("checked") != '') {
			$participation = intval($input->getAttribute("value"));
		}
	}

	$restrictionInputs = $xpath->query("label/checkbox", $formFields->item(3));
	$permanent = 0;
	$inceptive = 0;
	foreach ($restrictionInputs as $input) {
		${$input->getAttribute("value")} = 1;
	}

	$interactionInputs = $xpath->query("input", $formFields->item(4));
	$related = array();
	$dependent = array();
	$exclusive = array();
	foreach ($interactionInputs as $input) {
		$inputArray = array_filter(explode(",", $input->getAttribute("value")));
		${$input->getAttribute("name")} = $inputArray;
	}

	$moderatorInput = $xpath->query("input", $formFields->item(5))->item(0);
	$moderators = array_filter(explode(",", $moderatorInput->getAttribute("value")));

	$administratorInput = $xpath->query("input", $formFields->item(6))->item(0);
	$administrators = array_filter(explode(",", $administratorInput->getAttribute("value")));

	$staff = array();
	foreach ($moderators as $moderator) {
		$staff[] = array('id' => $moderator, 'role' => 2);
	}
	foreach ($administrators as $admin) {
		$staff[] = array('id' => $admin, 'role' => 3);
	}

	$tag = array('name' => $name,
								'description' => $description,
								'access' => $access,
								'access_users' => $access_users,
								'participation' => $participation,
								'permanent' => $permanent,
								'inceptive' => $inceptive,
								'related_tags' => $related,
								'dependency_tags' => $dependent,
								'forbidden_tags' => $exclusive,
								'staff' => $staff);
	return $tag;
}

function refreshAllTags($database, $user) {
	// updates all possible tags.
	$cookieString = getETILoginCookie();
	if (!$cookieString) {
		return array('location' => "tag.php", 'status' => "The server could not log into ETI. Please try again later.", 'class' => 'error');
	}
	$etiTagsListing = hitPageSSL("https://endoftheinter.net/main.php", $cookieString);
	if (!$etiTagsListing) {
		return array('location' => "tag.php", 'status' => "The server could not grab the tags listing from ETI. Please try again later.", 'class' => 'error');
	}
	$tagList = explode("&nbsp;&bull; ", get_enclosed_string($etiTagsListing, '<div style="font-size: 14px">', '				</div>'));
	$tagCount = count($tagList);
	$tagUpdateInterval = intval($tagCount/100);
	$tagNum = 0;
	foreach ($tagList as $tagHTML) {
		$tagName = get_enclosed_string($tagHTML, '">', '</a>');

		// grab tag public info.
		$tag = getTagPublicInfo($cookieString, $tagName, 0);
		if (!$tag) {
			continue;
		}
		// grab tag private info if appropriate.
		$privateInfo = getTagPrivateInfo($cookieString, $tagName);
		if ($privateInfo) {
			$tag = array_merge($tag, $privateInfo);
		}

		try {
			$dbTag = new Tag($database, False, $tag['name']);
		} catch (Exception $e) {
			if ($e->getMessage() == "ID Not Found") {
				// no such tag exists. create a new tag.
				$dbTag = new Tag($database, 0, $tag['name']);
			}
		}
		$updateDB = $dbTag->create_or_update($tag);
		$tagNum++;
		if ($tagNum % $tagUpdateInterval == 0) {
			$updateProgress = $database->stdQuery("UPDATE `indices` SET `value` = ".round(1.0*$tagNum/$tagCount, 2)." WHERE `name` = 'tag_update' LIMIT 1");
		}
	}
	$updateProgress = $database->stdQuery("UPDATE `indices` SET `value` = 1 WHERE `name` = 'tag_update' LIMIT 1");
	return array('location' => "tag.php", 'status' => "Successfully refreshed ".$tagNum." tags.", 'class' => 'success');
}

function refreshTag($database, $user, $name) {
	// takes a tag name, finds the first tag matching the name
	// updates the database with this tag and returns a redirect_to array.
	$cookieString = getETILoginCookie();
	if (!$cookieString) {
		return array('location' => "tag.php", 'status' => "The server could not log into ETI. Please try again later.", 'class' => 'error');
	}

	$etiTagPage = hitPageSSL("https://boards.endoftheinter.net/async-tag-query.php?e&q=".urlencode($name), $cookieString);
	if (!$etiTagPage) {
		return array('location' => "tag.php", 'status' => "The server could not grab the tags listing from ETI. Please try again later.", 'class' => 'error');
	}

	$tag = parseTagPublicInfo($etiTagPage);
	if (!$tag) {
		return array('location' => "tag.php", 'status' => "An error occurred parsing the tag information from ETI. Please try again later.", 'class' => 'error');
	}

	$privateInfo = getTagPrivateInfo($cookieString, $name);
	if ($privateInfo) {
		$tag = array_merge($tag, $privateInfo);
	}

	try {
		$dbTag = new Tag($database, False, $tag['name']);
	} catch (Exception $e) {
		if ($e->getMessage() == "ID Not Found") {
			// no such tag exists. create a new tag.
			$dbTag = new Tag($database, 0, $tag['name']);
		}
	}
	$updateDB = $dbTag->create_or_update($tag);
	if (!$updateDB) {
		return array('location' => "tag.php", 'status' => "An error occurred while updating ".$dbTag->name.". Please try again.", 'class' => 'error');
	}
	return array('location' => "tag.php", 'status' => $dbTag->name." successfully refreshed.", 'class' => 'success');
}
?>
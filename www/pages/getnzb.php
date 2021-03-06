<?php
$nzb = new NZB();
$rel = new Releases();
$uid = 0;

// Page is accessible only by the rss token, or logged in users.
if ($users->isLoggedIn()) {
	$uid = $users->currentUserId();
	$maxdls = $page->userdata["downloadrequests"];
} else {
	if ($page->site->registerstatus == Sites::REGISTER_STATUS_API_ONLY) {
		$res = $users->getById(0);
	} else {
		if ((!isset($_GET["i"]) || !isset($_GET["r"]))) {
			header("X-DNZB-RCode: 400");
			header("X-DNZB-RText: Bad request, please supply all parameters!");
			$page->show403();
		}

		$res = $users->getByIdAndRssToken($_GET["i"], $_GET["r"]);
		if (!$res) {
			header("X-DNZB-RCode: 401");
			header("X-DNZB-RText: Unauthorised, wrong user ID or rss key!");
			$page->show403();
		}
	}
	$uid = $res["id"];
	$maxdls = $res["downloadrequests"];
}

// Remove any suffixed id with .nzb which is added to help weblogging programs see nzb traffic.
if (isset($_GET["id"])) {
	$_GET["id"] = preg_replace("/\.nzb/i", "", $_GET["id"]);
}

// Check download limit on user role.
$dlrequests = $users->getDownloadRequests($uid);
if ($dlrequests['num'] > $maxdls) {
	header("X-DNZB-RCode: 503");
	header("X-DNZB-RText: User has exceeded maximum downloads for the day!");
	$page->show503();
}

// User requested a zip of guid,guid,guid releases.
if (isset($_GET["id"]) && isset($_GET["zip"]) && $_GET["zip"] == "1") {
	$guids = explode(",", $_GET["id"]);
	if ($dlrequests['num'] + sizeof($guids) > $maxdls) {
		header("X-DNZB-RCode: 503");
		header("X-DNZB-RText: User has exceeded maximum downloads for the day!");
		$page->show503();
	}

	$zip = $rel->getZipped($guids);
	if (strlen($zip) > 0) {
		$users->incrementGrabs($uid, count($guids));
		foreach ($guids as $guid) {
			$rel->updateGrab($guid);
			$users->addDownloadRequest($uid);

			if (isset($_GET["del"]) && $_GET["del"] == 1) {
				$users->delCartByUserAndRelease($guid, $uid);
			}
		}

		$filename = date("Ymdhis") . ".nzb.zip";
		header("Content-type: application/octet-stream");
		header("Content-disposition: attachment; filename=" . $filename);
		echo $zip;
		die();
	} else {
		$page->show404();
	}
}

if (isset($_GET["id"])) {
	$reldata = $rel->getByGuid($_GET["id"]);
	$nzbpath = $nzb->getNZBPath($_GET["id"]);

	if (!file_exists($nzbpath)) {
		header("X-DNZB-RCode: 404");
		header("X-DNZB-RText: NZB file not found!");
		$page->show404();
	}

	if ($reldata) {
		$rel->updateGrab($_GET["id"]);
		$users->addDownloadRequest($uid);
		$users->incrementGrabs($uid);
		if (isset($_GET["del"]) && $_GET["del"] == 1) {
			$users->delCartByUserAndRelease($_GET["id"], $uid);
		}
	} else {
		header("X-DNZB-RCode: 404");
		header("X-DNZB-RText: Release not found!");
		$page->show404();
	}

	// Start reading output buffer.
	ob_start();
	// De-gzip the NZB and store it in the output buffer.
	readgzfile($nzbpath);

	// Set the NZB file name.
	header("Content-Disposition: attachment; filename=" . str_replace(array(',', ' '), '_', $reldata["searchname"]) . ".nzb");
	// Get the size of the NZB file.
	header("Content-Length: " . ob_get_length());
	header("Content-Type: application/x-nzb");
	header("Expires: " . date('r', time() + 31536000));
	// Set X-DNZB header data.
	header("X-DNZB-Category: " . $reldata["category_name"]);
	header("X-DNZB-Details: " . $page->serverurl . 'details/' . $_GET["id"]);
	if (!empty($reldata['imdbid']) && $reldata['imdbid'] > 0) {
		header("X-DNZB-MoreInfo: http://www.imdb.com/title/tt" . $reldata['imdbid']);
	} else if (!empty($reldata['rageid']) && $reldata['rageid'] > 0) {
		header("X-DNZB-MoreInfo: http://www.tvrage.com/shows/id-" . $reldata['rageid']);
	}
	header("X-DNZB-Name: " . $reldata["searchname"]);
	if ($reldata['nfostatus'] == 1) {
		header("X-DNZB-NFO: " . $page->serverurl . 'nfo/' . $_GET["id"]);
	}
	header("X-DNZB-RCode: 200");
	header("X-DNZB-RText: OK, NZB content follows.");

	// Print buffer and flush it.
	ob_end_flush();
}

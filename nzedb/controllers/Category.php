<?php

use nzedb\db\DB;

class Category
{
	const CAT_GAME_NDS = 1010;
	const CAT_GAME_PSP = 1020;
	const CAT_GAME_WII = 1030;
	const CAT_GAME_XBOX = 1040;
	const CAT_GAME_XBOX360 = 1050;
	const CAT_GAME_WIIWARE = 1060;
	const CAT_GAME_XBOX360DLC = 1070;
	const CAT_GAME_PS3 = 1080;
	const CAT_GAME_OTHER = 1090;
	const CAT_MOVIE_FOREIGN = 2010;
	const CAT_MOVIE_OTHER = 2020;
	const CAT_MOVIE_SD = 2030;
	const CAT_MOVIE_HD = 2040;
	const CAT_MOVIE_3D = 2050;
	const CAT_MOVIE_BLURAY = 2060;
	const CAT_MOVIE_DVD = 2070;
	const CAT_MUSIC_MP3 = 3010;
	const CAT_MUSIC_VIDEO = 3020;
	const CAT_MUSIC_AUDIOBOOK = 3030;
	const CAT_MUSIC_LOSSLESS = 3040;
	const CAT_MUSIC_OTHER = 3050;
	const CAT_MUSIC_FOREIGN = 3060;
	const CAT_PC_0DAY = 4010;
	const CAT_PC_ISO = 4020;
	const CAT_PC_MAC = 4030;
	const CAT_PC_PHONE_OTHER = 4040;
	const CAT_PC_GAMES = 4050;
	const CAT_PC_PHONE_IOS = 4060;
	const CAT_PC_PHONE_ANDROID = 4070;
	const CAT_TV_WEBDL = 5010;
	const CAT_TV_FOREIGN = 5020;
	const CAT_TV_SD = 5030;
	const CAT_TV_HD = 5040;
	const CAT_TV_OTHER = 5050;
	const CAT_TV_SPORT = 5060;
	const CAT_TV_ANIME = 5070;
	const CAT_TV_DOCUMENTARY = 5080;
	const CAT_XXX_DVD = 6010;
	const CAT_XXX_WMV = 6020;
	const CAT_XXX_XVID = 6030;
	const CAT_XXX_X264 = 6040;
	const CAT_XXX_OTHER = 6050;
	const CAT_XXX_IMAGESET = 6060;
	const CAT_XXX_PACKS = 6070;
	const CAT_MISC = 7010;
	const CAT_BOOKS_EBOOK = 8010;
	const CAT_BOOKS_COMICS = 8020;
	const CAT_BOOKS_MAGAZINES = 8030;
	const CAT_BOOKS_TECHNICAL = 8040;
	const CAT_BOOKS_OTHER = 8050;
	const CAT_BOOKS_FOREIGN = 8060;
	const CAT_PARENT_GAME = 1000;
	const CAT_PARENT_MOVIE = 2000;
	const CAT_PARENT_MUSIC = 3000;
	const CAT_PARENT_PC = 4000;
	const CAT_PARENT_TV = 5000;
	const CAT_PARENT_XXX = 6000;
	const CAT_PARENT_MISC = 7000;
	const CAT_PARENT_BOOKS = 8000;
	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_DISABLED = 2;


	/**
	 * Temporary category while we sort through the name.
	 * @var int
	 */
	protected $tmpCat = 0;

	/**
	 * Release name to sort through.
	 * @var string
	 */
	protected $releaseName;

	/**
	 * Group ID of the releasename we are sorting through.
	 * @var int
	 */
	protected $groupID;

	/**
	 * @var bool
	 */
	protected $categorizeforeign;

	/**
	 * @var int
	 */
	protected $catlanguage;

	/**
	 * @var bool
	 */
	protected $catwebdl;

	/**
	 * @var DB
	 */
	protected $db;

	/**
	 * Construct.
	 */
	public function __construct()
	{
		$s = new Sites();
		$site = $s->get();
		$this->categorizeforeign = ($site->categorizeforeign == "0") ? false : true;
		$this->catlanguage = (!empty($site->catlanguage)) ? (int)$site->catlanguage : 0;
		$this->catwebdl = ($site->catwebdl == "0") ? false : true;
		$this->db = new DB();
	}

	/**
	 * Get array of categories in DB.
	 *
	 * @param bool  $activeonly
	 * @param array $excludedcats
	 *
	 * @return array
	 */
	public function get($activeonly = false, $excludedcats = array())
	{
		return $this->db->query(
			"SELECT c.id, CONCAT(cp.title, ' > ',c.title) AS title, cp.id AS parentid, c.status, c.minsize
			FROM category c
			INNER JOIN category cp ON cp.id = c.parentid " .
			($activeonly ?
				sprintf(
					" WHERE c.status = %d %s ",
					Category::STATUS_ACTIVE,
					(count($excludedcats) > 0 ? " AND c.id NOT IN (" . implode(",", $excludedcats) . ")" : '')
				) : ''
			) .
			" ORDER BY c.id"
		);
	}

	/**
	 * Check if category is parent.
	 *
	 * @param $cid
	 *
	 * @return bool
	 */
	public function isParent($cid)
	{
		$ret = $this->db->queryOneRow(sprintf("SELECT * FROM category WHERE id = %d AND parentid IS NULL", $cid));
		if ($ret) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param bool $activeonly
	 *
	 * @return array
	 */
	public function getFlat($activeonly = false)
	{
		$act = "";
		if ($activeonly) {
			$act = sprintf(" WHERE c.status = %d ", Category::STATUS_ACTIVE);
		}
		return $this->db->query("SELECT c.*, (SELECT title FROM category WHERE id=c.parentid) AS parentName FROM category c " . $act . " ORDER BY c.id");
	}

	/**
	 * Get children of a parent category.
	 *
	 * @param $cid
	 *
	 * @return array
	 */
	public function getChildren($cid)
	{
		return $this->db->query(sprintf("SELECT c.* FROM category c WHERE parentid = %d", $cid));
	}

	/**
	 * Get names of enabled parent categories.
	 * @return array
	 */
	public function getEnabledParentNames()
	{
		return $this->db->query("SELECT title FROM category WHERE parentid IS NULL AND status = 1");
	}

	/**
	 * Returns category ID's for site disabled categories.
	 *
	 * @return array
	 */
	public function getDisabledIDs()
	{
		return $this->db->query("SELECT id FROM category WHERE status = 2 OR parentid IN (SELECT id FROM category WHERE status = 2 AND parentid IS NULL)");
	}

	/**
	 * Get a single category by id.
	 *
	 * @param string|int $id
	 *
	 * @return array|bool
	 */
	public function getById($id)
	{
		return $this->db->queryOneRow(
			sprintf(
				"SELECT c.disablepreview, c.id,
					CONCAT(COALESCE(cp.title,'') ,
					CASE WHEN cp.title IS NULL THEN '' ELSE ' > ' END , c.title) AS title,
					c.status, c.parentID, c.minsize
				FROM category c
				LEFT OUTER JOIN category cp ON cp.id = c.parentid
				WHERE c.id = %d", $id
			)
		);
	}

	/**
	 * Get multiple categories.
	 *
	 * @param array $ids
	 *
	 * @return array|bool
	 */
	public function getByIds($ids)
	{
		if (count($ids) > 0) {
			return $this->db->query(
				sprintf(
					"SELECT CONCAT(cp.title, ' > ',c.title) AS title
					FROM category c
					INNER JOIN category cp ON cp.id = c.parentid
					WHERE c.id IN (%s)", implode(',', $ids)
				)
			);
		} else {
			return false;
		}
	}

	/**
	 * Update a category.
	 * @param $id
	 * @param $status
	 * @param $desc
	 * @param $disablepreview
	 * @param $minsize
	 *
	 * @return bool
	 */
	public function update($id, $status, $desc, $disablepreview, $minsize)
	{
		return $this->db->queryExec(
			sprintf(
				"UPDATE category SET disablepreview = %d, status = %d, description = %s, minsize = %d
				WHERE id = %d",
				$disablepreview, $status, $this->db->escapeString($desc), $minsize, $id
			)
		);
	}

	/**
	 * @param array $excludedcats
	 *
	 * @return array
	 */
	public function getForMenu($excludedcats = array())
	{
		$ret = array();

		$exccatlist = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND id NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$arr = $this->db->query(sprintf('SELECT * FROM category WHERE status = %d %s', Category::STATUS_ACTIVE, $exccatlist));
		foreach ($arr as $a) {
			if ($a['parentid'] == '') {
				$ret[] = $a;
			}
		}

		foreach ($ret as $key => $parent) {
			$subcatlist = array();
			$subcatnames = array();
			foreach ($arr as $a) {
				if ($a['parentid'] == $parent['id']) {
					$subcatlist[] = $a;
					$subcatnames[] = $a['title'];
				}
			}

			if (count($subcatlist) > 0) {
				array_multisort($subcatnames, SORT_ASC, $subcatlist);
				$ret[$key]['subcatlist'] = $subcatlist;
			} else {
				unset($ret[$key]);
			}
		}
		return $ret;
	}

	/**
	 * @param bool $blnIncludeNoneSelected
	 *
	 * @return array
	 */
	public function getForSelect($blnIncludeNoneSelected = true)
	{
		$categories = $this->get();
		$temp_array = array();

		if ($blnIncludeNoneSelected) {
			$temp_array[-1] = "--Please Select--";
		}

		foreach ($categories as $category) {
			$temp_array[$category["id"]] = $category["title"];
		}

		return $temp_array;
	}

	/**
	 * Return the parent and category name from the supplied categoryID.
	 * @param $ID
	 *
	 * @return string
	 */
	public function getNameByID($ID)
	{
		$parent = $this->db->queryOneRow(sprintf("SELECT title FROM category WHERE id = %d", substr($ID, 0, 1) . "000"));
		$cat = $this->db->queryOneRow(sprintf("SELECT title FROM category WHERE id = %d", $ID));
		return $parent["title"] . " " . $cat["title"];
	}

	/**
	 * Look up the site to see which language of categorizing to use.
	 * Then work out which category is applicable for either a group or a binary.
	 * Returns Category::CAT_MISC if no category is appropriate.
	 *
	 * @param string     $releaseName The name to parse.
	 * @param string|int $groupID     The groupID.
	 *
	 * @return int The categoryID.
	 */
	public function determineCategory($releaseName = "", $groupID)
	{
		/*
		 * 0 = English
		 * 2 = Danish
		 * 3 = French
		 * 1 = German
		 */

		switch ($this->catlanguage) {
			case 0:
				break;
			case 1:
				$cg = new CategoryGerman();
				return $cg->determineCategory($releaseName, $groupID);
			case 2:
				$cd = new CategoryDanish();
				return $cd->determineCategory($releaseName, $groupID);
			case 3:
				$cf = new CategoryFrench();
				return $cf->determineCategory($releaseName, $groupID);
			default:
				break;
		}

		$this->releaseName = $releaseName;
		$this->groupID = $groupID;
		$this->tmpCat = Category::CAT_MISC;

		// Note that in byGroup() some overrides occur...
		if ($this->isMisc()) {
			return $this->tmpCat;
		}
		if ($this->byGroup()) {
			return $this->tmpCat;
		}
		//Try against all functions, if still nothing, return Cat Misc.
		if ($this->isPC()) {
			return $this->tmpCat;
		}
		if ($this->isXXX()) {
			return $this->tmpCat;
		}
		if ($this->isTV()) {
			return $this->tmpCat;
		}
		if ($this->isMusic()) {
			return $this->tmpCat;
		}
		if ($this->isMovie()) {
			return $this->tmpCat;
		}
		if ($this->isConsole()) {
			return $this->tmpCat;
		}
		if ($this->isBook()) {
			return $this->tmpCat;
		}
		return $this->tmpCat;
	}

	//	Groups.
	public function byGroup()
	{
		$group = $this->db->queryOneRow('SELECT LOWER(name) AS name FROM groups WHERE id = ' . $this->groupID);
		if ($group !== false) {
			$group = $group['name'];

			if ($group === 'alt.binaries.0day.stuffz') {
				if ($this->isBook()) {
					return true;
				} else if ($this->isPC()) {
					return true;
				}
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if ($group === 'alt.binaries.audio.warez') {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.erotica\.|cartoons\.french\.|dvd\.|multimedia\.)?anime(\.highspeed|\.repost|s-fansub|\.german)?/', $group)) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if ($this->categorizeforeign && $group === 'alt.binaries.cartoons.french') {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if ($group === 'alt.binaries.cd.image.linux') {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if ($group === 'alt.binaries.cd.lossles') {
				if ($this->categorizeforeign && $this->isMusicForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if ($group === 'alt.binaries.classic.tv.shows') {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(comics\.dcp|pictures\.comics\.(complete|dcp|reposts?))/', $group)) {
				if ($this->categorizeforeign && $this->isBookForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_BOOKS_COMICS;
				return true;
			}

			if ($group === 'alt.binaries.console.ps3') {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}

			if ($group === 'alt.binaries.cores') {
				if ($this->isXxx()) {
					return true;
				}
				return false;
			}

			if (preg_match('/alt\.binaries(\.(19\d0s|country|sounds?(\.country|\.19\d0s)?))?\.mp3(\.[a-z]+)?/i', $group)) {
				if ($this->isMusic()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.dvd(\-?r)?(\.(movies|))?$/i', $group)) {
				if ($this->isMovie()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MISC;
				return true;
			}

			if ($this->categorizeforeign && preg_match('/alt\.binaries\.(dvdnordic\.org|nordic\.(dvdr?|xvid))|dk\.(binaer|binaries)\.film(\.divx)?/', $group)) {
				$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
				return true;
			}

			if ($group === 'alt.binaries.documentaries') {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?books?((\.|\-)(technical|textbooks))/', $group)) {
				if ($this->categorizeforeign && $this->isBookForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?book(\.[a-z]+)?/', $group)) {
				if ($this->is0day()) {
					return true;
				}

				if ($this->isBook()) {
					return true;
				}

				if ($this->categorizeforeign && $this->isBookForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MISC;
				return true;
			}

			if (preg_match('/alt\.binaries\..*(erotica|ijsklontje|xxx)/', $group)) {
				if ($this->isXxx()) {
					return true;
				}
				$this->tmpCat = Category::CAT_XXX_OTHER;
				return true;
			}

			if ($group === 'alt.binaries.games.dox') {
				$this->tmpCat = Category::CAT_PC_GAMES;
				return true;
			}

			if (preg_match('/alt\.binaries(\.games)?\.nintendo(\.)?ds/', $group)) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}

			if ($group === 'alt.binaries.games.wii') {
				if ($this->isGameWiiWare()) {
					return true;
				}
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}

			if ($group === 'alt.binaries.games.xbox') {
				if ($this->isGameXBOX360DLC()) {
					return true;
				}
				if ($this->isGameXBOX360()) {
					return true;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX;
				return true;
			}

			if ($group === 'alt.binaries.games.xbox360') {
				if ($this->isGameXBOX360DLC()) {
					return true;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}

			if ($group === 'alt.binaries.inner-sanctum') {
				if ($this->isMusic()) {
					return true;
				}
				if (preg_match('/-+(19|20)\d\d-\(?(album.*?|back|cover|front)\)?-+/i', $this->releaseName)) {
					$this->tmpCat = Category::CAT_MUSIC_OTHER;
					return true;
				} else if (preg_match('/(19|20)\d\d$/', $this->releaseName) && ctype_lower(preg_replace('/[^a-z]/i', '', $this->releaseName))) {
					$this->tmpCat = Category::CAT_MUSIC_OTHER;
					return true;
				}
				return false;
			}

			if (preg_match('/alt\.binaries\.ipod\.videos\.tvshows/', $group)) {
				$this->tmpCat = Category::CAT_TV_OTHER;
				return true;
			}

			if ($group === 'alt.binaries.mac') {
				$this->tmpCat = Category::CAT_PC_MAC;
				return true;
			}

			if ($group === 'alt.binaries.mma') {
				if ($this->is0day()) {
					return true;
				}
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if ($group === 'alt.binaries.moovee') {
				//check if it's TV first as some tv posted in moovee
				if ($this->isTV()) {
					return $this->tmpCat;
				}
				// Check the movie isn't an HD release before blindly assigning SD
				if ($this->isMovieHD()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MOVIE_SD;
				return true;
			}

			if ($group === 'alt.binaries.mpeg.video.music') {
				if ($this->categorizeforeign && $this->isMusicForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}

			if ($group === 'alt.binaries.multimedia.documentaries') {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.sports(\.boxing)?/', $group)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if ($group === 'alt.binaries.music.opera') {
				if ($this->categorizeforeign && $this->isMusicForeign()) {
					return true;
				}

				if (preg_match('/720p|[-._ ]mkv/i', $this->releaseName)) {
					$this->tmpCat = Category::CAT_MUSIC_VIDEO;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.music/', $group)) {
				if ($this->categorizeforeign && $this->isMusicForeign()) {
					return true;
				}
				if ($this->isMusic()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/audiobook/', $group)) {
				if ($this->categorizeforeign && $this->isMusicForeign()) {
					return true;
				}

				$this->tmpCat = Category::CAT_MUSIC_AUDIOBOOK;
				return true;
			}

			if ($group === 'alt.binaries.pro-wrestling') {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.(flac(\.jazz)|jpop|lossless(\.[a-z0-9]+)?)|alt\.binaries\.(cd\.lossless|music\.flac)/i', $group)) {
				if ($this->categorizeforeign && $this->isMusicForeign()) {
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if ($group === 'alt.binaries.sounds.whitburn.pop') {
				if ($this->categorizeforeign && $this->isMusicForeign()) {
						return true;
				}

				if (!preg_match('/[-._ ]scans[-._ ]/i', $this->releaseName)) {
					$this->tmpCat = Category::CAT_MUSIC_MP3;
					return true;
				}
				return false;
			}

			if ($group === 'alt.binaries.sony.psp') {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}

			if ($group === 'alt.binaries.warez') {
				if ($this->isTV()) {
					return $this->tmpCat;
				}

				if ($this->isPC()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if ($group === 'alt.binaries.warez.smartphone') {
				if ($this->isPhone()) {
					return true;
				}
				$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
				return true;
			}

			if ($this->categorizeforeign && $group === 'db.binaer.tv') {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		}
		return false;
	}

	//
	// Beginning of functions to determine category by release name.
	//

	//	TV.
	public function isTV()
	{
//		if (/*!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}|season|episode/i', $this->releaseName) &&*/ preg_match('/part[-._ ]?\d/i', $this->releaseName)) {
//			return false;
//		}

		if (preg_match('/Daily[-_\.]Show|Nightly News|(\d\d-){2}[12]\d{3}|[12]\d{3}(\.\d\d){2}|\d+x\d+|s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|[-._ ](\dx\d\d|C4TV|Complete[-._ ]Season|DSR|(D|H|P)DTV|EP[-._ ]?\d{1,3}|S\d{1,3}.+Extras|SUBPACK|Season[-._ ]\d{1,2}|WEB\-DL|WEBRip)([-._ ]|$)|TV[-._ ](19|20)\d\d|TrollHD/i', $this->releaseName)
			&& !preg_match('/[-._ ](flac|imageset|mp3|xxx)[-._ ]|[ .]exe$/i', $this->releaseName)) {

			if ($this->isOtherTV()) {
				return true;
			}

			if ($this->categorizeforeign && $this->isForeignTV()) {
				return true;
			}

			if ($this->isSportTV()) {
				return true;
			}

			if ($this->isDocumentaryTV()) {
				return true;
			}


			if ($this->catwebdl && $this->isWEBDL()) {
				return true;
			}

			if ($this->isHDTV()) {
				return true;
			}

			if ($this->isSDTV()) {
				return true;
			}

			if ($this->isAnimeTV()) {
				return true;
			}

			if ($this->isOtherTV2()) {
				return true;
			}

			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}

		if (preg_match('/[-._ ]((19|20)\d\d[-._ ]\d{1,2}[-._ ]\d{1,2}[-._ ]VHSRip|Indy[-._ ]?Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW[-._ ]TV|(Per|Post)\-Show|PPV|WrestleMania|WCW|WEB[-._ ]HD|WWE[-._ ](Monday|NXT|RAW|Smackdown|Superstars|WrestleMania))[-._ ]/i', $this->releaseName)) {
			if ($this->isSportTV()) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	public function isOtherTV()
	{
		if (preg_match('/[-._ ](S\d{1,3}.+Extras|SUBPACK)[-._ ]|News/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	public function isForeignTV()
	{
		if (!preg_match('/[-._ ](NHL|stanley.+cup)[-._ ]/', $this->releaseName)) {
			if (preg_match('/[-._ ](chinese|dk|fin|french|ger|heb|ita|jap|kor|nor|nordic|nl|pl|swe)[-._ ]?(sub|dub)(ed|bed|s)?|<German>/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish).+(720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid|x264)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid).+(brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/(S\d\d[EX]\d\d|DOCU(MENTAIRE)?|TV)?[-._ ](FRENCH|German|Dutch)[-._ ](720p|1080p|dv(b|d)r(ip)?|LD|HD\-?TV|TV[-._ ]?RIP|x264)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ]FastSUB|NL|nlvlaams|patrfa|RealCO|Seizoen|slosinh|Videomann|Vostfr|xslidian[-._ ]|x264\-iZU/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isSportTV()
	{
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])/i', $this->releaseName)) {
			if (preg_match('/[-._ ]?(Bellator|bundesliga|EPL|ESPN|FIA|la[-._ ]liga|MMA|motogp|NFL|NCAA|PGA|red[-._ ]bull.+race|Sengoku|Strikeforce|supercup|uefa|UFC|wtcc|WWE)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(DTM|FIFA|formula[-._ ]1|indycar|Rugby|NASCAR|NBA|NHL|NRL|netball[-._ ]anz|ROH|SBK|Superleague|The[-._ ]Ultimate[-._ ]Fighter|TNA|V8[-._ ]Supercars|WBA|WrestleMania)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(AFL|Grand Prix|Indy[-._ ]Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW|Poker|PWX|Rugby|WCW)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(Horse)[-._ ]Racing[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}
		}
		return false;
	}

	public function isDocumentaryTV()
	{
		if (preg_match('/[-._ ](Docu|Documentary)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
			return true;
		}
		return false;
	}

	public function isWEBDL()
	{
		if (preg_match('/web[-._ ]dl/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_WEBDL;
			return true;
		}
		return false;
	}

	public function isHDTV()
	{
		if (preg_match('/1080(i|p)|720p/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_HD;
			return true;
		}
		if ($this->catwebdl == false) {
			if (preg_match('/web[-._ ]dl/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_HD;
				return true;
			}
		}
		return false;
	}

	public function isSDTV()
	{
		if (preg_match('/(360|480|576)p|Complete[-._ ]Season|dvdr(ip)?|dvd5|dvd9|SD[-._ ]TV|TVRip|NTSC|BDRip|hdtv|xvid/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/((H|P)D[-._ ]?TV|DSR|WebRip)[-._ ]x264/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|\s\d{3,4}\s/i', $this->releaseName)) {
			if (preg_match('/(H|P)D[-._ ]?TV|BDRip[-._ ]x264/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}
		}
		return false;
	}

	public function isAnimeTV()
	{
		if (preg_match('/[-._ ]Anime[-._ ]|^\(\[AST\]\s|\[HorribleSubs\]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_ANIME;
			return true;
		}
		return false;
	}

	public function isOtherTV2()
	{
		if (preg_match('/[-._ ]s\d{1,3}[-._ ]?(e|d)\d{1,3}([-._ ]|$)/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	//  Movies.
	public function isMovie()
	{
		if (preg_match('/[-._ ]AVC|[-._ ]|[BH][DR]RIP|Bluray|BD[-._ ]?(25|50)?|\bBR\b|Camrip|[-._ ]\d{4}[-._ ].+(720p|1080p|Cam)|DIVX|[-._ ]DVD[-._ ]|DVD-?(5|9|R|Rip)|Untouched|VHSRip|XVID|[-._ ](DTS|TVrip)[-._ ]/i', $this->releaseName) && !preg_match('/auto(cad|desk)|divx[-._ ]plus|[-._ ]exe$|[-._ ](jav|XXX)[-._ ]|SWE6RUS|\wXXX(1080p|720p|DVD)|Xilisoft/i', $this->releaseName)) {
			if ($this->categorizeforeign && $this->isMovieForeign()) {
				return true;
			}
			if ($this->isMovieDVD()) {
				return true;
			}
			if ($this->isMovieSD()) {
				return true;
			}
			if ($this->isMovie3D()) {
				return true;
			}
			if ($this->isMovieBluRay()) {
				return true;
			}
			if ($this->isMovieHD()) {
				return true;
			}
			if ($this->isMovieOther()) {
				return true;
			}
		}

		return false;
	}

	public function isMovieForeign()
	{
		if (preg_match('/(danish|flemish|Deutsch|dutch|french|german|nl[-._ ]?sub(bed|s)?|\.NL|norwegian|swedish|swesub|spanish|Staffel)[-._ ]|\(german\)|Multisub/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/Castellano/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/(720p|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|XVID)[-._ ](Dutch|French|German|ITA)|\(?(Dutch|French|German|ITA)\)?[-._ ](720P|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|HD[-._ ]|XVID)/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		return false;
	}

	public function isMovieDVD()
	{
		if (preg_match('/(dvd\-?r|[-._ ]dvd|dvd9|dvd5|[-._ ]r5)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_DVD;
			return true;
		}
		return false;
	}

	public function isMovieSD()
	{
		if (preg_match('/(divx|dvdscr|extrascene|dvdrip|\.CAM|vhsrip|xvid)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_SD;
			return true;
		}
		return false;
	}

	public function isMovie3D()
	{
		if (preg_match('/[-._ ]3D\s?[\.\-_\[ ](1080p|(19|20)\d\d|AVC|BD(25|50)|Blu[-._ ]?ray|CEE|Complete|GER|MVC|MULTi|SBS|H(-)?SBS)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_3D;
			return true;
		}
		return false;
	}

	public function isMovieBluRay()
	{
		if (preg_match('/bluray\-|[-._ ]bd?[-._ ]?(25|50)|blu-ray|Bluray\s\-\sUntouched|[-._ ]untouched[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_BLURAY;
			return true;
		}
		return false;
	}

	public function isMovieHD()
	{
		if (preg_match('/720p|1080p|AVC|VC1|VC\-1|web\-dl|wmvhd|x264|XvidHD|bdrip/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_HD;
			return true;
		}
		return false;
	}

	public function isMovieOther()
	{
		if (preg_match('/[-._ ]cam[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_OTHER;
			return true;
		}
		return false;
	}

	//  PC.
	public function isPC()
	{
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|[^a-z0-9](FLAC|Imageset|MP3|Nintendo|PDTV|PS[23P]|SWE6RUS|UMD(RIP)?|WII|x264|XBOX|XXX)[^a-z0-9]/i', $this->releaseName)) {
			if ($this->isPhone()) {
				return true;
			}
			if ($this->isMac()) {
				return true;
			}
			if ($this->isISO()) {
				return true;
			}
			if ($this->is0day()) {
				return true;
			}
			if ($this->isPCGame()) {
				return true;
			}
		}
		return false;
	}

	public function isPhone()
	{
		if (preg_match('/[^a-z0-9](IPHONE|ITOUCH|IPAD)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_PHONE_IOS;
			return true;
		}

		if (preg_match('/[-._ ]?(ANDROID)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_PHONE_ANDROID;
			return true;
		}

		if (preg_match('/[^a-z0-9](symbian|xscale|wm5|wm6)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
			return true;
		}
		return false;
	}

	public function isISO()
	{
		if (preg_match('/\biso\b/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_ISO;
			return true;
		}
		return false;
	}

	public function is0day()
	{
		if (preg_match('/[-._ ]exe$|[-._ ](utorrent|Virtualbox)[-._ ]|incl.+crack| DRM$|>DRM</i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}

		if (preg_match('/[-._ ]((32|64)bit|converter|i\d86|key(gen|maker)|freebsd|GAMEGUiDE|hpux|irix|linux|multilingual|Patch|Pro v\d{1,3}|portable|regged|software|solaris|template|unix|win2kxp2k3|win64|win(2k|32|64|all|dows|nt(2k)?(xp)?|xp)|win9x(me|nt)?|x(32|64|86))[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}

		if (preg_match('/\b(Adobe|auto(cad|desk)|-BEAN|Cracked|Cucusoft|CYGNUS|Divx[-._ ]Plus|\.(deb|exe)|DIGERATI|FOSI|Key(filemaker|gen|maker)|Lynda\.com|lz0|MULTiLANGUAGE|MultiOS|-(iNViSiBLE|SPYRAL|SUNiSO|UNION|TE)|v\d{1,3}.*?Pro|[-._ ]v\d{1,3}[-._ ]|\(x(64|86)\)|Xilisoft)\b/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}
		return false;
	}

	public function isMac()
	{
		if (preg_match('/\bmac(\.|\s)?osx\b/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_MAC;
			return true;
		}
		return false;
	}

	public function isPCGame()
	{
		if (preg_match('/[^a-z0-9](0x0007|ALiAS|BACKLASH|BAT|CPY|FAS(DOX|iSO)|FLT([-._ ]|COGENT)|FLTDOX|Games|GENESIS|HI2U|INLAWS|JAGUAR|MAZE|MONEY|OUTLAWS|PPTCLASSiCS|PC Game|PROPHET|RAiN|Razor1911|RELOADED|RiTUELYPOGEiOS|Rip-UNLEASHED|SKIDROW|TiNYiSO)[^a-z0-9]/', $this->releaseName)) {
			$this->tmpCat = Category::CAT_PC_GAMES;
			return true;
		}
		return false;
	}

	//	XXX.
	public function isXxx()
	{
		if (preg_match('/\bXXX\b|(a\.b\.erotica|ClubSeventeen|Cum(ming|shot)|Err?oticax?|Porn(o|lation)?|Imageset|lesb(ians?|os?)|mastur(bation|e?bate)|My_Stepfather_Made_Me|nympho?|pictures\.erotica\.anime|sexontv|slut|Squirt|SWE6RUS|Transsexual|whore)/i', $this->releaseName)) {
			if ($this->isXxxPack()) {
				return true;
			}
			if ($this->isXxx264()) {
				return true;
			}
			if ($this->isXxxXvid()) {
				return true;
			}
			if ($this->isXxxImageset()) {
				return true;
			}
			if ($this->isXxxWMV()) {
				return true;
			}
			if ($this->isXxxDVD()) {
				return true;
			}
			if ($this->isXxxOther()) {
				return true;
			}
			$this->tmpCat = Category::CAT_XXX_OTHER;
			return true;
		}
		return false;
	}

	public function isXxx264()
	{
		if (preg_match('/720p|1080(hd|[ip])|[xh][^a-z0-9]?264/i', $this->releaseName) && !preg_match('/\bwmv\b/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_X264;
			return true;
		}
		return false;
	}

	public function isXxxWMV()
	{
		if (preg_match('/(\d{2}\.\d{2}\.\d{2})|([ex]\d{2,})|[^a-z0-9](f4v|flv|isom|(issue\.\d{2,})|mov|mp(4|eg)|multiformat|pack-|realmedia|uhq|wmv)[^a-z0-9]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_WMV;
			return true;
		}
		return false;
	}

	public function isXxxXvid()
	{
		if (preg_match('/(b[dr]|dvd)rip|detoxication|divx|nympho|pornolation|swe6|tesoro|xvid/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_XVID;
			return true;
		}
		return false;
	}

	public function isXxxDVD()
	{
		if (preg_match('/dvdr[^i]|dvd[59]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_DVD;
			return true;
		}
		return false;
	}

	public function isXxxImageset()
	{
		if (preg_match('/IMAGESET|ABPEA/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_IMAGESET;
			return true;
		}
		return false;
	}

	public function isXxxPack()
	{
		if (preg_match('/[ .]PACK[ .]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_PACKS;
			return true;
		}
		return false;
	}

	public function isXxxOther()
	{
		// If nothing else matches, then try these words.
		if (preg_match('/[-._ ]Brazzers|Creampie|[-._ ]JAV[-._ ]|North\.Pole|She[-._ ]?Male|Transsexual/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_XXX_OTHER;
			return true;
		}
		return false;
	}

	//	Console.
	public function isConsole()
	{
		if ($this->isGameNDS()) {
			return true;
		}
		if ($this->isGamePS3()) {
			return true;
		}
		if ($this->isGamePSP()) {
			return true;
		}
		if ($this->isGameWiiWare()) {
			return true;
		}
		if ($this->isGameWii()) {
			return true;
		}
		if ($this->isGameXBOX360DLC()) {
			return true;
		}
		if ($this->isGameXBOX360()) {
			return true;
		}
		if ($this->isGameXBOX()) {
			return true;
		}
		return false;
	}

	public function isGameNDS()
	{
		if (preg_match('/NDS|[\. ]nds|nintendo.+3ds/', $this->releaseName)) {
			if (preg_match('/\((DE|DSi(\sEnhanched)?|EUR?|FR|GAME|HOL|JP|JPN|NL|NTSC|PAL|KS|USA?)\)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}
			if (preg_match('/(EUR|FR|GAME|HOL|JP|JPN|NL|NTSC|PAL|KS|USA)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}
		}
		return false;
	}

	public function isGamePS3()
	{
		if (preg_match('/PS3/i', $this->releaseName)) {
			if (preg_match('/ANTiDOTE|DLC|DUPLEX|EUR?|Googlecus|GOTY|\-HR|iNSOMNi|JAP|JPN|KONDIOS|\[PS3\]|PSN/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
			if (preg_match('/AGENCY|APATHY|Caravan|MULTi|NRP|NTSC|PAL|SPLiT|STRiKE|USA?|ZRY/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
		}
		return false;
	}

	public function isGamePSP()
	{
		if (preg_match('/PSP/i', $this->releaseName)) {
			if (preg_match('/[-._ ](BAHAMUT|Caravan|EBOOT|EMiNENT|EUR?|EvoX|GAME|GHS|Googlecus|HandHeld|\-HR|JAP|JPN|KLOTEKLAPPERS|KOR|NTSC|PAL)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}
			if (preg_match('/[-._ ](Dynarox|HAZARD|ITALIAN|KLB|KuDoS|LIGHTFORCE|MiRiBS|POPSTATiON|(PLAY)?ASiA|PSN|SPANiSH|SUXXORS|UMD(RIP)?|USA?|YARR)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}
		}
		return false;
	}

	public function isGameWiiWare()
	{
		if (preg_match('/(Console|DLC|VC).+[-._ ]WII|(Console|DLC|VC)[-._ ]WII|WII[-._ ].+(Console|DLC|VC)|WII[-._ ](Console|DLC|VC)|WIIWARE/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_GAME_WIIWARE;
			return true;
		}
		return false;
	}

	public function isGameWii()
	{
		if (preg_match('/WII/i', $this->releaseName)) {
			if (preg_match('/[-._ ](Allstars|BiOSHOCK|dumpTruck|DNi|iCON|JAP|NTSC|PAL|ProCiSiON|PROPER|RANT|REV0|SUNSHiNE|SUSHi|TMD|USA?)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
			if (preg_match('/[-._ ](APATHY|BAHAMUT|DMZ|ERD|GAME|JPN|LoCAL|MULTi|NAGGERS|OneUp|PLAYME|PONS|Scrubbed|VORTEX|ZARD|ZER0)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
			if (preg_match('/[-._ ](ALMoST|AMBITION|Caravan|CLiiCHE|DRYB|HaZMaT|KOR|LOADER|MARVEL|PROMiNENT|LaKiTu|LOCAL|QwiiF|RANT)/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
		}
		return false;
	}

	public function isGameXBOX360DLC()
	{
		if (preg_match('/DLC.+xbox360|xbox360.+DLC|XBLA.+xbox360|xbox360.+XBLA/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_GAME_XBOX360DLC;
			return true;
		}
		return false;
	}

	public function isGameXBOX360()
	{
		if (preg_match('/XBOX360/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_GAME_XBOX360;
			return true;
		}
		if (preg_match('/x360/i', $this->releaseName)) {
			if (preg_match('/Allstars|ASiA|CCCLX|COMPLEX|DAGGER|GLoBAL|iMARS|JAP|JPN|MULTi|NTSC|PAL|REPACK|RRoD|RF|SWAG|USA?/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}
			if (preg_match('/DAMNATION|GERMAN|GOTY|iNT|iTA|JTAG|KINECT|MARVEL|MUX360|RANT|SPARE|SPANISH|VATOS|XGD/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}
		}
		return false;
	}

	public function isGameXBOX()
	{
		if (preg_match('/XBOX/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_GAME_XBOX;
			return true;
		}
		return false;
	}

	//	Music.
	public function isMusic()
	{
		if ($this->isMusicVideo()) {
			return true;
		}
		if ($this->isAudiobook()) {
			return true;
		}
		if ($this->isMusicLossless()) {
			return true;
		}
		if ($this->isMusicMP3()) {
			return true;
		}
		if ($this->isMusicOther()) {
			return true;
		}
		return false;
	}

	public function isMusicForeign()
	{
		if ($this->categorizeforeign) {
			if (preg_match('/[ \-\._](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish|bl|cz|de|es|fr|ger|heb|hu|hun|it(a| 19|20\d\d)|jap|ko|kor|nl|pl|se)[ \-\._]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_MUSIC_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isAudiobook()
	{
		if ($this->categorizeforeign) {
			if (preg_match('/Audiobook/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_MUSIC_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isMusicVideo()
	{
		if (preg_match('/(720P|x264)\-(19|20)\d\d\-[a-z0-9]{1,12}/i', $this->releaseName)) {
			if ($this->isMusicForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}
		}
		if (preg_match('/[a-z0-9]{1,12}\-(19|20)\d\d\-(720P|x264)/i', $this->releaseName)) {
			if ($this->isMusicForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}
		}
		return false;
	}

	public function isMusicLossless()
	{
		if (preg_match('/\[(19|20)\d\d\][-._ ]\[FLAC\]|(\(|\[)flac(\)|\])|FLAC\-(19|20)\d\d\-[a-z0-9]{1,12}|\.flac"|(19|20)\d\d\sFLAC|[-._ ]FLAC.+(19|20)\d\d[-._ ]| FLAC$/i', $this->releaseName)) {
			if ($this->isMusicForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}
		}
		return false;
	}

	public function isMusicMP3()
	{
		if (preg_match('/[a-z0-9]{1,12}\-(19|20)\d\d\-[a-z0-9]{1,12}|[\.\-\(\[_ ]\d{2,3}k[\.\-\)\]_ ]|\((192|256|320)\)|(320|cd|eac|vbr).+mp3|(cd|eac|mp3|vbr).+320|FIH\_INT|\s\dCDs|[-._ ]MP3[-._ ]|MP3\-\d{3}kbps|\.(m3u|mp3)"|NMR\s\d{2,3}\skbps|\(320\)\.|\-\((Bootleg|Promo)\)|\.mp3$|\-\sMP3\s(19|20)\d\d|\(vbr\)|rip(192|256|320)|[-._ ](CDR|WEB).+(19|20)\d\d/i', $this->releaseName)) {
			if ($this->isMusicForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}
		}
		if (preg_match('/\s(19|20)\d\d\s([a-z0-9]{3}|[a-z]{2,})$|\-(19|20)\d\d\-(C4|MTD)(\s|\.)|[-._ ]FM.+MP3[-._ ]|-web-(19|20)\d\d(\.|\s|$)|[-._ ](SAT|WEB).+(19|20)\d\d([-._ ]|$)|[-._ ](19|20)\d\d.+(SAT|WEB)([-._ ]|$)| MP3$/i', $this->releaseName)) {
			if ($this->isMusicForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}
		}
		return false;
	}

	public function isMusicOther()
	{
		if (preg_match('/(19|20)\d\d\-(C4)$|[-._ ]\d?CD[-._ ](19|20)\d\d|\(\d\-?CD\)|\-\dcd\-|\d[-._ ]Albums|Albums.+(EP)|Bonus.+Tracks|Box.+?CD.+SET|Discography|D\.O\.M|Greatest\sSongs|Live.+(Bootleg|Remastered)|Music.+Vol|(\(|\[|\s)NMR(\)|\]|\s)|Promo.+CD|Reggaeton|Tiesto.+Club|Vinyl\s2496|\WV\.A\.|^\(VA\s|^VA[-._ ]/i', $this->releaseName)) {
			if ($this->isMusicForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_OTHER;
				return true;
			}
		} else if (preg_match('/\(pure_fm\)|-+\(?(2lp|cd[ms]([-_ .][a-z]{2})?|cover|ep|ltd_ed|mix|original|ost|.*?(edit(ion)?|remix(es)?|vinyl)|web)\)?-+((19|20)\d\d|you$)/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MUSIC_OTHER;
			return true;
		}
		return false;
	}

	//	Books.
	public function isBook()
	{
		if (!preg_match('/AVI[-._ ]PDF|\.exe|Full[-._ ]Video/i', $this->releaseName)) {
			if ($this->isComic()) {
				return true;
			}
			if ($this->isTechnicalBook()) {
				return true;
			}
			if ($this->isMagazine()) {
				return true;
			}
			if ($this->isBookOther()) {
				return true;
			}
			if ($this->isEBook()) {
				return true;
			}
		}
		return false;
	}

	public function isBookForeign()
	{
		if ($this->categorizeforeign) {
			if (preg_match('/[ \-\._](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_BOOKS_FOREIGN;
				return true;
			}
		}

		return false;
	}

	public function isComic()
	{
		if (preg_match('/[\. ](cbr|cbz)|[\( ]c2c|cbr|cbz[\) ]|comix|^\(comic|[\.\-_\(\[ ]comics?[-._ ]|comic.+book|covers.+digital|DC.+(Adventures|Universe)|digital.+(son|zone)|Graphic.+Novel|[\.\-_h ]manga|Total[-._ ]Marvel/i', $this->releaseName)) {
			if ($this->isBookForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_BOOKS_COMICS;
				return true;
			}
		}
		return false;
	}

	public function isTechnicalBook()
	{
		if (preg_match('/^\(?(atz|bb|css|c ?t|Drawing|Gabler|IOS|Iphone|Lynda|Manning|Medic(al|ine)|MIT|No[-._ ]Starch|Packt|Peachpit|Pragmatic|Revista|Servo|SmartBooks|Spektrum|Strata|Sybex|Syngress|Vieweg|Wiley|Woods|Wrox)[-._ ]|[-._ ](Ajax|CSS|DIY|Javascript|(My|Postgre)?SQL|XNA)[-._ ]|3DS\.\-_ ]Max|Academic|Adobe|Algebra|Analysis|Appleworks|Archaeology|Bitdefender|Birkhauser|Britannica|[-._ ]C\+\+|C[-._ ](\+\+|Sharp|Plus)|Chemistry|Circuits|Cook(book|ing)|(Beginners?|Complete|Communications|Definitive|Essential|Hackers?|Practical|Professionals?)[-._ ]Guide|Developer|Diagnostic|Disassembl(er|ing|y)|Debugg(er|ing)|Dreamweaver|Economics|Education|Electronics|Enc(i|y)clopedia|Engineer(ing|s)|Essays|Exercizes|For.+Beginners|Focal[-._ ]Press|For[-._ ]Dummies|FreeBSD|Fundamentals[-._ ]of[-._ ]|(Galileo|Island)[-._ ]Press|Geography|Grammar|Guide[-._ ](For|To)|Hacking|Google|Handboo?k|How[-._ ](It|To)|Intoduction[-._ ]to|Iphone|jQuery|Lessons[-._ ]In|Learning|LibreOffice|Linux|Manual|Marketing|Masonry|Mathematic(al|s)?|Medical|Microsoft|National[-._ ]Academies|Nero[-._ ]\d+|OReilly|OS[-._ ]X[-._ ]|Official[-._ ]Guide|Open(GL|Office)|Pediatric|Periodic.+Table|Photoshop|Physics|Power(PC|Point|Shell)|Programm(ers?|ier||ing)|Raspberry.+Pi|Remedies|Service\s?Manual|SitePoint|Sketching|Statistics|Stock.+Market|Students|Theory|Training|Tutsplus|Ubuntu|Understanding[-._ ](and|Of|The)|Visual[-._ ]Studio|Textbook|VMWare|wii?max|Windows[-._ ](8|7|Vista|XP)|^Wood[-._ ]|Woodwork|WordPress|Work(book|shop)|Youtube/i', $this->releaseName)) {
			if ($this->isBookForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
				return true;
			}
		}
		return false;
	}

	public function isMagazine()
	{
		if (preg_match('/[a-z\-\._ ][-._ ](January|February|March|April|May|June|July|August|September|October|November|December)[-._ ](\d{1,2},)?20\d\d[-._ ]|^\(.+[ .]\d{1,2}[ .]20\d\d[ .].+\.scr|[-._ ](Catalogue|FHM|NUTS|Pictorial|Tatler|XXX)[-._ ]|^\(?(Allehanda|Club|Computer([a-z0-9]+)?|Connect \d+|Corriere|ct|Diario|Digit(al)?|Esquire|FHM|Gadgets|Galileo|Glam|GQ|Infosat|Inked|Instyle|io|Kicker|Liberation|New Scientist|NGV|Nuts|Popular|Professional|Reise|Sette(tv)?|Springer|Stuff|Studentlitteratur|Vegetarian|Vegetable|Videomarkt|Wired)[-._ ]|Brady(.+)?Games|Catalog|Columbus.+Dispatch|Correspondenten|Corriere[-._ ]Della[-._ ]Sera|Cosmopolitan|Dagbladet|Digital[-._ ]Guide|Economist|Eload ?24|ExtraTime|Fatto[-._ ]Quotidiano|Flight[-._ ](International|Journal)|Finanzwoche|France.+Football|Foto.+Video|Games?(Master|Markt|tar|TM)|Gardening|Gazzetta|Globe[-._ ]And[-._ ]Mail|Guitar|Heimkino|Hustler|La.+(Lettura|Rblica|Stampa)|Le[-._ ](Monde|Temps)|Les[-._ ]Echos|e?Magazin(es?)?|Mac(life|welt)|Marie.+Claire|Maxim|Men.+(Health|Fitness)|Motocross|Motorcycle|Mountain[-._ ]Bike|MusikWoche|National[-._ ]Geographic|New[-._ ]Yorker|PC([-._ ](Gamer|Welt|World)|Games|Go|Tip)|Penthouse|Photograph(er|ic)|Playboy|Posten|Quotidiano|(Golf|Readers?).+Digest|SFX[-._ ]UK|Recipe(.+Guide|s)|SkyNews|Sport[-._ ]?Week|Strategy.+Guide|TabletPC|Tattoo[-._ ]Life|The[-._ ]Guardian|Tageszeitung|Tid(bits|ning)|Top[-._ ]Gear[-._ ]|Total[-._ ]Guitar|Travel[-._ ]Guides?|Tribune[-._ ]De[-._ ]|US[-._ ]Weekly|USA[-._ ]Today|Vogue|Verlag|Warcraft|Web.+Designer|What[-._ ]Car|Zeitung/i', $this->releaseName)) {
			if ($this->isBookForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_BOOKS_MAGAZINES;
				return true;
			}
		}
		return false;
	}

	public function isBookOther()
	{
		if (preg_match('/"\d\d-\d\d-20\d\d\./i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_BOOKS_OTHER;
			return true;
		}
		return false;
	}

	public function isEBook()
	{
		if (preg_match('/^ePub|[-._ ](Ebook|E?\-book|\) WW|Publishing)|[\.\-_\(\[ ](epub|html|mobi|pdf|rtf|tif|txt)[\.\-_\)\] ]|[\. ](doc|epub|mobi|pdf)(?![\w .])/i', $this->releaseName)) {
			if ($this->isBookForeign()) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_BOOKS_EBOOK;
				return true;
			}
		}
		return false;
	}

	//	Misc, all hash/misc go in other misc.
	public function isMisc()
	{
		if (!preg_match('/[^a-z0-9]((480|720|1080)[ip]|s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ]))[^a-z0-9]/i', $this->releaseName)) {
			if (preg_match('/[a-z0-9]{20,}/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_MISC;
				return true;
			} else if (preg_match('/^[A-Z0-9]{1,}$/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_MISC;
				return true;
			}
		}
		return false;
	}
}

class CategoryDanish extends Category
{
	protected $tmpCat = 0;
	protected $releaseName;
	protected $groupID;

	public function determineCategory($releasename = "", $groupID)
	{
		$this->releaseName = $releasename;
		$this->groupID = $groupID;
		$this->tmpCat = Category::CAT_MISC;

		//Try against all functions, if still nothing, return Cat Misc.
		if ($this->byGroup()) {
			return $this->tmpCat;
		}
		if (Category::isPC()) {
			return $this->tmpCat;
		}
		if (Category::isXXX()) {
			return $this->tmpCat;
		}
		if ($this->isTV()) {
			return $this->tmpCat;
		}
		if ($this->isMovie()) {
			return $this->tmpCat;
		}
		if (Category::isConsole()) {
			return $this->tmpCat;
		}
		if (Category::isMusic()) {
			return $this->tmpCat;
		}
		if (Category::isBook()) {
			return $this->tmpCat;
		}
		if (Category::isMisc()) {
			return $this->tmpCat;
		}
	}

	// Groups.
	public function byGroup()
	{
		$group = $this->db->queryOneRow('SELECT name FROM groups WHERE id = ' . $this->groupID);
		if ($group !== false) {
			$group = $group['name'];

			if (preg_match('/alt\.binaries\.0day\.stuffz/', $group)) {
				if ($this->isBook()) {
					return $this->tmpCat;
				}
				if ($this->isPC()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.erotica\.|cartoons\.french\.|dvd\.|multimedia\.)?anime(\.highspeed|\.repost|s-fansub|\.german)?/', $group)) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if (preg_match('/alt\.binaries\.audio\.warez/', $group)) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.)?anime(\.(highspeed|repost))?/', $group)) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if (preg_match('/alt\.binaries\.cartoons\.french/', $group)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.image\.linux/', $group)) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.lossless/', $group)) {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.classic\.tv\.shows/i', $group)) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(comics\.dcp|pictures\.comics\.(complete|dcp|reposts?))/', $group)) {
				$this->tmpCat = Category::CAT_BOOKS_COMICS;
				return true;
			}

			if (preg_match('/alt\.binaries\.console\.ps3/', $group)) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}

			if (preg_match('/alt\.binaries\.cores/', $group)) {
				if ($this->isXxx()) {
					return $this->tmpCat;
				}
				return false;
			}

			if (preg_match('/alt\.binaries(\.(19\d0s|country|sounds?(\.country|\.19\d0s)?))?\.mp3(\.[a-z]+)?/i', $group)) {

				if ($this->isMusic()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.dvd(\-?r)?(\.(movies|))?$/i', $group)) {
				$this->tmpCat = Category::CAT_MOVIE_DVD;
				return true;
			}

			if (preg_match('/alt\.binaries\.documentaries/', $group)) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?books?((\.|\-)(technical|textbooks))/', $group)) {
				$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?book(\.[a-z]+)?/', $group)) {
				if ($this->isBook()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_BOOKS_EBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.((movies|multimedia)\.)?(erotica(\.(amateur|divx))?|ijsklontje)/', $group)) {
				if ($this->isXxx()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_XXX_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries(\.games)?\.nintendo(\.)?ds/', $group)) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.wii/', $group)) {
				if ($this->isGameWiiWare()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox$/', $group)) {
				if ($this->isGameXBOX360DLC()) {
					return $this->tmpCat;
				}
				if ($this->isGameXBOX360()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox360/', $group)) {
				if ($this->isGameXBOX360DLC()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}

			if (preg_match('/alt\.binaries\.ipod\.videos\.tvshows/', $group)) {
				$this->tmpCat = Category::CAT_TV_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries\.mac$/', $group)) {
				$this->tmpCat = Category::CAT_PC_MAC;
				return true;
			}

			if (preg_match('/alt\.binaries\.mma$/', $group)) {
				if ($this->is0day()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.moovee/', $group)) {
				// Check the movie isn't an HD release before blindly assigning SD
				if ($this->isMovieHD()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MOVIE_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.mpeg\.video\.music/', $group)) {
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.documentaries/', $group)) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.sports(\.boxing)?/', $group)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.music\.opera/', $group)) {
				if (preg_match('/720p|[-._ ]mkv/i', $this->releaseName)) {
					$this->tmpCat = Category::CAT_MUSIC_VIDEO;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.(mp3|sounds?)(\.mp3)?\.audiobook(s|\.repost)?/', $group)) {
				$this->tmpCat = Category::CAT_MUSIC_AUDIOBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.pro\-wrestling/', $group)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.(flac(\.jazz)|jpop|lossless(\.[a-z0-9]+)?)|alt\.binaries\.(cd\.lossless|music\.flac)/i', $group)) {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.whitburn\.pop/i', $group)) {
				if (!preg_match('/[-._ ]scans[-._ ]/i', $this->releaseName)) {
					$this->tmpCat = Category::CAT_MUSIC_MP3;
					return true;
				}
			}

			if (preg_match('/alt\.binaries\.sony\.psp/', $group)) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez$/', $group)) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez\.smartphone/', $group)) {
				if ($this->isPhone()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
				return true;
			}

			return false;
		}
	}

	//	TV.
	public function isTV()
	{
		$looksLikeTV = preg_match('/Daily[-_\.]Show|Nightly News|\d\d-\d\d-[12][90]\d\d|[12][90]\d{2}\.\d{2}\.\d{2}|\d+x\d+|s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|[-._ ](\dx\d\d|C4TV|Complete[-._ ]Season|DSR|(D|H|P)DTV|EP[-._ ]?\d{1,3}|S\d{1,3}.+Extras|SUBPACK|Season[-._ ]\d{1,2}|WEB\-DL|WEBRip)([-._ ]|$)|TV[-._ ](19|20)\d\d|TrollHD/i', $this->releaseName);
		$looksLikeSportTV = preg_match('/[-._ ]((19|20)\d\d[-._ ]\d{1,2}[-._ ]\d{1,2}[-._ ]VHSRip|Indy[-._ ]?Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW[-._ ]TV|(Per|Post)\-Show|PPV|WrestleMania|WCW|WEB[-._ ]HD|WWE[-._ ](Monday|NXT|RAW|Smackdown|Superstars|WrestleMania))[-._ ]/i', $this->releaseName);
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}|season|episode/i', $this->releaseName) && preg_match('/part[-._ ]?\d/i', $this->releaseName)) {
			return false;
		}
		if ($looksLikeTV && !preg_match('/[-._ ](flac|imageset|mp3|xxx)[-._ ]/i', $this->releaseName)) {
			if ($this->isOtherTV()) {
				return true;
			}
			if ($this->isForeignTV()) {
				return true;
			}
			if ($this->isSportTV()) {
				return true;
			}
			if ($this->isDocumentaryTV()) {
				return true;
			}
			if ($this->isWEBDL()) {
				return true;
			}
			if ($this->isHDTV()) {
				return true;
			}
			if ($this->isSDTV()) {
				return true;
			}
			if ($this->isAnimeTV()) {
				return true;
			}
			if ($this->isOtherTV2()) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}

		if ($looksLikeSportTV) {
			if ($this->isSportTV()) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	public function isOtherTV()
	{
		if (preg_match('/[-._ ](S\d{1,3}.+Extras|SUBPACK)[-._ ]|News/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}

	public function isForeignTV()
	{
		if (!preg_match('/[-._ ](NHL|stanley.+cup)[-._ ]/', $this->releaseName)) {
			if (preg_match('/[-._ ](chinese|fin|french|ger|heb|ita|jap|kor|nor|nordic|nl|pl|swe)[-._ ]?(sub|dub)(ed|bed|s)?|<German>/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](brazilian|chinese|croatian|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish).+(720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid).+(brazilian|chinese|croatian|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/(S\d\d[EX]\d\d|DOCU(MENTAIRE)?|TV)?[-._ ](FRENCH|German|Dutch)[-._ ](720p|1080p|dv(b|d)r(ip)?|LD|HD\-?TV|TV[-._ ]?RIP|x264)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ]FastSUB|NL|nlvlaams|patrfa|RealCO|Seizoen|slosinh|Videomann|Vostfr|xslidian[-._ ]|x264\-iZU/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isSportTV()
	{
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])/i', $this->releaseName)) {
			if (preg_match('/[-._ ]?(Bellator|bundesliga|EPL|ESPN|FIA|la[-._ ]liga|MMA|motogp|NFL|NCAA|PGA|red[-._ ]bull.+race|Sengoku|Strikeforce|supercup|uefa|UFC|wtcc|WWE)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(DTM|FIFA|formula[-._ ]1|indycar|Rugby|NASCAR|NBA|NHL|NRL|netball[-._ ]anz|ROH|SBK|Superleague|The[-._ ]Ultimate[-._ ]Fighter|TNA|V8[-._ ]Supercars|WBA|WrestleMania)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(AFL|Grand Prix|Indy[-._ ]Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW|Poker|PWX|Rugby|WCW)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(Horse)[-._ ]Racing[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}
		}
		return false;
	}

	public function isDocumentaryTV()
	{
		if (preg_match('/[-._ ](Docu|Documentary)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
			return true;
		}
		return false;
	}

	public function isWEBDL()
	{
		if (preg_match('/web[-._ ]dl/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_WEBDL;
			return true;
		}
		return false;
	}

	public function isHDTV()
	{
		if (preg_match('/1080(i|p)|720p/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_HD;
			return true;
		}
		return false;
	}

	public function isSDTV()
	{
		if (preg_match('/(360|480|576)p|Complete[-._ ]Season|dvdr|dvd5|dvd9|SD[-._ ]TV|TVRip|xvid/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/((H|P)D[-._ ]?TV|DSR|WebRip)[-._ ]x264/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|\s\d{3,4}\s/i', $this->releaseName)) {
			if (preg_match('/(H|P)D[-._ ]?TV|BDRip[-._ ]x264/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}
		}
		return false;
	}

	public function isAnimeTV()
	{
		if (preg_match('/[-._ ]Anime[-._ ]|^\(\[AST\]\s|\[HorribleSubs\]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_ANIME;
			return true;
		}

		return false;
	}

	public function isOtherTV2()
	{
		if (preg_match('/[-._ ]s\d{1,3}[-._ ]?(e|d)\d{1,3}[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}

	//  Movie.
	public function isMovie()
	{
		if (preg_match('/[-._ ]AVC|[-._ ]|(B|H)(D|R)RIP|Bluray|BD[-._ ]?(25|50)?|BR|Camrip|[-._ ]\d{4}[-._ ].+(720p|1080p|Cam)|DIVX|[-._ ]DVD[-._ ]|DVD-?(5|9|R|Rip)|Untouched|VHSRip|XVID|[-._ ](DTS|TVrip)[-._ ]/i', $this->releaseName) && !preg_match('/[-._ ]exe$|[-._ ](jav|XXX)[-._ ]|\wXXX(1080p|720p|DVD)|Xilisoft/i', $this->releaseName)) {
			if ($this->isMovieForeign()) {
				return true;
			}
			if ($this->isMovieDVD()) {
				return true;
			}
			if ($this->isMovieSD()) {
				return true;
			}
			if ($this->isMovie3D()) {
				return true;
			}
			if ($this->isMovieBluRay()) {
				return true;
			}
			if ($this->isMovieHD()) {
				return true;
			}
			if ($this->isMovieOther()) {
				return true;
			}
		}
		return false;
	}

	public function isMovieForeign()
	{
		if (preg_match('/(danish|flemish|Deutsch|dutch|french|german|nl[-._ ]?sub(bed|s)?|\.NL|norwegian|swedish|swesub|spanish|Staffel)[-._ ]|\(german\)/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/Castellano/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/(720p|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|XVID)[-._ ](Dutch|French|German|ITA)|\(?(Dutch|French|German|ITA)\)?[-._ ](720P|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|HD[-._ ]|XVID)/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		return false;
	}

	public function isMovieDVD()
	{
		if (preg_match('/(dvd\-?r|[-._ ]dvd|dvd9|dvd5|[-._ ]r5)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_DVD;
			return true;
		}
		return false;
	}

	public function isMovieSD()
	{
		if (preg_match('/(divx|dvdscr|extrascene|dvdrip|\.CAM|vhsrip|xvid)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_SD;
			return true;
		}
		return false;
	}

	public function isMovie3D()
	{
		if (preg_match('/[-._ ]3D\s?[\.\-_\[ ](1080p|(19|20)\d\d|AVC|BD(25|50)|Blu[-._ ]?ray|CEE|Complete|GER|MVC|MULTi|SBS)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_3D;
			return true;
		}
		return false;
	}

	public function isMovieBluRay()
	{
		if (preg_match('/bluray\-|[-._ ]bd?[-._ ]?(25|50)|blu-ray|Bluray\s\-\sUntouched|[-._ ]untouched[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_BLURAY;
			return true;
		}
		return false;
	}

	public function isMovieHD()
	{
		if (preg_match('/720p|1080p|AVC|VC1|VC\-1|web\-dl|wmvhd|x264|XvidHD|bdrip/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_HD;
			return true;
		}
		return false;
	}

	public function isMovieOther()
	{
		if (preg_match('/[-._ ]cam[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_OTHER;
			return true;
		}
		return false;
	}
}

class CategoryFrench extends Category
{
	protected $tmpCat = 0;
	protected $releaseName;
	protected $groupID;

	public function determineCategory($releasename = "", $groupID)
	{
		$this->releaseName = $releasename;
		$this->groupID = $groupID;
		$this->tmpCat = Category::CAT_MISC;

		//Try against all functions, if still nothing, return Cat Misc.
		if ($this->byGroup()) {
			return $this->tmpCat;
		}
		if (Category::isPC()) {
			return $this->tmpCat;
		}
		if (Category::isXXX()) {
			return $this->tmpCat;
		}
		if ($this->isTV()) {
			return $this->tmpCat;
		}
		if ($this->isMovie()) {
			return $this->tmpCat;
		}
		if (Category::isConsole()) {
			return $this->tmpCat;
		}
		if (Category::isMusic()) {
			return $this->tmpCat;
		}
		if (Category::isBook()) {
			return $this->tmpCat;
		}
		if (Category::isMisc()) {
			return $this->tmpCat;
		}
	}

	// Groups.
	public function byGroup()
	{
		$group = $this->db->queryOneRow('SELECT name FROM groups WHERE id = ' . $this->groupID);
		if ($group !== false) {
			$group = $group['name'];

			if (preg_match('/alt\.binaries\.0day\.stuffz/', $group)) {
				if ($this->isEBook()) {
					return $this->tmpCat;
				}
				if ($this->isPC()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.audio\.warez/', $group)) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.erotica\.|cartoons\.french\.|dvd\.|multimedia\.)?anime(\.highspeed|\.repost|s-fansub|\.german)?/', $group)) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.image\.linux/', $group)) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.lossless/', $group)) {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.classic\.tv\.shows/i', $group)) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(comics\.dcp|pictures\.comics\.(complete|dcp|reposts?))/', $group)) {
				$this->tmpCat = Category::CAT_BOOKS_COMICS;
				return true;
			}

			if (preg_match('/alt\.binaries\.console\.ps3/', $group)) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
			if (preg_match('/alt\.binaries\.cores/', $group)) {
				if ($this->isXxx()) {
					return $this->tmpCat;
				}
				return false;
			}

			if (preg_match('/alt\.binaries(\.(19\d0s|country|sounds?(\.country|\.19\d0s)?))?\.mp3(\.[a-z]+)?/i', $group)) {
				if ($this->isMusicLossless()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.dvd(\-?r)?(\.(movies|))?$/i', $group)) {
				$this->tmpCat = Category::CAT_MOVIE_DVD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(dvdnordic\.org|nordic\.(dvdr?|xvid))|dk\.(binaer|binaries)\.film(\.divx)?/', $group)) {
				$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
				return true;
			}

			if (preg_match('/alt\.binaries\.documentaries/', $group)) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?books?((\.|\-)(technical|textbooks))/', $group)) {
				$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?book(\.[a-z]+)?/', $group)) {
				if ($this->isBook()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_BOOKS_EBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.((movies|multimedia)\.)?(erotica(\.(amateur|divx))?|ijsklontje)/', $group)) {
				if ($this->isXxx()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_XXX_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries(\.games)?\.nintendo(\.)?ds/', $group)) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.wii/', $group)) {
				if ($this->isGameWiiWare()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox$/', $group)) {
				if ($this->isGameXBOX360DLC()) {
					return $this->tmpCat;
				}
				if ($this->isGameXBOX360()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox360/', $group)) {
				if ($this->isGameXBOX360DLC()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}

			if (preg_match('/alt\.binaries\.ipod\.videos\.tvshows/', $group)) {
				$this->tmpCat = Category::CAT_TV_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries\.mac$/', $group)) {
				$this->tmpCat = Category::CAT_PC_MAC;
				return true;
			}

			if (preg_match('/alt\.binaries\.mma$/', $group)) {
				if ($this->is0day()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.moovee/', $group)) {
				$this->tmpCat = Category::CAT_MOVIE_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.mpeg\.video\.music/', $group)) {
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.documentaries/', $group)) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.sports(\.boxing)?/', $group)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.music\.opera/', $group)) {
				if (preg_match('/720p|[-._ ]mkv/i', $this->releaseName)) {
					$this->tmpCat = Category::CAT_MUSIC_VIDEO;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.(mp3|sounds?)(\.mp3)?\.audiobook(s|\.repost)?/', $group)) {
				$this->tmpCat = Category::CAT_MUSIC_AUDIOBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.pro\-wrestling/', $group)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.(flac(\.jazz)|jpop|lossless(\.[a-z0-9]+)?)|alt\.binaries\.(cd\.lossless|music\.flac)/i', $group)) {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.whitburn\.pop/i', $group)) {
				if (!preg_match('/[-._ ]scans[-._ ]/i', $this->releaseName)) {
					$this->tmpCat = Category::CAT_MUSIC_MP3;
					return true;
				}
			}

			if (preg_match('/alt\.binaries\.sony\.psp/', $group)) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez$/', $group)) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez\.smartphone/', $group)) {
				if ($this->isPhone()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
				return true;
			}

			if (preg_match('/dk\.binaer\.tv/', $group)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			return false;
		}
	}

	//
	//	TV
	//

	public function isTV()
	{
		$looksLikeTV = preg_match('/Daily[-_\.]Show|Nightly News|\d\d-\d\d-[12][90]\d\d|[12][90]\d{2}\.\d{2}\.\d{2}|\d+x\d+|s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|[-._ ](\dx\d\d|C4TV|Complete[-._ ]Season|DSR|(D|H|P)DTV|EP[-._ ]?\d{1,3}|S\d{1,3}.+Extras|SUBPACK|Season[-._ ]\d{1,2}|WEB\-DL|WEBRip)([-._ ]|$)|TV[-._ ](19|20)\d\d|TrollHD/i', $this->releaseName);
		$looksLikeSportTV = preg_match('/[-._ ]((19|20)\d\d[-._ ]\d{1,2}[-._ ]\d{1,2}[-._ ]VHSRip|Indy[-._ ]?Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW[-._ ]TV|(Per|Post)\-Show|PPV|WrestleMania|WCW|WEB[-._ ]HD|WWE[-._ ](Monday|NXT|RAW|Smackdown|Superstars|WrestleMania))[-._ ]/i', $this->releaseName);
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}|season|episode/i', $this->releaseName) && preg_match('/part[-._ ]?\d/i', $this->releaseName)) {
			return false;
		}
		if ($looksLikeTV && !preg_match('/[-._ ](flac|imageset|mp3|xxx)[-._ ]/i', $this->releaseName)) {
			if ($this->isOtherTV()) {
				return true;
			}
			if ($this->isForeignTV()) {
				return true;
			}
			if ($this->isSportTV()) {
				return true;
			}
			if ($this->isDocumentaryTV()) {
				return true;
			}
			if ($this->isWEBDL()) {
				return true;
			}
			if ($this->isHDTV()) {
				return true;
			}
			if ($this->isSDTV()) {
				return true;
			}
			if ($this->isAnimeTV()) {
				return true;
			}
			if ($this->isOtherTV2()) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}

		if ($looksLikeSportTV) {
			if ($this->isSportTV()) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	public function isOtherTV()
	{
		if (preg_match('/[-._ ](S\d{1,3}.+Extras|SUBPACK)[-._ ]|News/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}

	public function isForeignTV()
	{
		if (!preg_match('/[-._ ](NHL|stanley.+cup)[-._ ]/', $this->releaseName)) {
			if (preg_match('/[-._ ](chinese|dk|fin|ger|heb|ita|jap|kor|nor|nordic|nl|pl|swe)[-._ ]?(sub|dub)(ed|bed|s)?|<German>/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish).+(720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid).+(brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/(S\d\d[EX]\d\d|DOCU|TV)?[-._ ](German|Dutch)[-._ ](720p|1080p|dv(b|d)r(ip)?|LD|HD\-?TV|TV[-._ ]?RIP|x264)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ]FastSUB|NL|nlvlaams|patrfa|RealCO|Seizoen|slosinh|Videomann|xslidian[-._ ]|x264\-iZU/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isSportTV()
	{
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])/i', $this->releaseName)) {
			if (preg_match('/[-._ ]?(Bellator|bundesliga|EPL|ESPN|FIA|la[-._ ]liga|MMA|motogp|NFL|NCAA|PGA|red[-._ ]bull.+race|Sengoku|Strikeforce|supercup|uefa|UFC|wtcc|WWE)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(DTM|FIFA|formula[-._ ]1|indycar|Rugby|NASCAR|NBA|NHL|NRL|netball[-._ ]anz|ROH|SBK|Superleague|The[-._ ]Ultimate[-._ ]Fighter|TNA|V8[-._ ]Supercars|WBA|WrestleMania)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(AFL|Grand Prix|Indy[-._ ]Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW|Poker|PWX|Rugby|WCW)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(Horse)[-._ ]Racing[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}
		}
		return false;
	}

	public function isDocumentaryTV()
	{
		if (preg_match('/[-._ ](Docu|Documentary)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
			return true;
		}
		return false;
	}

	public function isWEBDL()
	{
		if (preg_match('/web[-._ ]dl/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_WEBDL;
			return true;
		}
		return false;
	}

	public function isHDTV()
	{
		if (preg_match('/1080(i|p)|720p/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_HD;
			return true;
		}
		return false;
	}

	public function isSDTV()
	{
		if (preg_match('/(360|480|576)p|Complete[-._ ]Season|dvdr|dvd5|dvd9|SD[-._ ]TV|TVRip|xvid/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/((H|P)D[-._ ]?TV|DSR|WebRip)[-._ ]x264/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|\s\d{3,4}\s/i', $this->releaseName)) {
			if (preg_match('/(H|P)D[-._ ]?TV|BDRip[-._ ]x264/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}
		}
		return false;
	}

	public function isAnimeTV()
	{
		if (preg_match('/[-._ ]Anime[-._ ]|^\(\[AST\]\s|\[HorribleSubs\]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_ANIME;
			return true;
		}

		return false;
	}

	public function isOtherTV2()
	{
		if (preg_match('/[-._ ]s\d{1,3}[-._ ]?(e|d)\d{1,3}[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}

	//  Movies.
	public function isMovie()
	{
		if (preg_match('/[-._ ]AVC|[-._ ]|(B|H)(D|R)RIP|Bluray|BD[-._ ]?(25|50)?|BR|Camrip|[-._ ]\d{4}[-._ ].+(720p|1080p|Cam)|DIVX|[-._ ]DVD[-._ ]|DVD-?(5|9|R|Rip)|Untouched|VHSRip|XVID|[-._ ](DTS|TVrip)[-._ ]/i', $this->releaseName) && !preg_match('/[-._ ]exe$|[-._ ](jav|XXX)[-._ ]|\wXXX(1080p|720p|DVD)|Xilisoft/i', $this->releaseName)) {
			if ($this->isMovieForeign()) {
				return true;
			}
			if ($this->isMovieDVD()) {
				return true;
			}
			if ($this->isMovieSD()) {
				return true;
			}
			if ($this->isMovie3D()) {
				return true;
			}
			if ($this->isMovieBluRay()) {
				return true;
			}
			if ($this->isMovieHD()) {
				return true;
			}
			if ($this->isMovieOther()) {
				return true;
			}
		}
		return false;
	}

	public function isMovieForeign()
	{
		if (preg_match('/(danish|flemish|Deutsch|dutch|german|nl[-._ ]?sub(bed|s)?|\.NL|norwegian|swedish|swesub|spanish|Staffel)[-._ ]|\(german\)/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/Castellano/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/(720p|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|XVID)[-._ ](Dutch|French|German|ITA)|\(?(Dutch|French|German|ITA)\)?[-._ ](720P|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|HD[-._ ]|XVID)/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		return false;
	}

	public function isMovieDVD()
	{
		if (preg_match('/(dvd\-?r|[-._ ]dvd|dvd9|dvd5|[-._ ]r5)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_DVD;
			return true;
		}
		return false;
	}

	public function isMovieSD()
	{
		if (preg_match('/(divx|dvdscr|extrascene|dvdrip|\.CAM|vhsrip|xvid)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_SD;
			return true;
		}
		return false;
	}

	public function isMovie3D()
	{
		if (preg_match('/[-._ ]3D\s?[\.\-_\[ ](1080p|(19|20)\d\d|AVC|BD(25|50)|Blu[-._ ]?ray|CEE|Complete|GER|MVC|MULTi|SBS)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_3D;
			return true;
		}
		return false;
	}

	public function isMovieBluRay()
	{
		if (preg_match('/bluray\-|[-._ ]bd?[-._ ]?(25|50)|blu-ray|Bluray\s\-\sUntouched|[-._ ]untouched[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_BLURAY;
			return true;
		}
		return false;
	}

	public function isMovieHD()
	{
		if (preg_match('/720p|1080p|AVC|VC1|VC\-1|web\-dl|wmvhd|x264|XvidHD|bdrip/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_HD;
			return true;
		}
		return false;
	}

	public function isMovieOther()
	{
		if (preg_match('/[-._ ]cam[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_OTHER;
			return true;
		}
		return false;
	}
}

class CategoryGerman extends Category
{
	protected $tmpCat = 0;
	protected $releaseName;
	protected $groupID;

	public function determineCategory($releaseName = "", $groupID)
	{
		$this->releaseName = $releaseName;
		$this->groupID = $groupID;
		$this->tmpCat = Category::CAT_MISC;
		//Try against all functions, if still nothing, return Cat Misc.
		if ($this->byGroup()) {
			return $this->tmpCat;
		}
		if (Category::isPC()) {
			return $this->tmpCat;
		}
		if (Category::isXXX()) {
			return $this->tmpCat;
		}
		if ($this->isTV()) {
			return $this->tmpCat;
		}
		if ($this->isMovie()) {
			return $this->tmpCat;
		}
		if (Category::isConsole()) {
			return $this->tmpCat;
		}
		if (Category::isMusic()) {
			return $this->tmpCat;
		}
		if (Category::isBook()) {
			return $this->tmpCat;
		}
		if (Category::isMisc()) {
			return $this->tmpCat;
		}
	}

	public function byGroup()
	{
		$group = $this->db->queryOneRow('SELECT name FROM groups WHERE id = ' . $this->groupID);
		if ($group !== false) {
			$group = $group['name'];

			if (preg_match('/alt\.binaries\.0day\.stuffz/', $group)) {
				if ($this->isBook()) {
					return $this->tmpCat;
				}
				if ($this->isPC()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.erotica\.|cartoons\.french\.|dvd\.|multimedia\.)?anime(\.highspeed|\.repost|s-fansub|\.german)?/', $group)) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if (preg_match('/alt\.binaries\.audio\.warez/', $group)) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.)?anime(\.(highspeed|repost))?/', $group)) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if (preg_match('/alt\.binaries\.cartoons\.french/', $group)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.image\.linux/', $group)) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.lossless/', $group)) {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.classic\.tv\.shows/i', $group)) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(comics\.dcp|pictures\.comics\.(complete|dcp|reposts?))/', $group)) {
				$this->tmpCat = Category::CAT_BOOKS_COMICS;
				return true;
			}

			if (preg_match('/alt\.binaries\.console\.ps3/', $group)) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
			if (preg_match('/alt\.binaries\.cores/', $group)) {
				if ($this->isXxx()) {
					return $this->tmpCat;
				}
				return false;
			}

			if (preg_match('/alt\.binaries(\.(19\d0s|country|sounds?(\.country|\.19\d0s)?))?\.mp3(\.[a-z]+)?/i', $group)) {

				if ($this->isMusic()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.dvd(\-?r)?(\.(movies|))?$/i', $group)) {
				$this->tmpCat = Category::CAT_MOVIE_DVD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(dvdnordic\.org|nordic\.(dvdr?|xvid))|dk\.(binaer|binaries)\.film(\.divx)?/', $group)) {
				$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
				return true;
			}

			if (preg_match('/alt\.binaries\.documentaries/', $group)) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?books?((\.|\-)(technical|textbooks))/', $group)) {
				$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?book(\.[a-z]+)?/', $group)) {
				if ($this->isBook()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_BOOKS_EBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.((movies|multimedia)\.)?(erotica(\.(amateur|divx))?|ijsklontje)/', $group)) {
				if ($this->isXxx()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_XXX_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries(\.games)?\.nintendo(\.)?ds/', $group)) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.wii/', $group)) {
				if ($this->isGameWiiWare()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox$/', $group)) {
				if ($this->isGameXBOX360DLC()) {
					return $this->tmpCat;
				}
				if ($this->isGameXBOX360()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox360/', $group)) {
				if ($this->isGameXBOX360DLC()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}

			if (preg_match('/alt\.binaries\.ipod\.videos\.tvshows/', $group)) {
				$this->tmpCat = Category::CAT_TV_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries\.mac$/', $group)) {
				$this->tmpCat = Category::CAT_PC_MAC;
				return true;
			}

			if (preg_match('/alt\.binaries\.mma$/', $group)) {
				if ($this->is0day()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.moovee/', $group)) {
				// Check the movie isn't an HD release before blindly assigning SD
				if ($this->isMovieHD()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MOVIE_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.mpeg\.video\.music/', $group)) {
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.documentaries/', $group)) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.sports(\.boxing)?/', $group)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.music\.opera/', $group)) {
				if (preg_match('/720p|[-._ ]mkv/i', $this->releaseName)) {
					$this->tmpCat = Category::CAT_MUSIC_VIDEO;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.(mp3|sounds?)(\.mp3)?\.audiobook(s|\.repost)?/', $group)) {
				$this->tmpCat = Category::CAT_MUSIC_AUDIOBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.pro\-wrestling/', $group)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.(flac(\.jazz)|jpop|lossless(\.[a-z0-9]+)?)|alt\.binaries\.(cd\.lossless|music\.flac)/i', $group)) {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.whitburn\.pop/i', $group)) {
				if (!preg_match('/[-._ ]scans[-._ ]/i', $this->releaseName)) {
					$this->tmpCat = Category::CAT_MUSIC_MP3;
					return true;
				}
			}

			if (preg_match('/alt\.binaries\.sony\.psp/', $group)) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez$/', $group)) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez\.smartphone/', $group)) {
				if ($this->isPhone()) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
				return true;
			}

			if ($this->categorizeforeign) {
				if (preg_match('/dk\.binaer\.tv/', $group)) {
					$this->tmpCat = Category::CAT_TV_FOREIGN;
					return true;
				}
			}

			return false;
		}
	}

	//	TV.
	public function isTV()
	{
		$looksLikeTV = preg_match('/Daily[-_\.]Show|Nightly News|\d\d-\d\d-[12][90]\d\d|[12][90]\d{2}\.\d{2}\.\d{2}|\d+x\d+|s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|[-._ ](\dx\d\d|C4TV|Complete[-._ ]Season|DSR|(D|H|P)DTV|EP[-._ ]?\d{1,3}|S\d{1,3}.+Extras|SUBPACK|Season[-._ ]\d{1,2}|WEB\-DL|WEBRip)([-._ ]|$)|TV[-._ ](19|20)\d\d|TrollHD/i', $this->releaseName);
		$looksLikeSportTV = preg_match('/[-._ ]((19|20)\d\d[-._ ]\d{1,2}[-._ ]\d{1,2}[-._ ]VHSRip|Indy[-._ ]?Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW[-._ ]TV|(Per|Post)\-Show|PPV|WrestleMania|WCW|WEB[-._ ]HD|WWE[-._ ](Monday|NXT|RAW|Smackdown|Superstars|WrestleMania))[-._ ]/i', $this->releaseName);
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}|season|episode/i', $this->releaseName) && preg_match('/part[-._ ]?\d/i', $this->releaseName)) {
			return false;
		}
		if ($looksLikeTV && !preg_match('/[-._ ](flac|imageset|mp3|xxx)[-._ ]/i', $this->releaseName)) {
			if ($this->isOtherTV()) {
				return true;
			}
			if ($this->isForeignTV()) {
				return true;
			}
			if ($this->isSportTV()) {
				return true;
			}
			if ($this->isDocumentaryTV()) {
				return true;
			}
			if ($this->isWEBDL()) {
				return true;
			}
			if ($this->isHDTV()) {
				return true;
			}
			if ($this->isSDTV()) {
				return true;
			}
			if ($this->isAnimeTV()) {
				return true;
			}
			if ($this->isOtherTV2()) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}

		if ($looksLikeSportTV) {
			if ($this->isSportTV()) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	public function isOtherTV()
	{
		if (preg_match('/[-._ ](S\d{1,3}.+Extras|SUBPACK)[-._ ]|News/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}

	public function isForeignTV()
	{
		if (!preg_match('/[-._ ](NHL|stanley.+cup)[-._ ]/', $this->releaseName)) {
			if (preg_match('/[-._ ](chinese|dk|fin|french|heb|ita|jap|kor|nor|nordic|nl|pl|swe)[-._ ]?(sub|dub)(ed|bed|s)?/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](brazilian|chinese|croatian|danish|estonian|flemish|finnish|french|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish).+(720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](720p|1080p|Divx|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid).+(brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/(S\d\d[EX]\d\d|DOCU(MENTAIRE)?|TV)?[-._ ](FRENCH|Dutch)[-._ ](720p|1080p|dv(b|d)r(ip)?|LD|HD\-?TV|TV[-._ ]?RIP|x264)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ]FastSUB|NL|nlvlaams|patrfa|RealCO|Seizoen|slosinh|Videomann|Vostfr|xslidian[-._ ]|x264\-iZU/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isSportTV()
	{
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])/i', $this->releaseName)) {
			if (preg_match('/[-._ ]?(Bellator|bundesliga|EPL|ESPN|FIA|la[-._ ]liga|MMA|motogp|NFL|NCAA|PGA|red[-._ ]bull.+race|Sengoku|Strikeforce|supercup|uefa|UFC|wtcc|WWE)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(DTM|FIFA|formula[-._ ]1|indycar|Rugby|NASCAR|NBA|NHL|NRL|netball[-._ ]anz|ROH|SBK|Superleague|The[-._ ]Ultimate[-._ ]Fighter|TNA|V8[-._ ]Supercars|WBA|WrestleMania)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(AFL|Grand Prix|Indy[-._ ]Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW|Poker|PWX|Rugby|WCW)[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(Horse)[-._ ]Racing[-._ ]/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}
		}
		return false;
	}

	public function isDocumentaryTV()
	{
		if (preg_match('/[-._ ](Docu|Documentary)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
			return true;
		}
		return false;
	}

	public function isWEBDL()
	{
		if (preg_match('/web[-._ ]dl/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_WEBDL;
			return true;
		}
		return false;
	}

	public function isHDTV()
	{
		if (preg_match('/1080(i|p)|720p/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_HD;
			return true;
		}
		return false;
	}

	public function isSDTV()
	{
		if (preg_match('/(360|480|576)p|Complete[-._ ]Season|dvdr|dvd5|dvd9|SD[-._ ]TV|TVRip|xvid/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/((H|P)D[-._ ]?TV|DSR|WebRip)[-._ ]x264/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|\s\d{3,4}\s/i', $this->releaseName)) {
			if (preg_match('/(H|P)D[-._ ]?TV|BDRip[-._ ]x264/i', $this->releaseName)) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}
		}
		return false;
	}

	public function isAnimeTV()
	{
		if (preg_match('/[-._ ]Anime[-._ ]|^\(\[AST\]\s|\[HorribleSubs\]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_ANIME;
			return true;
		}
		return false;
	}

	public function isOtherTV2()
	{
		if (preg_match('/[-._ ]s\d{1,3}[-._ ]?(e|d)\d{1,3}[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}

	//
	//  Movie
	//

	public function isMovie()
	{
		if (preg_match('/[-._ ]AVC|[-._ ]|(B|H)(D|R)RIP|Bluray|BD[-._ ]?(25|50)?|BR|Camrip|[-._ ]\d{4}[-._ ].+(720p|1080p|Cam)|DIVX|[-._ ]DVD[-._ ]|DVD-?(5|9|R|Rip)|Untouched|VHSRip|XVID|[-._ ](DTS|TVrip)[-._ ]/i', $this->releaseName) && !preg_match('/[-._ ]exe$|[-._ ](jav|XXX)[-._ ]|\wXXX(1080p|720p|DVD)|Xilisoft/i', $this->releaseName)) {
			if ($this->isMovieForeign()) {
				return true;
			}
			if ($this->isMovieDVD()) {
				return true;
			}
			if ($this->isMovieSD()) {
				return true;
			}
			if ($this->isMovie3D()) {
				return true;
			}
			if ($this->isMovieBluRay()) {
				return true;
			}
			if ($this->isMovieHD()) {
				return true;
			}
			if ($this->isMovieOther()) {
				return true;
			}
		}
		return false;
	}

	public function isMovieForeign()
	{
		if (preg_match('/(danish|flemish|french|nl[-._ ]?sub(bed|s)?|\.NL|norwegian|swedish|swesub|spanish|Staffel)[-._ ]|\(german\)/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/Castellano/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/(720p|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|XVID)[-._ ](French|ITA)|\(?(French|ITA)\)?[-._ ](720P|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|HD[-._ ]|XVID)/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		return false;
	}

	public function isMovieDVD()
	{
		if (preg_match('/(dvd\-?r|[-._ ]dvd|dvd9|dvd5|[-._ ]r5)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_DVD;
			return true;
		}
		return false;
	}

	public function isMovieSD()
	{
		if (preg_match('/(divx|dvdscr|extrascene|dvdrip|\.CAM|vhsrip|xvid)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_SD;
			return true;
		}
		return false;
	}

	public function isMovie3D()
	{
		if (preg_match('/[-._ ]3D\s?[\.\-_\[ ](1080p|(19|20)\d\d|AVC|BD(25|50)|Blu[-._ ]?ray|CEE|Complete|GER|MVC|MULTi|SBS)[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_3D;
			return true;
		}
		return false;
	}

	public function isMovieBluRay()
	{
		if (preg_match('/bluray\-|[-._ ]bd?[-._ ]?(25|50)|blu-ray|Bluray\s\-\sUntouched|[-._ ]untouched[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_BLURAY;
			return true;
		}
		return false;
	}

	public function isMovieHD()
	{
		if (preg_match('/720p|1080p|AVC|VC1|VC\-1|web\-dl|wmvhd|x264|XvidHD|bdrip/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_HD;
			return true;
		}
		return false;
	}

	public function isMovieOther()
	{
		if (preg_match('/[-._ ]cam[-._ ]/i', $this->releaseName)) {
			$this->tmpCat = Category::CAT_MOVIE_OTHER;
			return true;
		}
		return false;
	}
}
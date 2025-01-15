<?php

# Class to create a publications database, implementing the Symplectic API
# Version 1.0.0

# Licence: GPL
# (c) Martin Lucas-Smith, University of Cambridge
# More info: https://github.com/camunigeog/symplectic-publications-client


#!# Consider adding support for direct upload of MP3 files, etc., in the same way as book covers


class symplecticPublicationsClient extends frontControllerApplication
{
	# Function to assign defaults additional to the general application defaults
	public function defaults ()
	{
		# Specify available arguments as defaults or as NULL (to represent a required argument)
		$defaults = array (
			'div' => strtolower (__CLASS__),
			'applicationName' => 'Publications database',
			'authentication' => true,
			'database' => 'publications',
			'table' => 'publications',
			'website' => NULL,
			'apiUrl' => NULL,
			'apiUsername' => false,
			'apiPassword' => false,
			'administrators' => 'administrators',
			'tabUlClass' => 'tabsflat',
			'yearsConsideredRecent' => 5,
			'yearsConsideredRecentMainListing' => 2,
			'canSplitIfTotal' => 10,
			'multisite' => false,	// Whether the user/group/member functions cover more than one organisation
			'getUsersFunction' => NULL,
			'getGroupsFunction' => NULL,
			'getGroupMembers' => NULL,
			'cronUsername' => NULL,
			'corsDomains' => array (),
			'bookcoversLocation' => 'bookcovers/',		// From baseUrl, or if starting with a slash, from DOCUMENT_ROOT
			'bookcoversFormat' => 'png',
			'bookcoversHeight' => 250,
			'enableRelationships' => false,		// Whether the relationships field in the API should be queried in limited circumstances
			'organisationDescription' => 'Department',	// String for all, or array to support multisite definitions
			'corsDomains' => array (),	// List of supported domains for JS embedding, respecting CORS
		);
		
		# Return the defaults
		return $defaults;
	}
	
	
	
	# Define the supported types and their labels
	private $types = array (
		'book'					=> 'Books',
		'journal-article'		=> 'Journal articles',
		'preprint'				=> 'Pre-prints',
		'chapter'				=> 'Book chapters',
		'conference'			=> 'Conference proceedings',
		'internet-publication'	=> 'Internet publications',
		'report'				=> 'Reports',
		'other'					=> 'Other publications',
		'thesis-dissertation'	=> 'Theses / dissertations',
		'c-19'					=> 'Working papers',
		'software'				=> 'Software',
		'presentation'			=> 'Presentations',
		'media'					=> 'Media',
		'performance'			=> 'Performances',
	//	'?'						=> 'Datasets',
	//	'patent'				=> 'Patents',
	//	'?'						=> 'Compositions',
	//	'?'						=> 'Designs',
	//	'?'						=> 'Artefacts',
	//	'?'						=> 'Exhibitions',
	//	'?'						=> 'Scholarly editions',
	//	'?'						=> 'Posters',
	);
	
	# Define the types that should use listing by year
	private $typesListingByYear = array (
		'journal-article',
	);
	
	# Define the types that should use the expandability system
	private $expandableTypes = array (
		'chapter',
		'journal-article',
		'report',
		'other',
	);
	
	
	# Function to assign additional actions
	public function actions ()
	{
		# Specify additional actions
		$actions = array (
			'home' => array (
				'description' => false,
				'url' => '',
				'icon' => 'house',
				'tab' => 'Home',
			),
			'bookcover' => array (
				'description' => 'Upload a book cover',
				'url' => 'bookcover.html',
				'icon' => 'book',
				'tab' => 'Book cover',
				'authentication' => true,
			),
			'recent' => array (
				'description' => 'Most recent publications',
				'url' => 'recent/',
				'icon' => 'new',
				'tab' => 'Recent',
			),
			'people' => array (
				'description' => 'People',
				'url' => 'people/',
				'icon' => 'user',
				'tab' => 'People',
			),
			'person' => array (
				'description' => 'Publications of person',
				'url' => 'people/%1/',
				'usetab' => 'people',
			),
			'groups' => array (
				'description' => 'Research groups',
				'url' => 'groups/',
				'icon' => 'group',
				'tab' => 'Research groups',
			),
			'group' => array (
				'description' => 'Publications of research group',
				'url' => 'groups/%1/',
				'usetab' => 'groups',
			),
			'statistics' => array (
				'description' => 'Statistics',
				'url' => 'statistics/',
				'tab' => 'Statistics',
				'administrator' => true,
			),
			'issues' => array (
				'description' => 'Issues - data problems to be fixed',
				'url' => 'issues.html',
				'tab' => 'Issues',
				'administrator' => true,
			),
			'import' => array (
				'description' => 'Import data from Symplectic',
				'url' => 'import/',
				'icon' => 'database_refresh',
				'tab' => 'Import',
				'administrator' => true,
			),
			'api' => array (
				'description' => 'API',
				'url' => '%/json',
				'authentication' => false,
				'export' => true,
			),
			'cron' => array (
				'description' => 'Cron hook for non-interactive processes',
				'url' => 'cron/',
				'authentication' => false,
				'export' => true,
			),
			'retrieve' => array (
				'description' => 'Retrieve raw data from the Symplectic API',
				'url' => 'retrieve.html',
				'parent' => 'admin',
				'subtab' => 'Retrieve raw data',
				'administrator'	=> true,
			),
		);
		
		# Return the actions
		return $actions;
	}
	
	
	# Database structure definition
	public function databaseStructure ()
	{
		return "
			CREATE TABLE `administrators` (
			  `crsid` varchar(10) NOT NULL PRIMARY KEY,
			  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
			  `name` varchar(255) NOT NULL,
			  `email` varchar(255) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Administrators';
			
			CREATE TABLE `instances` (
			`id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Automatic key' PRIMARY KEY,
			  `username` varchar(10) NOT NULL COMMENT 'Username',
			  `publicationId` int(11) NOT NULL COMMENT 'Publication ID',
			  `nameAppearsAsAuthor` varchar(255) DEFAULT NULL COMMENT 'The string appearing in the data for the name of the author',
			  `nameAppearsAsEditor` varchar(255) DEFAULT NULL COMMENT 'The string appearing in the data for the name of the editor',
			  `isFavourite` int(1) DEFAULT NULL COMMENT 'Favourite publication',
			  `savedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Automatic timestamp',
			  INDEX publicationId (publicationId)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Table of publications for each user';
			
			CREATE TABLE `publications` (
			  `id` int(11) NOT NULL COMMENT 'ID in original datasource' PRIMARY KEY,
			  `sourceName` varchar(255) NOT NULL COMMENT 'Source',
			  `type` varchar(255) DEFAULT NULL COMMENT 'Type',
			  `lastModifiedWhen` int(11) NOT NULL COMMENT 'Last modified when (Unixtime)',
			  `doi` varchar(255) DEFAULT NULL COMMENT 'DOI',
			  `title` text NOT NULL COMMENT 'Title',
			  `journal` varchar(255) DEFAULT NULL COMMENT 'Journal',
			  `publicationYear` varchar(255) DEFAULT NULL COMMENT 'Publication year',
			  `publicationMonth` varchar(255) DEFAULT NULL COMMENT 'Publication month',
			  `publicationDay` varchar(255) DEFAULT NULL COMMENT 'Publication day',
			  `dateIsAcceptance` INT(1) NULL DEFAULT NULL COMMENT 'Date is acceptance date',
			  `volume` varchar(255) DEFAULT NULL COMMENT 'Volume',
			  `issue` varchar(255) DEFAULT NULL COMMENT 'Issue',
			  `pagination` varchar(255) DEFAULT NULL COMMENT 'Pagination',
			  `publisher` varchar(255) DEFAULT NULL COMMENT 'Publisher',
			  `place` varchar(255) DEFAULT NULL COMMENT 'Place of publication',
			  `edition` varchar(255) DEFAULT NULL COMMENT 'Edition',
			  `editors` varchar(255) DEFAULT NULL COMMENT 'Editors',
			  `parentTitle` text DEFAULT NULL COMMENT 'Parent title',
			  `number` varchar(255) DEFAULT NULL COMMENT 'Number',
			  `authors` text COMMENT 'Authors',
			  `url` VARCHAR(255) NULL COMMENT 'URL',
			  `html` text NOT NULL COMMENT 'Compiled HTML representation of record',
			  `savedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Automatic timestamp'
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Publications';
			
			CREATE TABLE `userorganisations` (
			  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Automatic key' PRIMARY KEY,
			  `userId` varchar(10) NOT NULL COMMENT 'User ID (join to users.id)',
			  `organisation` varchar(255) NOT NULL COMMENT 'Organisation',
			  INDEX userId (userId)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Table of organisations of each user';
			
			CREATE TABLE `users` (
			  `id` varchar(10) NOT NULL COMMENT 'Username' PRIMARY KEY,
			  `forename` varchar(255) NOT NULL COMMENT 'Forename',
			  `surname` varchar(255) NOT NULL COMMENT 'Surname',
			  `savedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Automatic timestamp'
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Table of data of users who have publications';
			
			CREATE TABLE `instances_import` LIKE `instances`;
			CREATE TABLE `publications_import` LIKE `publications`;
			CREATE TABLE `userorganisations_import` LIKE `userorganisations`;
			CREATE TABLE `users_import` LIKE `users`;
			
			CREATE TABLE `exclude` (
			  `id` varchar(191) NOT NULL COMMENT 'Group' PRIMARY KEY,
			  `exclude` text COMMENT 'Publications to exclude, comma-separated',
			  `savedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Automatic timestamp'
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Table of publications to be excluded for a group';
		";
	}
	
	
	# Additional initialisation, prior to actions processing phase
	protected function mainPreActions ()
	{
		# Switch to API mode if specified
		$outputFormats = array ('json', 'html');
		if (isSet ($_GET['api']) && in_array ($_GET['api'], $outputFormats)) {
			$this->action = 'api';
		}
		
	}
	
	
	# Additional initiatialisation
	protected function main ()
	{
		# Set the first year when publications are considered old
		$this->firstOldYear = date ('Y') - $this->settings['yearsConsideredRecent'] - 1;	// e.g. 2015 gives 2009 as the old year
		
		# Define a database constraint string for types
		$this->typesConstraintString = "type IN('" . implode ("','", array_keys ($this->types)) . "')";
		
		# Determine the book covers directory; if a relative path, define it in relation to the baseUrl
		if (substr ($this->settings['bookcoversLocation'], 0, 1) != '/') {
			$this->settings['bookcoversLocation'] = $this->baseUrl . '/' . $this->settings['bookcoversLocation'];
		}
		
		# Ensure the book covers directory exists and is writable
		if (!is_dir ($_SERVER['DOCUMENT_ROOT'] . $this->settings['bookcoversLocation'])) {
			echo "\n<p class=\"warning\">The book covers directory is not present. The administrator needs to fix this problem before the system will run.</p>";
			return false;
		}
		if (!is_writable ($_SERVER['DOCUMENT_ROOT'] . $this->settings['bookcoversLocation'])) {
			echo "\n<p class=\"warning\">The book covers directory is not writable. The administrator needs to fix this problem before the system will run.</p>";
			return false;
		}
		
		# Define start words to strip in comma-and listings
		$this->commaAndStripStartingWords = array ('with');
	}
	
	
	# Show data date
	public function guiSearchBox ()
	{
		$tableStatus = $this->databaseConnection->getTableStatus ($this->settings['database'], $this->settings['table']);
		return $html = "\n<p class=\"small comment\"><!-- ignore-changes -->{$tableStatus['Comment']}<!-- /ignore-changes --></p>";
	}
	
	
	# API controller
	public function api ()
	{
		# Send CORS headers
		$this->corsHeaders ();
		
		# Get the data, which may be an error
		$data = $this->apiInner ($errorMessage);
		
		# Emit 404 header if an error (e.g. method wrong, or person not present, etc.)
		if (isSet ($data['json']['error'])) {
			application::sendHeader (404);
		}
		
		# Select the relevant rendering
		$outputFormat = $_GET['api'];	// Already validated in mainPreActions
		$data = $data[$outputFormat];
		
		# Determine output
		switch ($outputFormat) {
			
			# JSON
			case 'json':
				$data = json_encode ($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
				header ('Content-Type: application/json');
				break;
				
			# HTML
			case 'html':
				//
		}
		
		# Emit the data
		echo $data;
	}
	
	
	# API controller (inner)
	private function apiInner (&$errorMessage = '')
	{
		# Ensure an action is specified
		if (!isSet ($_GET['action'])) {
			return array ('json' => array ('error' => 'No method was specified.'), 'html' => '');
		}
		$action = $_GET['action'];
		
		# Ensure the specified action exists
		if (!isSet ($this->actions[$action])) {
			return array ('error' => 'An unsupported method was specified');
		}
		
		# Determine if an item is specified
		$item = (isSet ($_GET['item']) ? $_GET['item'] : false);
		
		# Get the data
		$data = $this->{$action} ($item);
		
		# Return the data
		return $data;
	}
	
	
	# Welcome screen
	public function home ()
	{
		# Introduction
		$html  = "\n<div class=\"graybox\">";
		$html .= "\n\t<p>This system contains data exported from the <a href=\"{$this->settings['website']}\" target=\"_blank\">Symplectic publications database portal</a>.</p>";
		$html .= "\n</div>";
		
		# Recent
		$html .= "\n<h3>Most recent</h3>";
		$html .= "\n<p><a href=\"{$this->baseUrl}/recent/\" class=\"actions\">" . '<img src="/images/icons/new.png" alt="*" class="icon" />' . " <strong>Most recent publications</strong></a></p>";
		
		# People
		$html .= "\n<h3>People</h3>";
		$html .= "\n<p><a href=\"{$this->baseUrl}/people/\" class=\"actions\">" . '<img src="/images/icons/user.png" alt="*" class="icon" />' . " <strong>Publications by person</strong></a></p>";
		
		# Research groups
		$html .= "\n<h3>Research groups</h3>";
		$html .= "\n<p><a href=\"{$this->baseUrl}/groups/\" class=\"actions\">" . '<img src="/images/icons/group.png" alt="*" class="icon" />' . " <strong>Publications by group</strong></a></p>";
		
		# Statistics
		if ($this->userIsAdministrator) {
			$html .= "\n<h3>Statistics</h3>";
			$data = $this->getStatistics ();
			$html .= application::htmlTable ($data, array (), 'statistics lines compressed');
		}
		
		# Show the HTML
		echo $html;
	}
	
	
	# Page to upload a book cover
	public function bookcover ()
	{
		# Start the HTML
		$html = '';
		
		# Ensure the person is present, or end
		if (!$this->userHasPublications ($this->user)) {
			$html .= "\n<p>You do not appear to have any books in the Symplectic system.</p>";
			echo $html;
			return true;
		}
		
		# Get the publications for that user
		if (!$data = $this->getPerson ($this->user, 'book')) {
			$html .= "\n<p>You do not appear to have any books in the Symplectic system.</p>";
			$html .= "\n<p>However, if you have added a book to Symplectic just now, please check back here in a few hours, as there is a slight delay for this website to pick up new publications from Symplectic.</p>";
			echo $html;
			return true;
		}
		
		# Arrange as key => title, and show whether there is currently a cover
		$books = array ();
		foreach ($data as $id => $book) {
			$books[$id] = ($book['thumbnail'] ? chr(0xe2).chr(0x9c).chr(0x93) : chr(0xe2).chr(0x96).chr(0xa2)) . ' ' . $book['title'];
		}
		
		# Assemble the book covers directory
		$directory = $_SERVER['DOCUMENT_ROOT'] . $this->settings['bookcoversLocation'];
		
		# Show the upload form
		$form = new form (array (
			'formCompleteText' => false,
			'div' => 'ultimateform',
		));
		$form->select (array (
			'name' => 'book',
			'title' => 'Book',
			'values' => $books,
			'required' => true,
		));
		$form->heading ('p', "Please select an image from your computer. It will be automatically resized to a height of {$this->settings['bookcoversHeight']}px.");
		$form->upload (array (
			'name'				=> 'image',
			'title'				=> 'Image',
			'required'			=> 1,
			'directory'			=> $directory,
			'allowedExtensions'	=> array ('jpg', 'gif', 'png', ),
			'forcedFileName'	=> $this->user,		// Avoids race condition issues
			'required'			=> true,
			'thumbnail'			=> true,
		));
		if (!$result = $form->process ()) {
			echo $html;
			return false;
		}
		
		# Rename the file to the ID of the book
		$tmpFile = $_SERVER['DOCUMENT_ROOT'] . $this->settings['bookcoversLocation'] . $result['image'][0];
		$uploadedFile = $_SERVER['DOCUMENT_ROOT'] . $this->settings['bookcoversLocation'] . $result['book'] . '-original' . '.' . pathinfo ($result['image'][0], PATHINFO_EXTENSION);
		rename ($tmpFile, $uploadedFile);
		
		# Resize
		$thumbnailFile = $_SERVER['DOCUMENT_ROOT'] . $this->settings['bookcoversLocation'] . $result['book'] . '.' . $this->settings['bookcoversFormat'];
		image::resize ($uploadedFile, $outputFormat = 'jpg', $newWidth = '', $this->settings['bookcoversHeight'], $thumbnailFile, false);
		
		# Confirm success
		$html  = "\n<p>{$this->tick} The book cover has been successfully uploaded.</p>";
		$html .= "\n<p>Please navigate to your public page on the website to see it.</p>";
		$html .= "\n<p><a href=\"{$this->baseUrl}/bookcover.html\">Add another?</a></p>";
		
		# Show the HTML
		echo $html;
	}
	
	
	# People listing page
	public function people ()
	{
		# Start the output HTML
		$html = '';
		
		# Provide API links
		$apiLinks = $this->apiLinks ();
		
		# Get the users from the local database
		$users = $this->getPeople ();
		
		# End if none
		if (!$users) {
			$html .= "\n<p>There are no users.</p>";
			if ($this->action == 'api') {return array ('json' => $users, 'html' => $html);}
			echo $html;
			return true;
		}
		
		# Create a listing
		$list = array ();
		foreach ($users as $username => $user) {
			$nameHtml = htmlspecialchars ($user['forename']) . ' <strong>' . htmlspecialchars ($user['surname']) . '</strong>';
			$list[$username] = "<a href=\"{$this->baseUrl}/people/{$username}/\">{$nameHtml} &lt;{$username}&gt; ({$user['total']})" . ($user['favourites'] ? " ({$user['favourites']}<img src=\"/images/general/star.png\" class=\"icon favourite\" />)" : '') . '</a>';
		}
		$html = application::htmlUl ($list);
		
		# API output
		if ($this->action == 'api') {return array ('json' => $users, 'html' => $html);}
		
		# Render the page HTML
		$pageHtml  = $apiLinks;
		$pageHtml .= "\n<p>Please select a user:</p>";
		$pageHtml .= $html;
		
		# Show the page HTML
		echo $pageHtml;
	}
	
	
	# Publications for a person
	public function person ($username = false)
	{
		# Start the output HTML
		$html = '';
		
		# Ensure the person is present, or end
		if (!$user = $this->userHasPublications ($username)) {
			$errorMessage = 'There is no such user.';
			if ($this->action == 'api') {return array ('json' => array ('error' => $errorMessage), 'html' => $html);}
			$html .= "\n<p>{$errorMessage}</p>";
			echo $html;
			return true;
		}
		
		# Determine whether to show starred items with the star
		$showStars = true;
		if (isSet ($_GET['stars']) && ($_GET['stars'] == '0')) {
			$showStars = false;
		}
		
		# Get the publications for that user
		$publications = $this->getPerson ($username);
		
		# Render as a list
		$html = $this->publicationsList ($publications, $showFeatured = true, $showStars);
		
		# API output
		if ($this->action == 'api') {return array ('json' => $publications, 'html' => $html);}
		
		# Show publications
		$nameHtml = htmlspecialchars ($user['forename']) . ' <strong>' . htmlspecialchars ($user['surname']) . '</strong>';
		$total = number_format (count ($publications));
		$pageHtml  = $this->apiLinks ();
		$pageHtml .= "\n<p id=\"introduction\">Publications ({$total}) for {$nameHtml}:</p>";
		$pageHtml .= "\n<hr />";
		$pageHtml .= $html;
		
		# Show the page HTML
		echo $pageHtml;
	}
	
	
	# Function to provide a side-by-side comparison system for migration
	public function autoreplace ($baseUrl, $username, $previewMode, $goLiveDate = 'soon')
	{
		# Ensure the page has a publications div
		if (!$contents = file_get_contents ($_SERVER['SCRIPT_FILENAME'])) {return false;}
		if (!substr_count ($contents, '<h2 id="publications">')) {return false;}
		
		# When live, do nothing if the user has no publications
		if (!$previewMode) {
			if (!$this->userHasPublications ($username)) {return false;}
		}
		
		# Determine if the user is authorised for internal functions
		$authorisedUser = false;
		if ($_SERVER['REMOTE_USER']) {
			
			# Determine if the user is an administrator
			$currentUser = $_SERVER['REMOTE_USER'];
			$userIsAdministrator = array_key_exists ($currentUser, $this->administrators);
			
			# End if not the current user or an administrator
			if (($currentUser == $username) || $userIsAdministrator) {
				$authorisedUser = true;
			}
		}
		$showToolsJs = ($authorisedUser ? 'true' : 'false');
		
		# In preview mode, require an authorised user
		if ($previewMode) {
			if (!$authorisedUser) {return false;}
		}
		
		$previewModeJs = ($previewMode ? 'true' : 'false');
		
		# Define the HTML
		$html = "\n\n
		<!-- Load publications -->
		<script src=\"{$baseUrl}/dist/symplecticPublications.js\"></script>
		<script>
				document.addEventListener ('DOMContentLoaded', function () {
					const settings = {
						baseUrl: '{$baseUrl}',
						username: '{$username}',
						showTools: {$showToolsJs},
						previewMode: {$previewModeJs},
						goLiveDate: '{$goLiveDate}',
						website: '{$this->settings['website']}'
					};
					symplecticPublications.init (settings);
				});
			</script>
		";
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to determine if a user has publications
	public function userHasPublications ($username)
	{
		# Get the users
		$users = $this->getPeople ();
		
		# If not present, return false
		if (!isSet ($users[$username])) {return false;}
		
		# Return the user's details
		return $users[$username];
	}
	
	
	# Research group listings
	public function groups ()
	{
		# Start the output HTML
		$html = '';
		
		# Provide API links
		$apiLinks = $this->apiLinks ();
		
		# Get the users and their organisations
		list ($groups, $groupsBySite) = $this->getGroups ();
		
		# End if none
		if (!$groups) {
			$html .= "\n<p>There are no research groups.</p>";
			if ($this->action == 'api') {return array ('json' => $groups, 'html' => $html);}
			echo $html;
			return true;
		}
		
		# Create a listing
		foreach ($groupsBySite as $organisation => $groupsThisSite) {
			$list = array ();
			foreach ($groupsThisSite as $id => $group) {
				$nameHtml = htmlspecialchars ($group['name']);
				$list[$id] = "<a href=\"{$this->baseUrl}/groups/{$id}/\">{$nameHtml}</a>";
			}
			if ($organisation) {
				$html .= "\n<h3>" . htmlspecialchars ($organisation) . ':</h3>';
			}
			$html .= application::htmlUl ($list);
		}
		
		# API output
		if ($this->action == 'api') {return array ('json' => $groups, 'html' => $html);}
		
		# Render the page HTML
		$pageHtml  = $apiLinks;
		$pageHtml .= "\n<p>Please select a research group:</p>";
		$pageHtml .= $html;
		
		# Show the page HTML
		echo $pageHtml;
	}
	
	
	# Publications for a research group
	public function group ($moniker = false)
	{
		# Start the output HTML
		$html = '';
		
		# Get the users and their organisations
		list ($groups, $groupsBySite) = $this->getGroups ();
		
		# Ensure the group is present, or end
		if (!isSet ($groups[$moniker])) {
			$errorMessage = 'There is no such group.';
			if ($this->action == 'api') {return array ('json' => array ('error' => $errorMessage), 'html' => $html);}
			$html .= "\n<p>{$errorMessage}</p>";
			echo $html;
			return true;
		}
		$group = $groups[$moniker];
		
		# Determine whether to show starred items with the star
		$showStars = true;
		if (isSet ($_GET['stars']) && ($_GET['stars'] == '0')) {
			$showStars = false;
		}
		
		# Get the members of the group
		$usernames = $this->getGroupMembersUpstream ($group['url']);
		
		# Get the publications for that user
		$publications = $this->getPeoplePublications ($usernames);
		
		# Determine publications determined already as filtered
		$currentlyFiltered = $this->databaseConnection->selectOneField ($this->settings['database'], 'exclude', 'exclude', array ('id' => $moniker));
		$currentlyFiltered = ($currentlyFiltered ? explode (',', $currentlyFiltered) : array ());		// Convert to array
		
		# Determine whether to enable the filtering UI, and if so, specify the moniker of the group
		$filteringUiGroup = (($this->userIsAdministrator || ($this->user && in_array ($this->user, $group['managers']))) ? ($this->action == 'group' ? $moniker : false) : false);
		
		# If a remote user has been passed through, add an editing link
		if (isSet ($_GET['REMOTE_USER'])) {
			$editingPageUrl = $_SERVER['_SITE_URL'] . $this->baseUrl . '/groups/' . $moniker . '/';
			$html .= "\n<p class=\"primaryaction right\"><a href=\"{$editingPageUrl}\" title=\"Edit the publications in this list, by filtering out unwanted items\"><img src=\"/images/icons/pencil.png\" class=\"icon\" /> Edit publications</a></p>";
		}
		
		# Render as a list
		$html .= $this->publicationsList ($publications, false, $showStars, $currentlyFiltered, $filteringUiGroup);
		
		# API output
		if ($this->action == 'api') {return array ('json' => $publications, 'html' => $html);}
		
		# Show publications
		$nameHtml = htmlspecialchars ($group['name']);
		$total = number_format (count ($publications));
		$pageHtml  = $this->apiLinks ();
		$pageHtml .= "\n<p id=\"introduction\">Recent publications ({$total}) of members of the <strong>{$nameHtml}</strong> research group:</p>";
		$pageHtml .= "\n<hr />";
		$pageHtml .= $html;
		
		# Show the page HTML
		echo $pageHtml;
	}
	
	
	# Most recent publications
	public function recent ()
	{
		# Start the output HTML
		$html = '';
		
		# Get the list of organisations (which will be done when multisite is disabled)
		$organisations = $this->getOrganisations ();
		
		# If an organisation has been specified, ensure that organisations are enabled and that Determine and validate the requested organisation, if any
		$organisation = (isSet ($_GET['organisation']) ? $_GET['organisation'] : false);
		if ($organisation) {
			if (!$organisations || !in_array ($organisation, $organisations)) {
				$this->page404 ();
				return false;
			}
		}
		
		# Determine the number of years to show
		$years = $this->settings['yearsConsideredRecentMainListing'];
		if (isSet ($_GET['years']) && ctype_digit ($_GET['years'])) {
			$years = $_GET['years'];
		}
		
		# Determine whether to show starred items with the star
		$showStars = true;
		if (isSet ($_GET['stars']) && ($_GET['stars'] == '0')) {
			$showStars = false;
		}
		
		# Get the most recent publications
		$publications = $this->getRecent ($years, $organisation);
		
		# Render as a list
		$html = $this->publicationsList ($publications, false, $showStars);
		
		# API output
		if ($this->action == 'api') {return array ('json' => $publications, 'html' => $html);}
		
		# Determine the organisation description
		$organisationDescription = $this->settings['organisationDescription'];
		if ($this->settings['multisite'] && is_array ($this->settings['organisationDescription'])) {
			if ($organisation) {
				$organisationDescription = $this->settings['organisationDescription'][$organisation];
			} else {
				$organisationDescriptionValues = array_values ($this->settings['organisationDescription']);
				$firstOrganisationDescription = array_shift ($organisationDescriptionValues);
				$organisationDescription = $firstOrganisationDescription;
			}
		}
		
		# Show publications
		$total = number_format (count ($publications));
		$pageHtml  = $this->organisationsTabs ($organisations);
		$pageHtml .= $this->apiLinks ();
		$pageHtml .= "\n<p id=\"introduction\">Most recent publications ({$total}) involving members of the {$organisationDescription} in the last {$years} " . ($years == 1 ? 'year' : 'years') . ":</p>";
		$pageHtml .= "\n<hr />";
		$pageHtml .= $html;
		
		# Show the page HTML
		echo $pageHtml;
	}
	
	
	# Function to determine the organisations present
	private function getOrganisations ()
	{
		# End if disabled
		if (!$this->settings['multisite']) {return false;}
		
		# Get an array of distinct organisations
		$query = "SELECT DISTINCT(organisation) FROM {$this->settings['database']}.userorganisations ORDER BY organisation;";
		$data = $this->databaseConnection->getPairs ($query);
		
		# Return the list
		return $data;
	}
	
	
	# Function to create a tab set of organisations
	private function organisationsTabs ($organisations)
	{
		# End if not multiple organisations
		if (!$organisations) {return false;}
		
		# Define a base link for all tabs
		$baseLink = $this->baseUrl . '/recent/';
		
		# Start with the default (all)
		$list = array ();
		$list[''] = 'View: ';
		$list[$baseLink] = "<a href=\"{$baseLink}\">All</a>";
		
		# Add a link for each organisation
		foreach ($organisations as $organisation) {
			$link = $baseLink . htmlspecialchars (urlencode ($organisation)) . '/';
			$list[$link] = "<a href=\"{$link}\">" . htmlspecialchars ($organisation) . '</a>';
		}
		
		# Compile the HTML
		$html = application::htmlUl ($list, 0, 'tabs subtabs', true, false, false, false, $selected = $_SERVER['REQUEST_URI']);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to provide an API link to the data equivalent of the current page
	private function apiLinks ()
	{
		# Construct the HTML
		$html  = "\n" . '<p class="right faded"><a href="json"><img src="/images/icons/feed.png" alt="JSON output" border="0" /> JSON</a> | <a href="html"><img src="/images/icons/feed.png" alt="JSON output" border="0" /> HTML</a></p>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to get the list of users from the database that have publications
	private function getPeople ()
	{
		# Get the data
		$query = "SELECT
				users.id,
				users.forename,
				users.surname,
				COUNT(instances.username) AS total,
				COUNT(instances.isFavourite) AS favourites
			FROM users
			LEFT JOIN instances ON users.id = instances.username
			LEFT JOIN publications ON instances.publicationId = publications.id
			WHERE {$this->typesConstraintString}
			GROUP BY instances.username
			HAVING total > 0
			ORDER BY surname, forename
		;";
		$data = $this->databaseConnection->getData ($query, "{$this->settings['database']}.users", true);
		
		# Return the data
		return $data;
	}
	
	
	# Function to get publications of a user from the database
	private function getPerson ($username, $type = false)
	{
		# Get the data
		$query = "SELECT
				publications.*,
				instances.isFavourite,
				instances.nameAppearsAsAuthor AS highlightAuthors,
				instances.nameAppearsAsEditor AS highlightEditors
			FROM instances
			LEFT OUTER JOIN publications ON instances.publicationId = publications.id
			WHERE
				    username = :username
				AND {$this->typesConstraintString}"
				. ($type ? " AND type = '{$type}'" : '') . "
			ORDER BY publicationYear DESC, authors
		;";
		$data = $this->databaseConnection->getData ($query, "{$this->settings['database']}.instances", true, array ('username' => $username));
		
		# Highlight the authors
		$data = $this->decoratePublicationsRuntime ($data);
		
		# Return the data
		return $data;
	}
	
	
	# Function to get publications of a set of users from the database
	private function getPeoplePublications ($usernames)
	{
		# Assemble the username list into a regexp
		$usernames = '^(' . implode ('|', $usernames) . ')$';
		
		# Get the data; uses GROUP_CONCAT method as described at https://www.percona.com/blog/2013/10/22/the-power-of-mysql-group_concat/
		$query = "SELECT
				publications.*,
				GROUP_CONCAT(DISTINCT instances.isFavourite) AS isFavourite,
				GROUP_CONCAT(DISTINCT instances.nameAppearsAsAuthor ORDER BY nameAppearsAsAuthor SEPARATOR '|') AS highlightAuthors,
				GROUP_CONCAT(DISTINCT instances.nameAppearsAsEditor ORDER BY nameAppearsAsEditor SEPARATOR '|') AS highlightEditors
			FROM instances
			LEFT OUTER JOIN publications ON instances.publicationId = publications.id
			WHERE
				    username REGEXP :usernames
				AND {$this->typesConstraintString}
				AND CAST(publicationYear AS UNSIGNED INT) > '{$this->firstOldYear}'
			GROUP BY publications.id
			ORDER BY publicationYear DESC, authors
		;";
		$data = $this->databaseConnection->getData ($query, "{$this->settings['database']}.publications", true, array ('usernames' => $usernames));
		
		# Highlight the authors and add starring
		$data = $this->decoratePublicationsRuntime ($data);
		
		# Return the data
		return $data;
	}
	
	
	# Function to get the most recent publications
	private function getRecent ($years, $organisation = false)
	{
		# Determine constraints
		$preparedStatementValues = array ();
		if ($organisation) {
			$preparedStatementValues['organisation'] = $organisation;
		}
		
		# Get the data
		$firstOldYearMainListing = date ('Y') - $years - 1;
		$query = "SELECT
				publications.*,
				GROUP_CONCAT(DISTINCT instances.isFavourite) AS isFavourite,
				GROUP_CONCAT(DISTINCT instances.nameAppearsAsAuthor ORDER BY nameAppearsAsAuthor SEPARATOR '|') AS highlightAuthors,
				GROUP_CONCAT(DISTINCT instances.nameAppearsAsEditor ORDER BY nameAppearsAsEditor SEPARATOR '|') AS highlightEditors
			FROM instances
			LEFT OUTER JOIN publications ON instances.publicationId = publications.id
			LEFT JOIN userorganisations ON instances.username = userorganisations.userId	/* Only actually needed when organisation constraint present */
			WHERE
				    CAST(publicationYear AS UNSIGNED INT) > '{$firstOldYearMainListing}'
				AND {$this->typesConstraintString}
				" . ($organisation ? ' AND organisation = :organisation' : '') . "
			GROUP BY publications.id
			ORDER BY publicationYear DESC, authors
		;";
		$data = $this->databaseConnection->getData ($query, "{$this->settings['database']}.instances", true, $preparedStatementValues);
		
		# Highlight the authors and add starring if required
		$data = $this->decoratePublicationsRuntime ($data);
		
		# Return the data
		return $data;
	}
	
	
	# Function to decorate publications at runtime (e.g. highlight the authors and add stars)
	private function decoratePublicationsRuntime ($data)
	{
		# Highlight authors
		foreach ($data as $id => $publication) {
			$publication['html'] = $this->highlightContributors ($publication, 'authors', 'highlightAuthors');
			$publication['html'] = $this->highlightContributors ($publication, 'editors', 'highlightEditors');
			$data[$id]['html'] = $publication['html'];
		}
		
		# Add book covers if present
		foreach ($data as $id => $publication) {
			$data[$id]['thumbnail'] = false;
			$data[$id]['thumbnailHtml'] = false;
			$location = $this->settings['bookcoversLocation'] . $id . '.' . $this->settings['bookcoversFormat'];
			if (file_exists ($_SERVER['DOCUMENT_ROOT'] . $location)) {
				list ($width, $height, $type, $attributesHtml) = getimagesize ($_SERVER['DOCUMENT_ROOT'] . $location);
				$altHtml = htmlspecialchars ($publication['title']);
				#!# Need to add a configuration option for whether book covers (and other assets) should have $_SERVER['_SITE_URL'] prepended or not
				$data[$id]['thumbnail'] = $location;
				$data[$id]['thumbnailHtml'] = "<img src=\"{$data[$id]['thumbnail']}\" {$attributesHtml} alt=\"{$altHtml}\" class=\"bookcover\" />";
			}
		}
		
		// application::dumpData ($data);
		
		# Return the data
		return $data;
	}
	
	
	# Function to perform contributor highlighting
	public function highlightContributors ($publication, $field, $highlightField)
	{
		# Convert the full list of contributors and the list of contributors to be highlighted into arrays
		$contributorsOriginal = (strlen ($publication[$field]) ? explode ('|', $publication[$field]) : array ());
		$highlightContributors = (strlen ($publication[$highlightField]) ? explode ('|', $publication[$highlightField]) : array ());
		
		# Add bold to any contributor which is set to be highlighted
		$contributors = $contributorsOriginal;
		foreach ($contributors as $index => $contributor) {
			if (in_array ($contributor, $highlightContributors, true)) {		// Strict matching applied
				$contributors[$index] = '<strong>' . $contributor . '</strong>';
			}
		}
		
		# Convert the original contributors list and the highlighted versions into "A, B, and C" format, as per the original HTML
		$contributorsOriginal = application::commaAndListing ($contributorsOriginal, $this->commaAndStripStartingWords);
		$contributorsHighlighted = application::commaAndListing ($contributors, $this->commaAndStripStartingWords);
		
		# Substitute the contributors listing at the start of the HTML with the new contributors listing
		$delimiter = '/';
		$html = preg_replace ($delimiter . '^' . addcslashes ($contributorsOriginal, $delimiter) . $delimiter, $contributorsHighlighted, $publication['html']);
		
		# Return the HTML
		return $html;
	}
	
	
	# Statistics page
	public function statistics ()
	{
		# Start the HTML
		$html = '';
		
		# Add a form to enable limiting
		$dateFilters = $this->dateFilterForm ($html);
		
		# Get the data
		$data = $this->getStatistics ($dateFilters['startDate'], $dateFilters['untilDate']);
		
		# Render as a table
		$html .= "\n<p>This page shows the data available in this system.</p>";
		$html .= "\n<p>You can filter by date, where the date of a publication is known, using the controls on the right.</p>";
		$html .= application::htmlTable ($data, array (), 'statistics lines');
		
		# Show the HTML
		echo $html;
	}
	
	
	# Issues page
	public function issues ()
	{
		# Start the HTML
		$html = '';
		
		# Publications without a date
		$data = $this->getYearMissing ();
		$html .= "\n<h3>Publications without a date (" . count ($data) . ')</h3>';
		$html .= "\n<p>This page shows publications without a date:</p>";
		$html .= $this->publicationsToDebugList ($data);
		
		# Publications without any authors
		$data = $this->getAuthorsMissing ();
		$html .= "\n<h3>Publications without any authors (" . count ($data) . ')</h3>';
		$html .= "\n<p>This page shows publications without any authors:</p>";
		$html .= $this->publicationsToDebugList ($data);
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to render a list of publications for debugging as a list
	private function publicationsToDebugList ($data)
	{
		# Convert to list
		$list = array ();
		foreach ($data as $publicationId => $publication) {
			$list[$publicationId] = "<a href=\"{$this->settings['website']}viewobject.html?cid=1&amp;id={$publicationId}\" target=\"_blank\">Edit {$publication['type']}</a> (" . htmlspecialchars ($publication['sourceName']) . ") | <a href=\"https://www.google.co.uk/search?q=" . htmlspecialchars (urlencode ($publication['title'])) . "\" target=\"_blank\">Google search</a>: " . $publication['html'];
		}
		
		# Compile to HTML
		$html = application::htmlUl ($list, false, 'spaced');
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to get publications without a year
	private function getYearMissing ()
	{
		# Get the data
		return $this->databaseConnection->select ($this->settings['database'], $this->settings['table'], array ('publicationYear' => NULL), array ('id', 'sourceName', 'type', 'title', 'html'), true, 'type, html');
	}
	
	
	# Function to get publications without any authors
	private function getAuthorsMissing ()
	{
		# Get the data
		return $this->databaseConnection->select ($this->settings['database'], $this->settings['table'], array ('authors' => NULL), array ('id', 'sourceName', 'type', 'title', 'html'), true, 'type, html');
	}
	
	
	# Function to provide a date filtering form
	private function dateFilterForm (&$html)
	{
		# Default
		$filter = array (
			'startDate'		=> false,
			'untilDate'	=> false,
		);
		
		# Create the form
		$form = new form (array (
			'div' => 'datefilter ultimateform graybox',
			'displayRestrictions' => false,
			'nullText' => false,
			'display' => 'template',
			'displayTemplate' => "\n{[[PROBLEMS]]}" . "\n<p>Optional date filters:</p>\n<p><span>Earliest:</span> {startDate}<br /><span>Latest:</span> {untilDate}<br />{[[SUBMIT]]}\n<span class=\"reset small\">or <a href=\"{$this->baseUrl}/statistics/\">reset</a></span></p>",
			'submitButtonText' => 'Apply filter',
			'submitButtonAccesskey' => false,
			'formCompleteText' => false,
			'requiredFieldIndicator' => false,
			'reappear' => true,
		));
		$form->datetime (array (
			'name'			=> 'startDate',
			'title'			=> 'Earliest date',
			'picker'		=> true,
		));
		$form->datetime (array (
			'name'			=> 'untilDate',
			'title'			=> 'Latest date',
			'picker'		=> true,
		));
		$form->validation ('either', array ('startDate', 'untilDate'));
		if ($unfinalisedData = $form->getUnfinalisedData ()) {
			if ($unfinalisedData['startDate'] && $unfinalisedData['untilDate']) {
				if ($unfinalisedData['startDate'] > $unfinalisedData['untilDate']) {
					$form->registerProblem ('timeOrderingInvalid', 'The dates must be in order.');
				}
			}
		}
		
		# Process the form
		if ($result = $form->process ($html)) {
			$filter = $result;
		}
		
		# Return the dates
		return $filter;
	}
	
	
	# Function to get the statistics data
	private function getStatistics ($startDate = false, $untilDate = false)
	{
		# Start an array of data
		$data = array ();
		
		# Define organisations
		$organisations = array ();
		if ($this->settings['multisite']) {
			$sites = $this->getOrganisations ();
			foreach ($sites as $organisation) {
				$organisations[$organisation] = true;	// true indicates additional clause in query
			}
		}
		$organisations['total'] = false;	// false indicates no additional clause in query
		
		# Start an array of constraints
		$constraints = array ();
		
		# Determine date limitation SQL
		$publicationDateSql = "STR_TO_DATE( CONCAT(IFNULL(publicationDay, '1'), ',', IFNULL(publicationMonth, '1'), ',', publicationYear), '%d,%m,%Y')";
		if ($startDate) {
			$constraints[] = "{$publicationDateSql} >= '{$startDate}'";
		}
		if ($untilDate) {
			$constraints[] = "{$publicationDateSql} <= '{$untilDate}'";
		}
		
		# Get the distinct publication types in the data
		$availableTypes = $this->databaseConnection->getPairs ("SELECT DISTINCT(type) FROM {$this->settings['database']}.{$this->settings['table']} ORDER BY type;");
		
		# Create listings for each organisation
		foreach ($organisations as $organisation => $filterQuery) {
			
			# Determine filter
			$preparedStatementValues = array ();
			if ($filterQuery) {
				$preparedStatementValues = array ('organisation' => $organisation);
			}
			
			# Total users
			$query = "SELECT
					COUNT(*) AS total
				FROM (
					SELECT
						users.id
					FROM (
						/* Pre-filter table for matches with the relevant organisation (if any) */
						SELECT users.id
						FROM {$this->settings['database']}.users
						LEFT JOIN userorganisations ON users.id = userorganisations.userId"
						. ($filterQuery ? ' WHERE organisation = :organisation' : '') . "
						GROUP BY id
					) AS users
					LEFT JOIN instances ON users.id = instances.username
					LEFT JOIN publications ON instances.publicationId = publications.id
					" . ($constraints ? 'WHERE ' . implode (' AND ', $constraints) : '') . "
					GROUP BY users.id
				) AS userPublications
			;";
			$data['Users'][$organisation] = $this->databaseConnection->getOneField ($query, 'total', $preparedStatementValues);
			
			# Publication types
			$query = "SELECT
					type,
					COUNT(*) AS total
				FROM (
					/* Pre-filter table for matches with the relevant organisation (if any) */
					SELECT {$this->settings['table']}.*
					FROM {$this->settings['database']}.{$this->settings['table']}
					LEFT JOIN instances ON {$this->settings['table']}.id = instances.publicationId
					LEFT JOIN userorganisations ON instances.username = userorganisations.userId"
					. ($filterQuery ? ' WHERE organisation = :organisation' : '') . "
					GROUP BY id
				) AS records
				" . ($constraints ? 'WHERE ' . implode (' AND ', $constraints) : '') . "
				GROUP BY type
				ORDER BY type
			;";
			$typeTotals = $this->databaseConnection->getPairs ($query, false, $preparedStatementValues);
			foreach ($availableTypes as $type) {
				$typeLabel = 'Type - ' . $type;
				$data[$typeLabel][$organisation] = (isSet ($typeTotals[$type]) ? $typeTotals[$type] : 0);		// A type with no matches for the organisation concerned will thus not be present in the data
			}
			
			# Total records
			$query = "SELECT
					COUNT(*) AS total
				FROM (
					/* Pre-filter table for matches with the relevant organisation (if any) */
					SELECT {$this->settings['table']}.*
					FROM {$this->settings['database']}.{$this->settings['table']}
					LEFT JOIN instances ON {$this->settings['table']}.id = instances.publicationId
					LEFT JOIN userorganisations ON instances.username = userorganisations.userId"
					. ($filterQuery ? ' WHERE organisation = :organisation' : '') . "
					GROUP BY id
				) AS records
				" . ($constraints ? 'WHERE ' . implode (' AND ', $constraints) : '') . "
			;";
			$data['Publications'][$organisation] = $this->databaseConnection->getOneField ($query, 'total', $preparedStatementValues);
			
			# Total favourited items
			$thisConstraints = array_merge ($constraints, array ('isFavourite = 1'));
			$query = "SELECT
					COUNT(*) AS total
				FROM (
					/* Pre-filter table for matches with the relevant organisation (if any) */
					SELECT instances.*, publications.publicationYear, publications.publicationMonth, publications.publicationDay
					FROM {$this->settings['database']}.instances
					LEFT JOIN userorganisations ON instances.username = userorganisations.userId
					LEFT JOIN publications ON instances.publicationId = publications.id"
					. ($filterQuery ? ' WHERE organisation = :organisation' : '') . "
					GROUP BY instances.id
				) AS records
				" . ($thisConstraints ? 'WHERE ' . implode (' AND ', $thisConstraints) : '') . "
			;";
			$data['Favourited'][$organisation] = $this->databaseConnection->getOneField ($query, 'total', $preparedStatementValues);
		}
		
		# Apply number formatting decoration to each entry
		foreach ($data as $key => $values) {
			foreach ($organisations as $organisation => $filterQuery) {
				$data[$key][$organisation] = number_format ($values[$organisation]);
			}
		}
		
		# Return the data
		return $data;
	}
	
	
	# Function to get the total number of publications
	private function getTotalPublications ()
	{
		# Return the count
		return $this->databaseConnection->getTotal ($this->settings['database'], $this->settings['table']);
	}
	
	
	# Import page
	public function import ()
	{
		# Start the HTML
		$html = '';
		
		# Show current statistics
		$totalPublicationsCurrently = $this->getTotalPublications ();
		$html .= "\n<p>There are currently " . number_format ($totalPublicationsCurrently) . " publications imported.</p>";
		
		# Ensure an import is not running
		if ($importHtml = $this->importInProgress ($detectStaleLockfileHours = 4)) {
			$html .= $importHtml;
			echo $html;
			return false;
		}
		
		# Ensure the lockfile directory is writable
		$lockdir = dirname ($this->lockfile);
		if (!is_writable ($lockdir)) {
			$html .= "\n<p class=\"error\"><em>Error: The lockfile directory is not writable, so an import cannot be run.</em></p>";
			echo $html;
			return false;
		}
		
		# Create the form
		if (!$result = $this->runImportForm ($html)) {
			echo $html;
			return true;
		}
		$html = '';		// Reset the HTML
		
		# Start a timer
		$startTime = time ();
		
		# Run the import
		if (!$totalPublications = $this->doImport ($_SERVER['REMOTE_USER'], $importOutputHtml)) {
			$html .= $importOutputHtml;
			echo $importOutputHtml;
			return false;
		}
		
		# Determine how long the import took
		$finishTime = time ();
		$seconds = $finishTime - $startTime;
		
		# Confirm success
		$html .= "\n<div class=\"graybox\">";
		$html .= "\n\t<p>{$this->tick} " . number_format ($totalPublications) . ' publications were imported.</p>';
		$html .= "\n\t<p>The import took: {$seconds} seconds.</p>";
		$html .= "\n</div>";
		
		# Show output from the import
		$html .= "\n<p>The following warnings were found during the import process, and should be fixed in the source data:</p>";
		$html .= $importOutputHtml;
		
		# Show the HTML
		echo $html;
	}
	
	
	# Define cron jobs; run as per example file .cron-example.job
	protected function cronJobs ()
	{
		# Run the import
		$this->doImport ('cron');
	}
	
	
	# Function to run the import
	private function doImport ($user, &$html = '')
	{
		# Start the HTML
		$html = '';
		
		# Ensure another import is not running
		if ($importHtml = $this->importInProgress ()) {
			$html .= $importHtml;
			return $html;
		}
		
		# Write the lockfile
		$now = time ();
		file_put_contents ($this->lockfile, 'full' . ' ' . $user . ' ' . date ('Y-m-d H:i:s', $now));
		
		# Clear any existing data from the import tables; this should have been done at the end of any previous import
		$tables = array ($this->settings['table'], 'instances', 'users', 'userorganisations');
		foreach ($tables as $table) {
			$this->databaseConnection->truncate ($this->settings['database'], "{$table}_import", true);
		}
		
		# Get the users and their organisations
		list ($users, $userOrganisations) = $this->getUsers ();
		
		# End if no users
		if (!$users) {
			$html .= "\n<p>There are no users.</p>";
			unlink ($this->lockfile);
			return false;
		}
		
		# Add the user organisations to the database
		$this->databaseConnection->insertMany ($this->settings['database'], 'userorganisations' . '_import', $userOrganisations, $chunking = false);
		
		# Obtain the sources list, ordered by precedence
		$sources = $this->getSources ();
		
		# Import the publications of each user
		foreach ($users as $username => $user) {
			
			# Get the publications of this user, or skip
			if (!$publications = $this->retrievePublicationsOfUser ($username, $sources, $html, $isFatalError)) {
				
				# Report fatal errors for this user
				if ($isFatalError) {
					unlink ($this->lockfile);
					return false;
				}
				
				# Continue to next user for non-fatal errors
				continue;
			}
			
			# Assemble the publications IDs for this user
			$instances = array ();
			foreach ($publications as $publicationId => $publication) {
				$instances[] = array (
					'username' => $username,
					'publicationId' => $publicationId,
					'nameAppearsAsAuthor' => $publication['nameAppearsAsAuthor'],
					'nameAppearsAsEditor' => $publication['nameAppearsAsEditor'],
					'isFavourite' => $publication['isFavourite'],	// This is a user-specific value
				);
				
				# Prevent leakage into the stored publication data
				unset ($publications[$publicationId]['nameAppearsAsAuthor']);
				unset ($publications[$publicationId]['nameAppearsAsEditor']);
				unset ($publications[$publicationId]['isFavourite']);
			}
			
			# Add the instances to the database
			$this->databaseConnection->insertMany ($this->settings['database'], 'instances' . '_import', $instances, $chunking = false);
			
			# Add each publication to the database, replacing if it already exists
			$this->databaseConnection->replaceMany ($this->settings['database'], $this->settings['table'] . '_import', $publications, $chunking = 5);
			
			# Insert the user into the local database
			$user['id'] = $user['username'];
			unset ($user['username']);
			$this->databaseConnection->insert ($this->settings['database'], 'users' . '_import', $user);
		}
		
		# For each table, clear existing data from the live table, cross-insert the new data, and clear up the import table
		foreach ($tables as $table) {
			$this->databaseConnection->truncate ($this->settings['database'], $table, true);
			$this->databaseConnection->query ("INSERT INTO {$table} SELECT * FROM {$table}_import;");
			$this->databaseConnection->query ("ALTER TABLE {$table} COMMENT = 'Data from Symplectic dated: " . date ('ga, jS F, Y', $now) . "';");	// Needs ALTER privileges; will silently fail otherwise
			$this->databaseConnection->truncate ($this->settings['database'], "{$table}_import", true);
		}
		
		# Get the number of publications
		$totalPublications = $this->getTotalPublications ();
		
		# Remove the lockfile
		unlink ($this->lockfile);
		
		# Signal success by returning the number of publications
		return $totalPublications;
	}
	
	
	# Function to create the run import form
	private function runImportForm (&$html)
	{
		# Create the form
		$form = new form (array (
			'submitButtonText' => 'Begin import!',
			'div' => 'graybox',
			'name' => 'import',
			'requiredFieldIndicator' => false,
			'formCompleteText' => false,
		));
		#!# Bogus input needed only because ultimateForm currently can't be empty
		$form->hidden (array (
			'values'	=> array ('bogus' => true),
			'name'		=> 'hidden',
			'title'		=> '',
		));
		
		# End if not submitted
		$result = $form->process ($html);
		
		# Return the status
		return $result;
	}
	
	
	# Function to get the users and their organisations
	private function getUsers ()
	{
		# Get the users from the config
		$usersRaw = $this->getUsersUpstream ();
		
		# Ensure the users are nested by organisation (site1 => users1, site2, => users2, ...)
		if (!$this->settings['multisite']) {
			$usersRaw = array ('global' => $usersRaw);
		}
		
		# Loop through the user groups, to extract user data
		$users = array ();
		$userOrganisations = array ();
		foreach ($usersRaw as $organisation => $usersThisSite) {
			
			# Merge into the master list of users
			$users = array_merge ($users, $usersThisSite);
			
			# Capture the organisations of the users
			foreach ($usersThisSite as $username => $user) {
				$userOrganisations[] = array (
					'userId' => $username,
					'organisation' => $organisation,
				);
			}
		}
		
		# Return the two arrays
		return array ($users, $userOrganisations);
	}
	
	
	# Function to obtain the sources list, ordered by precedence
	private function getSources ()
	{
		# Start a list of sources
		$sources = array ();
		
		# Get the data
		if (!$xpathDom = $this->getData ('/publication/sources')) {return $sources;}
		
		# Loop through each entry in the data; see: https://stackoverflow.com/questions/11886176/ and https://stackoverflow.com/questions/5929263/
		$entriesNode = $xpathDom->query ('/default:feed/default:entry');
		foreach ($entriesNode as $index => $entryNode) {
			
			# Obtain the properties
			$precedence = $this->XPath ($xpathDom, './api:data-source/api:precedence/api:type[@name="default"]/@precedence-value', $entryNode);
			$sources[$precedence] = array (
				'id' => $this->XPath ($xpathDom, './api:data-source/@id', $entryNode),
				'name' => $this->XPath ($xpathDom, './api:data-source/@name', $entryNode),
				'title' => $this->XPath ($xpathDom, './api:data-source/api:display-name', $entryNode),
				'precedence' => $precedence,
			);
		}
		
		# Order by precedence
		ksort ($sources);
		
		# Return the data
		return $sources;
	}
	
	
	# Function to get the groups, both as a unified list and grouped by site
	private function getGroups ()
	{
		# Get the groups from the config
		$groupsRaw = $this->getGroupsUpstream ();
		
		# Ensure the groups are nested by organisation (site1 => groups1, site2, => groups2, ...)
		if ($this->settings['multisite']) {
			$groupsBySite = $groupsRaw;
		} else {
			$groupsBySite = array ('' => $groupsRaw);	// Unnamed, so that no title is shown
		}
		
		# Merge each group into the master list of groups
		$groups = array ();
		foreach ($groupsRaw as $organisation => $groupsThisSite) {
			$groups = array_merge ($groups, $groupsThisSite);
		}
		
		# Return the two arrays
		return array ($groups, $groupsBySite);
	}
	
	
	# Function to create a formatted list of publications
	# Desired format is:
	// Batchelor, C.L., Dowdeswell, J.A. and Pietras, J.T., 2014. Evidence for multiple Quaternary ice advances and fan development from the Amundsen Gulf cross-shelf trough and slope, Canadian Beaufort Sea margin. Marine and Petroleum Geology, v. 52, p.125-143. doi:10.1016/j.marpetgeo.2013.11.005
	public function publicationsList ($publications, $showFeatured = false, $showStars = true, $currentlyFiltered = array (), $filteringUiGroup = false)
	{
		# Start the HTML
		$html = '';
		
		# In the public listing, filter out unwanted publications if required; the UI still requires these, however, so that they can be shown with buttons, faded
		if (!$filteringUiGroup) {
			foreach ($currentlyFiltered as $excludePublicationId) {
				if (isSet ($publications[$excludePublicationId])) {
					unset ($publications[$excludePublicationId]);
				}
			}
		}
		
		# Determine favourites
		$favourites = array ();
		foreach ($publications as $publicationId => $publication) {
			if ($publication['isFavourite']) {
				$key = 'publication' . $publicationId;
				$favourites[$key] = $publication['html'];
			}
		}
		
		# Add stars if required
		if ($showStars) {
			foreach ($publications as $publicationId => $publication) {
				if ($publication['isFavourite']) {
					$publications[$publicationId]['html'] = '<img src="/images/general/star.png" class="icon favourite" /> ' . $publication['html'];
				}
			}
		}
		
		# If the filtering interface is required, add placeholders to each publication entry; this is done after the favourites stage, to avoid duplication of placeholders
		if ($filteringUiGroup) {
			$placeholders = array ();
			foreach ($publications as $publicationId => $publication) {
				$placeholders[$publicationId] = '{' . $publicationId . '}';
				$publications[$publicationId]['html'] = $placeholders[$publicationId] . ' ' . $publication['html'];
			}
		}
		
		# Show favourites if enabled
		if ($showFeatured) {
			if ($favourites) {
				$html .= "\n<h3>Featured publications</h3>";
				$html .= application::htmlUl ($favourites, 0, NULL, true, false, false, $liClass = true);
			}
		}
		
		# Regroup by type
		$publicationsByType = application::regroup ($publications, 'type');
		
		# Work through each enabled type
		foreach ($this->types as $type => $label) {
			
			# Skip if none of this type
			if (!isSet ($publicationsByType[$type])) {continue;}
			
			# Create a listing for this type
			if (in_array ($type, $this->typesListingByYear)) {
				$html .= $this->publicationsListByYear ($publicationsByType[$type], $label, $type, $favourites, $showStars);
			} else {
				$html .= $this->publicationsListSimple ($publicationsByType[$type], $label, $type);
			}
		}
		
		# Surround with a div
		$html = "\n\n\n<div id=\"publicationslist\">" . "\n" . $html . "\n\n</div><!-- /#publicationslist -->\n\n";
		
		# Add book cover CSS
		$html = "\n\n<style type=\"text/css\">\n\t#publicationslist p.bookcovers img {margin-right: 12px; margin-bottom: 16px; box-shadow: 5px 5px 10px #888;}\n</style>" . $html;
		
		# Add the filtering form if required
		if ($filteringUiGroup) {
			$html = $this->filteringForm ($filteringUiGroup, $currentlyFiltered, $placeholders, $html);
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to provide a filtering form
	private function filteringForm ($group, $currentlyFiltered, $placeholders, $template)
	{
		# Start the HTML
		$html  = '';
		
		# Create the form
		$form = new form (array (
			'displayRestrictions' => false,
			'formCompleteText' => false,
			'display' => 'template',
			'displayTemplate' => '<p>{[[SUBMIT]]}</p>' . '{[[PROBLEMS]]}' . $template . '<p>{[[SUBMIT]]}</p>',
			'reappear' => true,
			'submitButtonText' => 'Exclude ticked publications',
			'unsavedDataProtection' => true,
		));
		foreach ($placeholders as $publicationId => $placeholder) {
			$form->checkboxes (array (
				'title'			=> 'Exclude #' . $publicationId,
				'name'			=> $publicationId,
				'values'		=> array ('exclude'),
				'labels'		=> false,
				'linebreaks'	=> false,
				'output'		=> array ('processing' => 'special-setdatatype'),	// Flattens the output
				'default'		=> (in_array ($publicationId, $currentlyFiltered) ? 'exclude' : false),
			));
		}
		# Add hidden field so that form will be processed even if no checkboxes are ticked
		#!# This should ideally be handled by ultimateForm natively
		$form->hidden (array (
			'values'	=> array ('discard'),
			'name'		=> 'discard',
			'discard'	=> true,
		));
		if ($result = $form->process ($html)) {
			
			# Determine excluded values
			$exclude = array ();
			foreach ($result as $publicationId => $excluded) {
				if ($excluded) {
					$exclude[] = $publicationId;
				}
			}
			
			# Assemble the database values
			$data = array (
				'id' => $group,
				'exclude' => implode (',', $exclude),
			);
			
			# Insert/update the value
			$this->databaseConnection->insert ($this->settings['database'], 'exclude', $data, $onDuplicateKeyUpdate = true);
			
			# Confirm success
			$confirmationHtml  = "\n" . '<div class="graybox">';
			$totalExcluded = count ($exclude);
			$confirmationHtml .= "\n<p>{$this->tick} " . ($totalExcluded ? ($totalExcluded == 1 ? 'One publication is now' : "{$totalExcluded} publications are now") : 'No publications are') . ' being excluded from the public listing' . ($totalExcluded ? ', shown faded-out below' : '') . '.</p>';
			$confirmationHtml .= "\n" . '</div>';
			$html = $confirmationHtml . $html;
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to render a publication group as a simple bullet-point list without grouping
	private function publicationsListSimple ($publications, $label, $type)
	{
		# Start the HTML with label
		$html  = "\n<h3>{$label}</h3>";
		
		# Determine a namespace extension for the query selector references
		$namespace = '_' . str_replace ('-', '', $type);
		
		# Compile the list
		$oldYear = false;
		$hasRecent = false;
		$html .= "\n<ul id=\"publications{$namespace}\">";
		foreach ($publications as $publicationId => $publication) {
			
			# If enabled, for the first old year, open a div for Javascript filtering purposes
			if (in_array ($type, $this->expandableTypes)) {
				if (!$oldYear) {		// If not already found, check
					if ($publication['publicationYear'] <= $this->firstOldYear) {
						$oldYear = true;
					} else {
						$hasRecent = true;
					}
				}
			}
			
			# Add the publication
			$html .= "\n\t<li class=\"publication" . htmlspecialchars ($publicationId) . '-' . htmlspecialchars ($publication['sourceName']) . ($oldYear ? ' oldyear' : '') . '">' . $publication['html'] . '</li>';
		}
		$html .= "\n</ul>";
		
		# Add expandability at the end of the list
		if ($oldYear) {
			$html .= $this->showHideLinkUl ($namespace, $label, $hasRecent);
		}
		
		# If there are book covers show these, as a block at the end of the books
		$images = array ();
		foreach ($publications as $id => $publication) {
			if ($publication['thumbnailHtml']) {
				$images[$id] = $publication['thumbnailHtml'];
			}
		}
		if ($images) {
			$html .= "\n<p class=\"bookcovers\">" . implode (' ', $images) . "\n</p>";
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to render a publication group as a simple bullet-point list without grouping
	private function publicationsListByYear ($publications, $label, $type, $favourites, $showStars = true)
	{
		# End if none
		if (!$publications) {return false;}
		
		# Start the HTML
		$html  = "\n<h3>{$label}</h3>";
		
		# Add stars indication if required
		if ($showStars) {
			if ($favourites) {
				$html .= "<p class=\"small comment\"><em>Key publications are marked with a star.</em></p>";
			}
		}
		
		# Regroup the remaining items by year
		$publications = application::regroup ($publications, 'publicationYear', false);
		
		# Determine a namespace extension for the query selector references
		$namespace = '_' . str_replace ('-', '', $type);
		
		# Loop through each year
		$oldYearsOpened = false;
		$canSplitIfTotal = $this->settings['canSplitIfTotal'];
		foreach ($publications as $year => $publicationsThisYear) {
			
			# If enabled, for the first old year, open a div for Javascript filtering purposes
			if (in_array ($type, $this->expandableTypes)) {
				if (!$oldYearsOpened && ($year <= $this->firstOldYear) && $canSplitIfTotal <= 0) {
					$oldYearsOpened = true;
					
					# Add the div
					$html .= "\n\n" . '<div class="olderpublications" data-type="' . $type . '" data-label="' . htmlspecialchars ($label)	 . '">' . "\n";
				}
			}
			
			# Loop through the publications in the year and add it to the list
			$articles = array ();
			foreach ($publicationsThisYear as $publicationId => $publication) {
				$canSplitIfTotal--;
				$key = 'publication' . $publicationId . '-' . $publication['sourceName'];
				$articles[$key] = $publication['html'];
			}
			
			# Add the list for this year
			$html .= "\n<h4>" . ($year ? $year : '[Unknown year]') . '</h4>';
			$html .= application::htmlUl ($articles, 0, NULL, true, false, false, $liClass = true);
		}
		
		# Close the old years div if it was created
		if ($oldYearsOpened) {
			$html .= "\n\n</div><!-- /#olderpublications -->\n";
		}
		
		# Add JS for show/hide links if required
		if ($oldYearsOpened) {
			$html .= $this->showHideLinkDivJs ();
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Helper function to create a show/hidden link for a list
	private function showHideLinkUl ($namespace, $label, $hasRecent)
	{
		# Compile the expansion message
		$message = 'Show ' . ($hasRecent ? 'earlier ' : '') . lcfirst ($label);
		
		# Compile the HTML
		$selector = "#publications{$namespace} li.oldyear";
		$html  = "\n\n<!-- Show/hide link -->";
		$html .= "\n" . "<script>
			document.querySelectorAll ('{$selector}').forEach (function (element) {element.style.display = 'none';});
			var showButtonHtml = '<p class=\"showall\" id=\"showall" . $namespace . "\"><a href=\"#showall" . $namespace . "\">&#9660; " . $message . " &hellip;</a></p>';
			document.querySelector ('#publications" . $namespace . "').insertAdjacentHTML ('beforeend', showButtonHtml);
			document.querySelector ('#showall" . $namespace . " a').addEventListener ('click', function (e) {
				e.preventDefault ();
				document.querySelector ('#showall" . $namespace . "').style.display = 'none';
				document.querySelectorAll ('{$selector}').forEach (function (element) {element.style.display = 'list-item';});
			});
		</script>
		";
		
		# Return the HTML
		return $html;
	}
	
	
	# Helper function to create a show/hidden link for a div
	private function showHideLinkDivJs ()
	{
		# Compile the HTML
		$html .= "\n" . "<script>
			// Show/hide link for each set of expandable publications type
			document.querySelectorAll ('.olderpublications').forEach (function (olderPublicationsDiv) {
				olderPublicationsDiv.style.display = 'none';
				
				const type = olderPublicationsDiv.dataset.type;
				const label = olderPublicationsDiv.dataset.label;
				
				const showButtonHtml = '<p class=\"showall\" data-type=\"' + type + '\"><a href=\"#\">&#9660; Show earlier ' + label + ' &hellip;</a></p>';
				olderPublicationsDiv.insertAdjacentHTML ('beforebegin', showButtonHtml);
				
				document.querySelector ('.showall[data-type=\"' + type + '\"] a').addEventListener ('click', function (e) {
					e.preventDefault ();
					document.querySelector ('.showall[data-type=\"' + type + '\"]').style.display = 'none';
					olderPublicationsDiv.style.display = 'block';
				});
			});
		</script>
		";
		
		# Return the HTML
		return $html;
	}
	
	
	# Get the users
	# NB The getUsersFunction callback function must return a datastructure like that defined in the index.html.template
	private function getUsersUpstream ()
	{
		# Run callback function
		$function = $this->settings['getUsersFunction'];
		return $function ();
	}
	
	
	# Get the groups
	# NB The getGroupsFunction callback function must return a datastructure like that defined in the index.html.template
	private function getGroupsUpstream ()
	{
		# Run callback function
		$function = $this->settings['getGroupsFunction'];
		return $function ();
	}
	
	
	# Get the group members
	#!# This is not ideal, because in multisite mode, the callback has to do string matching on the groupUrl; ideally supply the users up-front within getGroupsUpstream
	# NB The getGroupMembers callback function must return a datastructure like that defined in the index.html.template
	private function getGroupMembersUpstream ($groupUrl)
	{
		# Run callback function
		$function = $this->settings['getGroupMembers'];
		return $function ($groupUrl);
	}
	
	
	# Function to get data from the Symplectic API
	private function getData ($call, $format = 'xpathDom', $isFullUrl = false, /* already retrieved */ $data = false)
	{
		# If data is already received use that, else retrieve the data
		if (!$data) {
			
			# Assemble the URL
			$url = ($isFullUrl ? '' : $this->settings['apiUrl']) . $call;
			
			# Retrieve the data from the URL, reporting and stopping if a fatal error (inability to retrieve the URL at all) occurs
			if (!$data = $this->urlCall ($url, $errorHtml, $isFatalError)) {
				if ($isFatalError) {
					$html = $errorHtml;
					echo $html;
					die;
				}
			}
			
			# Delay to prevent API overload
			usleep (500000);	// 0.5 seconds is requested in documentation (page 16, "500ms")
		}
		
		# Debug if required
		// application::dumpData (xml::xml2arrayWithNamespaces ($data));
		// echo $data; die;
		
		# Take no action if no data, e.g. the user has no publications
		if (!$data) {return false;}
		
		# Convert the XML to an array, maintaining namespaced objects
		if ($format == 'json' || $format == 'data') {
			$data = xml::xml2arrayWithNamespaces ($data);
		}
		
		# Return an XPath DOM object if required; see: https://stackoverflow.com/a/20318801 and a good explanation of the default namespace at https://web.archive.org/web/20090414184326/http://people.ischool.berkeley.edu/~felix/xml/php-and-xmlns.html
		if ($format == 'xpathDom') {
			$dom = new DOMDocument ();
			$dom->loadXml ($data);
			$xpathDom = new DOMXpath ($dom);
			$xpathDom->registerNamespace ('default', 'http://www.w3.org/2005/Atom');
			$xpathDom->registerNamespace ('api', 'http://www.symplectic.co.uk/publications/api');
			return $xpathDom;
		}
		
		# Convert the array to JSON
		if ($format == 'json') {
			header ('Content-Type: application/json');
			$data = json_encode ($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		}
		
		# Send XML header if required
		if ($format == 'xml') {
			header ('Content-Type: application/xml; charset=utf-8');
		}
		
		# Return the data
		return $data;
	}
	
	
	# Function to retrieve data from the URL
	private function urlCall ($url, &$errorHtml = false, &$isFatalError = false)
	{
		# Inject the API credentials into the request URL if required
		if ($this->settings['apiUsername'] && $this->settings['apiPassword']) {
			$url = preg_replace ('|^(https?://)(.+)$|', "$1{$this->settings['apiUsername']}:{$this->settings['apiPassword']}@$2", $url);
		}
		
		# Attempt to retrieve the data
		$data = @file_get_contents ($url);
		
		# If no data, check if the result was a 404, by checking the auto-created variable $http_response_header
		if (!$data) {
			
			# End if no response at all
			if (!isSet ($http_response_header) || empty ($http_response_header)) {		// It appears that when a failure happens, the magic variable $http_response_header is in fact created but not populated; https://php.net/reserved.variables.httpresponseheader doesn't seem to document this.
				$errorHtml = "\n<p class=\"warning\">No response was received for <em>{$url}</em>.</p>";
				$isFatalError = true;
				return false;
			}
			
			# Find the header which contains the HTTP response code (seemingly usually the first)
			foreach ($http_response_header as $header) {
				if (preg_match ('|^HTTP/1|i', $header)) {
					break;	// The correct header has been found
				}
			}
			
			# If the response was anything other than 404, report the error
			if (!substr_count ($header, ' 404 ')) {
				$errorHtml = "\n<p class=\"warning\">An empty response was received for <em>{$url}</em>, with header response: <em>{$header}</em>.</p>";
				$isFatalError = true;
				// application::dumpData ($http_response_header);
				return false;
			}
			
			# Non-fatal error, e.g. person simply not present
			$errorHtml = "\n<p class=\"warning\">No publications found for URL: <tt>" . htmlspecialchars ($url) . '</tt>.</p>';
			
			# Signal no data
			return false;
		}
		
		# Return the data
		return $data;
	}
	
	
	# Function to enable XPath querying of the data
	private function XPath ($xpathDom, $xpath, $contextnode = NULL)
	{
		# Evaluate the XPath and return it as a string
		$string = $xpathDom->evaluate ('string(' . $xpath . ')', $contextnode);
		
		# Return the string
		return $string;
	}
	
	
	# Get data for a user
	private function getUser ($username)
	{
		# Obtain the data
		$call = '/users/username-' . $username . '?detail=full';
		if (!$xpathDom = $this->getData ($call)) {return false;}
		
		# Assemble the data
		$data = array (
			'id'			=> $this->XPath ($xpathDom, '//default:feed/default:entry/api:object/@id'),
			'username'		=> $username,
			'is-academic'	=> $this->XPath ($xpathDom, '//default:feed/default:entry/api:object/api:is-academic'),
			'title'			=> $this->XPath ($xpathDom, '//default:feed/default:entry/api:object/api:title'),
			'surname'		=> $this->XPath ($xpathDom, '//default:feed/default:entry/api:object/api:last-name'),
			'initials'		=> $this->XPath ($xpathDom, '//default:feed/default:entry/api:object/api:initials'),
			'forename'		=> $this->XPath ($xpathDom, '//default:feed/default:entry/api:object/api:first-name'),
			'email'			=> $this->XPath ($xpathDom, '//default:feed/default:entry/api:object/api:email-address'),
		);
		
		# Add the display name as it appears in publications
		$data['displayName'] = $this->formatContributor ($data['surname'], $data['initials']);
		
		# Return the user ID
		return $data;
	}
	
	
	# Get the publications for a user
	private function retrievePublicationsOfUser ($username, $sources, &$errorHtml, &$isFatalError = false)
	{
		# Define the starting point for the call
		$call = '/users/username-' . $username . '/publications?detail=full';
		$resultsUrlPage = $this->settings['apiUrl'] . $call;	// A full URL is defined here initially, because the iteration below determines the next request, which is a full URL
		
		# Get the user's details, or skip if they do not exist
		if (!$user = $this->getUser ($username)) {return false;}
		
		# Start an array of all publication data to return
		$publications = array ();
		
		# Loop through each page of results
		while ($resultsUrlPage) {
			
			# Obtain the data or continue to next
			if (!$xpathDom = $this->getData ($resultsUrlPage, 'xpathDom', true)) {continue;}
			
			# Extract the user's name
			$personName = $this->XPath ($xpathDom, '/default:feed/default:title');
			$personName = $this->extractPersonName ($personName);
			
			# Loop through each entry in the data; see: https://stackoverflow.com/questions/11886176/ and https://stackoverflow.com/questions/5929263/
			$publicationsNode = $xpathDom->query ('/default:feed/default:entry');
			foreach ($publicationsNode as $index => $publicationNode) {
				
				# Parse the publication, or skip if not visible (or other problem)
				if (!$publication = $this->parsePublication ($publicationNode, $xpathDom, $sources, $user, $username, $id /* returned by reference */, $isFatalError /* returned by reference */, $errorHtml /* returned by reference */)) {continue;}
				
				# Add this publication
				$publications[$id] = $publication;
			}
			
			# Determine the next page, if any
			$resultsUrlPage = $this->XPath ($xpathDom, "/default:feed/api:pagination/api:page[@position='next']/@href");
		}
		
		# Return the array of publications
		return $publications;
	}
	
	
	# Function to parse a publication's data
	private function parsePublication ($publicationNode, $xpathDom, $sources, $user, $username, &$id = false, &$isFatalError, &$errorHtml)
	{
		# Ensure the publication is set to be visible
		$isVisible = ($this->XPath ($xpathDom, './api:relationship/api:is-visible', $publicationNode) == 'true');
		if (!$isVisible) {return false;}
		
		# Get values which will be reused more than once in code below
		$id = $this->XPath ($xpathDom, './api:relationship/api:related/api:object/@id', $publicationNode);
		$type = $this->XPath ($xpathDom, './api:relationship/api:related/api:object/@type', $publicationNode);
		
		# Select the record source to use, either the record explicitly marked as is-preferred-record="true", or the next best
		$sourceId = $this->selectRecordSource ($xpathDom, $publicationNode, $sources);
		
		# Obtain the source display name for error-reporting purposes
		$sourceDisplayName = $this->XPath ($xpathDom, './api:relationship/api:related/api:object/api:records/api:record[@source-id="' . $sourceId . '"]/@source-display-name', $publicationNode);
		
		# Zoom in on this record source node
		$sourceNode = $xpathDom->query ('(./api:relationship/api:related/api:object/api:records/api:record[@source-id="' . $sourceId . '"])[1]/api:native', $publicationNode)->item(0);
		
		# Define alternative date fields for particular types of publications, for checking if the standard publication date is not available
		$alternativeDateFields = array (
			'journal-article'		=> 'online-publication-date',	// Online publication date
			'conference'			=> 'start-date',				// "Conference start date"
			'patent'				=> 'start-date',				// "Awarded date"
			'thesis-dissertation'	=> 'filed-date',				// "Date submitted"
			'performance'			=> 'start-date',				// "Start date"
		);
		
		# Check alternative date fields, but prefer the default if it exists, as that relates to actual publication date (rather than e.g. date of a conference)
		$datesField = 'publication-date';	// Default
		if (isSet ($alternativeDateFields[$type])) {
			if (!$this->XPath ($xpathDom, './api:field[@name="' . $datesField . '"]/api:date/api:year', $sourceNode)) {
				if ($this->XPath ($xpathDom, './api:field[@name="' . $alternativeDateFields[$type] . '"]/api:date/api:year', $sourceNode)) {
					$datesField = $alternativeDateFields[$type];
				}
			}
		}
		
		# If there is no date present, look for an acceptance date
		$isAcceptanceDate = false;
		if (!$this->XPath ($xpathDom, './api:field[@name="' . $datesField . '"]/api:date/api:year', $sourceNode)) {
			$datesField = 'acceptance-date';
			$isAcceptanceDate = true;
		}
		
		# Add key details
		$publication = array (
			'id'					=> $id,
			'sourceName'			=> $this->XPath ($xpathDom, './api:relationship/api:related/api:object/api:records/api:record[@source-id="' . $sourceId . '"]/@source-name', $publicationNode),
			'type'					=> $type,
			'lastModifiedWhen'		=> strtotime ($this->XPath ($xpathDom, './api:relationship/api:related/api:object/@last-modified-when', $publicationNode)),
			'doi'					=> $this->XPath ($xpathDom, './api:field[@name="doi"]/api:text', $sourceNode),
			'title'					=> str_replace (array ("\n", ' '), ' ', $this->XPath ($xpathDom, './api:field[@name="title"]/api:text', $sourceNode)),
			'journal'				=> $this->XPath ($xpathDom, './api:field[@name="journal"]/api:text', $sourceNode),
			'publicationYear'		=> $this->XPath ($xpathDom, './api:field[@name="' . $datesField . '"]/api:date/api:year', $sourceNode),
			'publicationMonth'		=> $this->XPath ($xpathDom, './api:field[@name="' . $datesField . '"]/api:date/api:month', $sourceNode),
			'publicationDay'		=> $this->XPath ($xpathDom, './api:field[@name="' . $datesField . '"]/api:date/api:day', $sourceNode),
			'dateIsAcceptance'		=> ($isAcceptanceDate ? 1 : NULL),
			'volume'				=> $this->XPath ($xpathDom, './api:field[@name="volume"]/api:text', $sourceNode),
			'issue'					=> $this->XPath ($xpathDom, './api:field[@name="issue"]/api:text', $sourceNode),
			'pagination'			=> $this->formatPagination (
				$this->XPath ($xpathDom, './api:field[@name="pagination"]/api:pagination/api:begin-page', $sourceNode),
				$this->XPath ($xpathDom, './api:field[@name="pagination"]/api:pagination/api:end-page', $sourceNode),
				$this->XPath ($xpathDom, './api:field[@name="pagination"]/api:pagination/api:page-count', $sourceNode),
				$type
			),
			'publisher'				=> $this->XPath ($xpathDom, './api:field[@name="publisher"]/api:text', $sourceNode),
			'place'					=> $this->XPath ($xpathDom, './api:field[@name="place-of-publication"]/api:text', $sourceNode),
			'parentTitle'			=> $this->XPath ($xpathDom, './api:field[@name="parent-title"]/api:text', $sourceNode),
			'edition'				=> $this->XPath ($xpathDom, './api:field[@name="edition"]/api:text', $sourceNode),
			'number'				=> $this->XPath ($xpathDom, './api:field[@name="number"]/api:text', $sourceNode),
			'url'					=> $this->XPath ($xpathDom, './api:field[@name="publisher-url"]/api:text', $sourceNode),
			'isFavourite'			=> ($this->XPath ($xpathDom, './api:relationship/api:is-favourite', $publicationNode) == 'false' ? NULL : 1),
		);
		
		# Detect no title, as this indicates an upstream data issue
		if (!strlen ($publication['title'])) {
			$errorHtml .= "\n<p class=\"warning\">There is no title for <a href=\"{$this->settings['website']}viewobject.html?cid=1&amp;id={$id}\" target=\"_blank\">publication #{$id}</a> ({$sourceDisplayName}), representing a data error.</p>";
			// $isFatalError = true;
			return false;
		}
		
		# If relationships are enabled, for books, look for additional editors, which are in the api:relationships field
		$additionalEditor = false;
		if ($this->settings['enableRelationships']) {
			if ($type == 'book') {
				$relationshipsUrl = $this->XPath ($xpathDom, './api:relationship/api:related/api:object/api:relationships/@href', $publicationNode);
				if ($relationshipsUrl) {
					if ($xpathDomRelationships = $this->getData ($relationshipsUrl, 'xpathDom', true)) {
						$usernameEditor = $this->XPath ($xpathDomRelationships, '/default:feed/default:entry/api:relationship[@type-id="9"]/api:related[@direction="to"]/api:object[@category="user"]/@username');	// "Relationship type 9 means "Edited by" in this context."
						if (mb_strtolower ($usernameEditor) == $user['username']) {
							$additionalEditor = $user['displayName'];
						}
					}
				}
			}
		}
		
		# Get the authors
		$authorsNode = $xpathDom->query ('./api:field[@name="authors"]/api:people/api:person', $sourceNode);
		list ($publication['authors'], $publication['nameAppearsAsAuthor']) = $this->processContributors ($authorsNode, $xpathDom, $user, $publication['id'], 'author', $sourceDisplayName, NULL, $errorHtml);
		
		# Get the editors
		$editorsNode = $xpathDom->query ('./api:field[@name="editors"]/api:people/api:person', $sourceNode);
		list ($publication['editors'], $publication['nameAppearsAsEditor']) = $this->processContributors ($editorsNode, $xpathDom, $user, $publication['id'], 'editor', $sourceDisplayName, $additionalEditor, $errorHtml);
		
		# Create a compiled HTML version; highlighting is not applied at this stage, as that has to be done at listing runtime depending on the listing context (person/group/all)
		$publication['html'] = $this->compilePublicationHtml ($publication, $errorHtml);
		
		# Return the publication data
		return $publication;
	}
	
	
	# Helper function to select the record source
	private function selectRecordSource ($xpathDom, $publicationNode, $sources)
	{
		# Look for is-preferred-record="true", which is explicitly marked by the user as the preferred record
		if ($sourceId = $this->XPath ($xpathDom, './api:relationship/api:related/api:object/api:records/api:record[@is-preferred-record="true"]/@source-id', $publicationNode)) {
			return $sourceId;
		}
		
		# Work through the sources in precedence order, and stop when/if found; the first is used if more than one
		foreach ($sources as $precedence => $source) {
			if ($sourceId = $this->XPath ($xpathDom, '(./api:relationship/api:related/api:object/api:records/api:record[@source-id="' . $source['id'] . '"])[1]/@source-id', $publicationNode)) {
				return $sourceId;
			}
		}
		
		# Otherwise (which should never happen), fall back to the first record
		$sourceId = $this->XPath ($xpathDom, './api:relationship/api:related/api:object/api:records/api:record[1]/@source-id', $publicationNode);
		return $sourceId;
	}
	
	
	# Helper function to process contributors (authors/editors)
	private function processContributors ($contributorsNode, $xpathDom, $user, $publicationId, $type, $sourceDisplayName, $additionalPerson = false, &$errorHtml)
	{
		# Start a list of contributors and how their name appears
		$contributors = array ();
		$nameAppearsAs = array ();
		
		# Add in additional person if specified, as the first
		if ($additionalPerson) {
			$contributors[] = $additionalPerson;
			$nameAppearsAs[] = $additionalPerson;
		}
		
		# Process the contributors
		foreach ($contributorsNode as $index => $contributorNode) {
			$surname	= $this->XPath ($xpathDom, './api:last-name', $contributorNode);
			$initials	= $this->XPath ($xpathDom, './api:initials', $contributorNode);
			$contributor = $this->formatContributor ($surname, $initials);
			$contributors[] = $contributor;
			
			# If this contributor's name appears to match, register this as a possible name match; it is unfortunate that the API seems to provide no proper match indication
			if ($this->isContributorNameMatch ($surname, $initials, $user)) {
				$nameAppearsAs[] = $contributor;
			}
		}
		
		# Unique the lists
		$contributors = array_unique ($contributors);
		$nameAppearsAs = array_unique ($nameAppearsAs);
		
		# Compile as a string
		$contributorsString = implode ('|', $contributors);
		
		# Register what the name is formatted as, reporting any errors detected
		if (!$nameAppearsAs) {
			$errorHtml .= "\n<p class=\"warning\">The {$type}s list for <a href=\"{$this->settings['website']}viewobject.html?cid=1&amp;id={$publicationId}\" target=\"_blank\">publication #{$publicationId}</a> ({$sourceDisplayName}) does not appear to contain a match for <em>{$user['displayName']}</em> even though that publication is registered to that user; the {$type}s found were: <em>" . implode ('</em>, <em>', $contributors) . "</em>.</p>";
			$nameAppearsAs = array ();
		}
		if (count ($nameAppearsAs) > 1) {
			$errorHtml .= "\n<p class=\"warning\">A single unique {$type} match for <a href=\"{$this->settings['website']}viewobject.html?cid=1&amp;id={$publicationId}\" target=\"_blank\">publication #{$publicationId}</a> ({$sourceDisplayName}) could not be made against <em>{$user['displayName']}</em>; the matches were: <em>" . implode ('</em>, <em>', $nameAppearsAs) . "</em>.</p>";
			$nameAppearsAs = array ();
		}
		$nameAppearsAsString = ($nameAppearsAs ? $nameAppearsAs[0] : NULL);	// Convert the single item to a string, or the empty array to a database NULL
		
		# Return the pair
		return array ($contributorsString, $nameAppearsAsString);
	}
	
	
	# Helper function to match a contributor's name; this attempts to deal with the situation where two names are similar, e.g. the current user is "J. Smith" but the publication has "J. Smith" and "A. Smith" and "A.J. Smith"; this routine would match only on "J. Smith"
	private function isContributorNameMatch ($surname, $initials, $user)
	{
		# Normalise the surname components for comparison purposes
		$surname = $this->normaliseSurname ($surname);
		$user['surname'] = $this->normaliseSurname ($user['surname']);
		
		# End if the surname does match
		if ($surname != $user['surname']) {
			return false;
		}
		
		# Normalise the initials components for comparison purposes
		$initials = $this->normaliseInitials ($initials);
		$user['initials'] = $this->normaliseInitials ($user['initials'], $user['forename']);
		
		# Ensure the arrays are the same length, i.e. so that "A.B. Smith" is compared against "A. Smith" by the initials "A.B." being trimmed to "A."
		$subjectLength = count ($initials);
		$comparatorLength = count ($user['initials']);
		if ($comparatorLength > $subjectLength) {
			$user['initials'] = array_slice ($user['initials'], 0, $subjectLength);
		}
		if ($subjectLength > $comparatorLength) {
			$initials = array_slice ($initials, 0, $comparatorLength);
		}
		
		# Return whether the two arrays are exactly equal
		return ($initials === $user['initials']);
	}
	
	
	# Helper function to normalise names for comparison purposes
	private function normaliseSurname ($surname)
	{
		# Lower-case
		$surname = mb_strtolower ($surname);
		
		# Trim
		$surname = trim ($surname);
		
		# Return the result
		return $surname;
	}
	
	
	# Helper function to normalise initials lists for comparison purposes, e.g. "A.B.C." "AB.C." "ABC" "A B C1", or no initials but forename "Anthony Ben Calix", each become array('A','B','C')
	private function normaliseInitials ($initials, $forename = false)
	{
		# Trim and lower-case, and remove non-alphanumeric characters
		$initials = preg_replace ('/[^a-z]/', '', trim (mb_strtolower ($initials)));
		
		# If no initials, use the forname(s), if any
		if ($forename) {
			if (!strlen ($initials)) {
				$forenames = preg_split ('/\s+/', mb_strtolower ($forename));
				foreach ($forenames as $forename) {
					$initials .= substr ($forename, 0, 1);
				}
			}
		}
		
		# End if none
		if (empty ($initials)) {
			return array ();
		}
		
		# Explode the initials into an array
		$initials = mb_str_split ($initials);
		
		# Return the list
		return $initials;
	}
	
	
	# Helper function to create a compiled HTML version of a publication
	private function compilePublicationHtml ($publication, &$errorHtml = '')
	{
		# Convert each element to entities
		foreach ($publication as $key => $value) {
			$publication[$key] = htmlspecialchars ($value);
			
			# If the conversion failed, report
			if (strlen ($value) && !strlen ($publication[$key])) {
				$errorHtml .= "\n<p class=\"warning\">Invalid character(s) were found in <a href=\"{$this->settings['website']}viewobject.html?cid=1&amp;id={$publication['id']}\" target=\"_blank\">publication #{$publication['id']}</a> in the {$key} field; input text: {$value} .</p>";
			}
		}
		
		# Unpack the contributor listings into "A, B and C" format; the same routine is also used at runtime for higlighting
		$authors = application::commaAndListing (explode ('|', $publication['authors']), $this->commaAndStripStartingWords);
		$editors = application::commaAndListing (explode ('|', $publication['editors']), $this->commaAndStripStartingWords);
		
		# Compile the HTML for this publication
		$html  = '';
		if (($publication['type'] == 'book') && strlen ($publication['editors'])) {
			$html .= $editors . ' (' . (substr_count ($publication['editors'], '|') ? 'eds' : 'ed') . '.)';
		} else {
			$html .= $authors;
		}
		$html .= ($publication['publicationYear'] ? ', ' . ($publication['dateIsAcceptance'] ? 'accepted ' : '') . $publication['publicationYear'] : '') . '. ';
		if (strlen ($publication['url'])) {
			$html .= '<a href="' . $publication['url'] . '" target="_blank">';
		}
		if (($publication['type'] == 'book') || ($publication['type'] == 'internet-publication')) {
			$html .= '<em>';
		}
		$html .= $publication['title'];
		if (($publication['type'] == 'book') || ($publication['type'] == 'internet-publication')) {
			$html .= '</em>';
		}
		if (strlen ($publication['url'])) {
			$html .= '</a>';
			if (preg_match ('/\.mp3$/', $publication['url'])) {
				$html .= ' [MP3 file]';
			}
		}
		if ($publication['type'] == 'chapter' && strlen ($publication['parentTitle'])) {
			$html .= ', in';
			if (strlen ($publication['editors'])) {$html .= ' ' . $editors . ' (' . (substr_count ($publication['editors'], '|') ? 'eds' : 'ed') . '.)';}
			if (strlen ($publication['parentTitle'])) {$html .= " <em>{$publication['parentTitle']}</em>";}
		}
		if (($publication['type'] == 'book') || ($publication['type'] == 'chapter')) {
			if (strlen ($publication['edition'])) {$html .= ", {$publication['edition']} edition";}
		}
		if (($publication['type'] == 'book') || ($publication['type'] == 'chapter') || ($publication['type'] == 'internet-publication')) {
			if (strlen ($publication['publisher'])) {
				$html .= ", {$publication['publisher']}";
				if (strlen ($publication['place'])) {$html .= ", {$publication['place']}";}
			}
		}
		if ($publication['type'] == 'internet-publication') {
			if ($publication['publicationYear']) {
				$html .= ' (' . $this->formatDate ($publication) . ')';
			}
		}
		if (substr ($html, -1) != '.') {		// Do not add . after if already present
			$html .= '.';
		}
		$html .= (strlen ($publication['journal']) ? " <em>{$publication['journal']}</em>," : '');
		$html .= (strlen ($publication['volume']) ? " vol. {$publication['volume']}," : '');
		$html .= (strlen ($publication['issue']) ? " issue {$publication['issue']}," : '');
		$html .= (strlen ($publication['number']) ? " art. {$publication['number']}," : '');
		$html .= (strlen ($publication['pagination']) ? " {$publication['pagination']}." : '');
		$html .= (strlen ($publication['doi']) ? " <a href=\"https://doi.org/{$publication['doi']}\" title=\"Link to publication\" target=\"_blank\">doi:{$publication['doi']}</a>" : '');
		
		# Ensure ends with a dot
		if (substr ($html, -1) == ',') {$html = substr ($html, 0, -1);}
		if (substr ($html, -1) != '.') {$html .= '.';}
		
		# Return the HTML
		return $html;
	}
	
	
	# Page to retrieve raw data from the Symplectic API
	public function retrieve ()
	{
		# Start the HTML
		$html = '';
		
		# Include examples
		$html .= "
			<p>Examples:</p>
			<ul>
				<li>/users/username-<span class=\"comment\">&lt;crsid&gt;</span>?detail=full</li>
				<li>/users/username-<span class=\"comment\">&lt;crsid&gt;</span>/publications?detail=full</li>
				<li>/users/username-<span class=\"comment\">&lt;crsid&gt;</span>/publications?detail=full&amp;modified-since=...&amp;after-id=publicationid (see position=\"next\" in results, or click link)</li>
				<li>/publications/<span class=\"comment\">&lt;id&gt;</span></li>
				<li>/publications/<span class=\"comment\">&lt;id&gt;</span>/relationships</li>
				<li>/publication/sources</li>
			</ul>
		";
		
		# Show the upload form
		$form = new form (array (
			'formCompleteText' => false,
			'div' => 'graybox ultimateform',
			'display' => 'paragraphs',
			'reappear' => true,
			'get' => true,
			'name' => false,
		));
		$form->input (array (
			'name'			=> 'url',
			'title'			=> 'URL',
			'required'		=> true,
			'autofocus'		=> true,
			'prepend'		=> $this->settings['apiUrl'] . ' ',
			'size'			=> 50,
		));
		if (!$result = $form->process ($html)) {
			echo $html;
			return false;
		}
		
		# Assemble the URL
		$url = $this->settings['apiUrl'] . $result['url'];
		
		# Retrieve the data from the URL
		if (!$data = $this->urlCall ($url, $errorHtml)) {
			$html .= $errorHtml;
			echo $html;
			return false;
		}
		
		# For paginated results, create a link for the next page
		$xpathDom = $this->getData (false, 'xpathDom', false, $data);
		if ($next = $this->XPath ($xpathDom, "/default:feed/api:pagination/api:page[@position='next']/@href")) {
			$url = $this->baseUrl . '/' . $this->actions[$this->action]['url'] . '?url=' . urlencode (str_replace ($this->settings['apiUrl'], '', $next));
			$html .= "\n<p class=\"alignright\"><a href=\"{$url}\">Next &raquo;</a></p>";
		}
			
		# Show the result
		$html .= xml::formatter ($data);
		
		# Show the HTML
		echo $html;
	}
	
	
	# Helper function to format a date
	private function formatDate ($publication)
	{
		# Add day, month and year, if they exist
		if ($publication['publicationDay']) {
			$dates[] = application::ordinalSuffix ($publication['publicationDay']);
		}
		if ($publication['publicationMonth']) {
			$months = array (1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
			$month = $months[(int) $publication['publicationMonth']];
			$dates[] = $month;
		}
		if ($publication['publicationYear']) {
			$dates[] = $publication['publicationYear'];
		}
		
		# Compile the HTML
		$html = implode (' ', $dates);
		
		# Return the HTML
		return $html;
	}
	
	
	# Helper function to extract the person's name
	private function extractPersonName ($personName)
	{
		# Remove prefix text
		if (preg_match ('/^Publications related to the user: (.+)$/', $personName, $matches)) {
			$personName = $matches[1];
		}
		
		# Return string
		return $personName;
	}
	
	
	# Helper function to format pagination
	private function formatPagination ($begin, $end, $count, $type)
	{
		# For a book, if a total count is provided, show only that
		if ($type == 'book') {
			if ($count) {
				return $count . 'pp';
			}
		}
		
		# End if none
		if (!$begin) {return '';}
		
		# Compile the range string
		return 'p.' . implode ('-', array ($begin, $end));
	}
	
	
	# Helper function to format an author
	private function formatContributor ($surname, $initials)
	{
		# Add dots after each initials
		$initials = implode ('.', mb_str_split ($initials)) . '.';
		
		# Return the string
		return $surname . ', ' . $initials;
	}
	
	
	
	# Get details of publication
	private function getPublication ($publicationId)
	{
		$call = '/publications/' . $publicationId;
		if (!$data = $this->getData ($call)) {return false;}
		
		# Return the data
		return $data;
	}
}

?>

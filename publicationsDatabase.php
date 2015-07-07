<?php

# Class to create a publications database, implementing the Symplectic API
# Version 1.0.0

# Licence: GPL
# (c) Martin Lucas-Smith, University of Cambridge
# More info: https://github.com/camunigeog/publications-database


#!# Consider adding support for direct upload of MP3 files, etc., in the same way as book covers


require_once ('frontControllerApplication.php');
class publicationsDatabase extends frontControllerApplication
{
	# Function to assign defaults additional to the general application defaults
	public function defaults ()
	{
		# Specify available arguments as defaults or as NULL (to represent a required argument)
		$defaults = array (
			'div' => strtolower (__CLASS__),
			'database' => 'publications',
			'table' => 'publications',
			'website' => NULL,
			'apiHttp' => NULL,
			'apiHttps' => NULL,
			'administrators' => 'administrators',
			'yearsConsideredRecent' => 5,
			'yearsConsideredRecentMainListing' => 2,
			'canSplitIfTotal' => 10,
			'getUsersFunction' => NULL,
			'getGroupsFunction' => NULL,
			'getGroupMembers' => NULL,
			'cronUsername' => NULL,
			'corsDomains' => array (),
			'bookcoversLocation' => 'bookcovers/',		// From baseUrl, or if starting with a slash, from DOCUMENT_ROOT
			'bookcoversFormat' => 'png',
			'bookcoversHeight' => 250,
		);
		
		# Return the defaults
		return $defaults;
	}
	
	
	
	# Define the supported types and their labels
	private $types = array (
		'book'					=> 'Books',
		'journal-article'		=> 'Journal articles',
		'chapter'				=> 'Book chapters',
		'conference'			=> 'Conference proceedings',
		'internet-publication'	=> 'Internet publications',
		
	//	'?'						=> 'Datasets',
	//	'patent'				=> 'Patents',
	//	'report'				=> 'Reports',
	//	'software'				=> 'Software',
	//	'?'						=> 'Performances',
	//	'?'						=> 'Compositions',
	//	'?'						=> 'Designs',
	//	'?'						=> 'Artefacts',
	//	'?'						=> 'Exhibitions',
	//	'other'					=> 'Other',
	//	'?'						=> 'Scholarly editions',
	//	'?'						=> 'Posters',
	//	'thesis-dissertation'	=> 'Theses / Dissertations',
	//	'?'						=> 'Working papers',
	);
	
	# Define the types that should use listing by year
	private $typesListingByYear = array (
		'journal-article',
	);
	
	# Define the types that should use the expandability system
	private $expandableTypes = array (
		'chapter',
		'journal-article',
	);
	
	# General class properties
	private $jQueryEnabled = false;
	
	
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
				'icon' => 'application_view_columns',
				'tab' => 'Statistics',
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
				'export' => true,
			),
			'data' => array (	// Used for e.g. AJAX calls, etc.
				'description' => 'Data point',
				'url' => 'data.json',
				'export' => true,
			),
		);
		
		# Return the actions
		return $actions;
	}
	
	
	# Database structure definition
	public function _databaseStructure ()
	{
		return "
			CREATE TABLE `administrators` (
			  `crsid` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
			  `active` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Y',
			  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  PRIMARY KEY (`crsid`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Administrators';
			
			CREATE TABLE `instances` (
			`id` int(11) NOT NULL COMMENT 'Automatic key',
			  `username` varchar(10) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Username',
			  `publicationId` int(11) NOT NULL COMMENT 'Publication ID',
			  `nameAppearsAsAuthor` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'The string appearing in the data for the name of the author',
			  `nameAppearsAsEditor` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'The string appearing in the data for the name of the editor',
			  `isFavourite` int(1) DEFAULT NULL COMMENT 'Favourite publication',
			  `savedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Automatic timestamp',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table of publications for each user';
			
			CREATE TABLE `publications` (
			  `id` int(11) NOT NULL COMMENT 'ID in original datasource',
			  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Type',
			  `lastModifiedWhen` int(11) NOT NULL COMMENT 'Last modified when (Unixtime)',
			  `doi` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'DOI',
			  `title` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Title',
			  `journal` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Journal',
			  `publicationYear` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Publication year',
			  `publicationMonth` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Publication month',
			  `publicationDay` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Publication day',
			  `volume` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Volume',
			  `pagination` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Pagination',
			  `publisher` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Publisher',
			  `edition` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Publisher',
			  `editors` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Editors',
			  `parentTitle` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Parent title',
			  `number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Number',
			  `authors` text COLLATE utf8_unicode_ci COMMENT 'Authors',
			  `url` VARCHAR(255) COLLATE utf8_unicode_ci NULL COMMENT 'URL';
			  `html` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Compiled HTML representation of record',
			  `savedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Automatic timestamp',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Publications';
			
			CREATE TABLE `users` (
			  `id` varchar(10) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Username',
			  `forename` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Forename',
			  `surname` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Surname',
			  `savedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Automatic timestamp',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table of data of users who have publications';
			
			CREATE TABLE `instances_import` LIKE `instances`;
			CREATE TABLE `publications_import` LIKE `publications`;
			CREATE TABLE `users_import` LIKE `users`;
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
		$html .= "\n<h3>Statistics</h3>";
		$data = $this->getStatistics ();
		$html .= application::htmlTableKeyed ($data, array (), false, 'lines compressed');
		
		# Show the HTML
		echo $html;
	}
	
	
	# Page to upload a book cover
	public function bookcover ()
	{
		# Start the HTML
		$html = '';
		
		# Ensure the person is present, or end
		if (!$user = $this->userHasPublications ($this->user)) {
			$html .= "\n<p>You do not appear to have any books in the Symplectic system.</p>";
			echo $html;
			return true;
		}
		
		# Get the publications for that user
		if (!$data = $this->getPerson ($this->user, 'book')) {
			$html .= "\n<p>You do not appear to have any books in the Symplectic system. However, if you have added a book to Symplectic just now, please check back here in a few hours, as there is a slight delay for this website to pick up new publications from Symplectic.</p>";
			echo $html;
			return true;
		}
		
		# Arrange as key => title
		$books = array ();
		foreach ($data as $id => $book) {
			$books[$id] = $book['title'];
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
		require_once ('image.php');
		image::resize ($uploadedFile, $outputFormat = 'jpg', $newWidth = '', $this->settings['bookcoversHeight'], $thumbnailFile, false);
		
		# Confirm success
		$html  = "\n<p>{$this->tick} The book cover has been successfully uploaded.</p>";
		$html .= "\n<p>Please navigate to your public page on the website to see it.</p>";
		
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
			$list[$username] = "<a href=\"{$this->baseUrl}/people/{$username}/\">{$nameHtml} ({$user['total']})" . ($user['favourites'] ? " ({$user['favourites']}<img src=\"/images/icons/star.png\" class=\"icon favourite\" />)" : '') . '</a>';
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
		
		# Get the publications for that user
		$publications = $this->getPerson ($username);
		
		# Render as a list
		$html = $this->publicationsList ($publications);
		
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
	public static function comparison ($baseUrl, $username, $administrators, $websiteUrl, $goLiveDate)
	{
		# End if no a logged-in user
		if (!$_SERVER['REMOTE_USER']) {return false;}
		
		# Ensure the page has a publications div
		if (!$contents = file_get_contents ($_SERVER['SCRIPT_FILENAME'])) {return false;}
		if (!substr_count ($contents, '<h2 id="publications">')) {return false;}
		
		# Determine if the user is an administrator
		$currentUser = $_SERVER['REMOTE_USER'];
		$userIsAdministrator = in_array ($currentUser, $administrators);
		
		# End if not the current user or an administrator
		if (($currentUser != $username) && !$userIsAdministrator) {
			return false;
		}
		
		# Define the HTML
		$html = <<< EOT
			
			<style type="text/css">
				#symplecticswitch {margin-bottom: 20px;}
				#symplecticswitch p {float: right; border: 1px solid #603; background-color: #f7f7f7; padding: 5px;}
				#symplecticpublications {border-top: 1px dashed #ccc; border-bottom: 1px dashed #ccc; padding: 5px 0; background-color: #f7f7f7;}
				#symplecticpublications img.bookcover {min-width: 170px; margin: 5px 10px 12px 0; box-shadow: 5px 5px 10px 0 #aaa;}
			</style>
			<script type="text/javascript" src="/javascripts/libs/jquery-min.js"></script>
			<script type="text/javascript">
				$(function(){
					
					// Define username
					var username = '{$username}';
					
					// Add checkbox
					$('#publications').before('<div id="symplecticswitch" />');
					
					// Attempt to get the HTML (will be run asyncronously) from the API for this user, or return 404
					$.get('{$baseUrl}/people/' + username + '/html', function (symplecticpublicationsHtml) {
						
						// Add checkbox
						$('#symplecticswitch').html('<p><label for="symplectic">Show Symplectic version? </label><input type="checkbox" id="symplectic" name="symplectic" /></p>');
						
						// Surround existing publications block with a div, automatically, unless <div id="manualpublications">...</div> is already present
						if($("#" + 'manualpublications').length == 0) {
							$("h2#publications").nextUntil('h2').wrapAll('<div id="manualpublications" />');
						}
						
						// Add a location for the new publications block, and hide it at first
						$('#manualpublications').after('<div id="symplecticpublications" />');
						$('#symplecticpublications').hide();
						
						// Add the HTML from the API
						$('#symplecticpublications').html(symplecticpublicationsHtml);
						
						// Add helpful links
						$('#symplecticpublications').prepend('<ul class="actions spaced"><li>This listing goes live {$goLiveDate} &nbsp;</li><li><a href="{$websiteUrl}" target="_blank"><img src="/images/icons/pencil.png" /> Add/edit this list</a></li><li><a href="{$baseUrl}/quickstart.pdf" target="_blank" class="noautoicon"><img src="/images/icons/page.png" />  Help guide (PDF)</a></li></div>');
						
						// Toggle div blocks when checkbox is on
						$('#symplectic').click(function(){
							if ($('#symplectic').is(':checked')) {
								$('#symplecticpublications').show();
								$('#manualpublications').hide();
							} else {
								$('#manualpublications').show();
								$('#symplecticpublications').hide();
							}
						});
						
					// No such user (error 404)
					}).fail(function(){
						$('#symplecticswitch').html('<p>(No publications found in Symplectic.)</p>');
					});
					
				});
			</script>
EOT;
		
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
		
		# Get the groups from the installation
		$groups = $this->getGroupsUpstream ();
		
		# End if none
		if (!$groups) {
			$html .= "\n<p>There are no research groups.</p>";
			if ($this->action == 'api') {return array ('json' => $groups, 'html' => $html);}
			echo $html;
			return true;
		}
		
		# Create a listing
		$list = array ();
		foreach ($groups as $id => $group) {
			$nameHtml = htmlspecialchars ($group['name']);
			$list[$id] = "<a href=\"{$this->baseUrl}/groups/{$id}/\">{$nameHtml}</a>";
		}
		$html = application::htmlUl ($list);
		
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
		
		# Ensure the group is present, or end
		$groups = $this->getGroupsUpstream ();
		if (!isSet ($groups[$moniker])) {
			$errorMessage = 'There is no such group.';
			if ($this->action == 'api') {return array ('json' => array ('error' => $errorMessage), 'html' => $html);}
			$html .= "\n<p>{$errorMessage}</p>";
			echo $html;
			return true;
		}
		$group = $groups[$moniker];
		
		# Get the members of the group
		$users = $this->getGroupMembersUpstream ($group['url']);
		$usernames = array_keys ($users);
		
		# Get the publications for that user
		$publications = $this->getPeoplePublications ($usernames);
		
		# Render as a list
		$html = $this->publicationsList ($publications);
		
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
		
		# Get the most recent publications
		$publications = $this->getRecent ($this->settings['yearsConsideredRecentMainListing']);
		
		# Render as a list
		$html = $this->publicationsList ($publications);
		
		# API output
		if ($this->action == 'api') {return array ('json' => $publications, 'html' => $html);}
		
		# Show publications
		$total = number_format (count ($publications));
		$pageHtml  = $this->apiLinks ();
		$pageHtml .= "\n<p id=\"introduction\">Most recent publications ({$total}) involving members of the Department in the last {$this->settings['yearsConsideredRecentMainListing']} " . ($this->settings['yearsConsideredRecentMainListing'] == 1 ? 'year' : 'years') . ":</p>";
		$pageHtml .= "\n<hr />";
		$pageHtml .= $html;
		
		# Show the page HTML
		echo $pageHtml;
	}
	
	
	
	# Function to provide an API link to the data equivalent of the current page
	private function apiLinks ()
	{
		# Construct the HTML
		$html  = "\n" . '<p class="right faded"><a href="json"><img src="/images/icons/feed.png" alt="JSON output" border="0" /> JSON</a> | <a href="html"><img src="/images/icons/feed.png" alt="JSON output" border="0" /> HTML</a></p>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to get the list of users from the database
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
		
		# Highlight the authors and add starring
		$data = $this->decoratePublications ($data);
		
		# Return the data
		return $data;
	}
	
	
	# Function to get publications of a set of users from the database
	private function getPeoplePublications ($usernames)
	{
		# Assemble the username list into a regexp
		$usernames = '^(' . implode ('|', $usernames) . ')$';
		
		# Get the data; uses GROUP_CONCAT method as described at http://www.mysqlperformanceblog.com/2013/10/22/the-power-of-mysqls-group_concat/
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
		$data = $this->databaseConnection->getData ($query, false, "{$this->settings['database']}.{$this->settings['table']}", array ('usernames' => $usernames));
		
		# Highlight the authors and add starring
		$data = $this->decoratePublications ($data);
		
		# Return the data
		return $data;
	}
	
	
	# Function to get the most recent publications
	private function getRecent ($years)
	{
		# Get the data
		$firstOldYearMainListing = date ('Y') - $this->settings['yearsConsideredRecentMainListing'] - 1;
		$query = "SELECT
				publications.*,
				GROUP_CONCAT(DISTINCT instances.isFavourite) AS isFavourite,
				GROUP_CONCAT(DISTINCT instances.nameAppearsAsAuthor ORDER BY nameAppearsAsAuthor SEPARATOR '|') AS highlightAuthors,
				GROUP_CONCAT(DISTINCT instances.nameAppearsAsEditor ORDER BY nameAppearsAsEditor SEPARATOR '|') AS highlightEditors
			FROM instances
			LEFT OUTER JOIN publications ON instances.publicationId = publications.id
			WHERE
				    CAST(publicationYear AS UNSIGNED INT) > '{$firstOldYearMainListing}'
				AND {$this->typesConstraintString}
			GROUP BY publications.id
			ORDER BY publicationYear DESC, authors
		;";
		$data = $this->databaseConnection->getData ($query, "{$this->settings['database']}.instances");
		
		# Highlight the authors and add starring
		$data = $this->decoratePublications ($data);
		
		# Return the data
		return $data;
	}
	
	
	# Function to decorate publications at runtime (e.g. highlight the authors and add stars)
	private function decoratePublications ($data)
	{
		# Highlight authors
		foreach ($data as $id => $publication) {
			$data[$id]['html'] = $this->highlightContributors ($publication);
		}
		
		# Add book covers if present
		foreach ($data as $id => $publication) {
			$data[$id]['thumbnail'] = false;
			$data[$id]['thumbnailHtml'] = false;
			$location = $this->settings['bookcoversLocation'] . $id . '.' . $this->settings['bookcoversFormat'];
			if (file_exists ($_SERVER['DOCUMENT_ROOT'] . $location)) {
				list ($width, $height, $type, $attributesHtml) = getimagesize ($_SERVER['DOCUMENT_ROOT'] . $location);
				$altHtml = htmlspecialchars ($publication['title']);
				$data[$id]['thumbnail'] = $_SERVER['_SITE_URL'] . $location;
				$data[$id]['thumbnailHtml'] = "<img src=\"{$data[$id]['thumbnail']}\" {$attributesHtml} alt=\"{$altHtml}\" class=\"bookcover\" />";
			}
		}
		
		# Add stars
		foreach ($data as $id => $publication) {
			if ($publication['isFavourite']) {
				$data[$id]['html'] = '<img src="/images/icons/star.png" class="icon favourite" /> ' . $publication['html'];
			}
		}
		
		// application::dumpData ($data);
		
		# Return the data
		return $data;
	}
	
	
	# Function to perform contributor highlighting
	public function highlightContributors ($publication)
	{
		# Convert the full list of contributors and the list of contributors to be highlighted into arrays
		$contributorsOriginal = explode ('|', $publication['authors']);
		$highlightContributors = explode ('|', $publication['highlightAuthors']);
		
		# Add bold to any contributor which is set to be highlighted
		$contributors = $contributorsOriginal;
		foreach ($contributors as $index => $contributor) {
			if (in_array ($contributor, $highlightContributors, true)) {		// Strict matching applied
				$contributors[$index] = '<strong>' . $contributor . '</strong>';
			}
		}
		
		# Convert the original contributors list and the highlighted versions into "A, B, and C" format, as per the original HTML
		$contributorsOriginal = application::commaAndListing ($contributorsOriginal);
		$contributorsHighlighted = application::commaAndListing ($contributors);
		
		# Substitute the contributors listing at the start of the HTML with the new contributors listing
		$delimiter = '/';
		$html = preg_replace ($delimiter . '^' . addcslashes ($contributorsOriginal, $delimiter) . $delimiter, $contributorsHighlighted, $publication['html']);
		
		# Return the HTML
		return $html;
	}
	
	
	# Statistics page
	public function statistics ()
	{
		# Get the data
		$data = $this->getStatistics ();
		
		# Render as a table
		$html  = "\n<p>This page shows the data available in this system:</p>";
		$html .= application::htmlTableKeyed ($data);
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to get the statistics data
	private function getStatistics ()
	{
		# Start an array of data
		$data = array ();
		
		# Total users
		$data['Users'] = $this->databaseConnection->getTotal ($this->settings['database'], 'users');
		
		# Total publications
		$data['Publications'] = $this->getTotalPublications ();
		
		# Total favourited items
		$data['Favourited'] = $this->databaseConnection->getTotal ($this->settings['database'], 'instances', 'WHERE isFavourite = 1');
		
		# Publication types
		$query = "SELECT CONCAT('Type - ', type) AS type, COUNT(*) AS total FROM {$this->settings['table']} GROUP BY type ORDER BY type;";
		$types = $this->databaseConnection->getPairs ($query);
		$data += $types;
		
		# Apply number formatting decoration to each entry
		foreach ($data as $key => $value) {
			$data[$key] = number_format ($value);
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
		if ($importHtml = $this->importInProgress ()) {
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
		if (!$totalPublications = $this->doImport ($importOutputHtml)) {
			$html .= $importOutputHtml;
			echo $importOutputHtml;
			return false;
		}
		
		# Determine how long the import took
		$finishTime = time ();
		$seconds = $finishTime - $startTime;
		
		# Confirm success
		$html .= "\n<div class=\"graybox\">";
		$html .= "\n\t<p>{$this->tick} {$totalPublications} publications were imported.</p>";
		$html .= "\n\t<p>The import took: {$seconds} seconds.</p>";
		$html .= "\n</div>";
		
		# Show output from the import
		$html .= "\n<p>The following warnings were found during the import process, and should be fixed in the source data:</p>";
		$html .= $importOutputHtml;
		
		# Show the HTML
		echo $html;
	}
	
	
	# Define cron jobs; run using:
	# 55 4,9,11,13,15,17,19,21 * * * wget -q -O - http://theusername:@example.com/baseUrl/cron/
	protected function cronJobs ()
	{
		# Run the import
		$this->doImport ();
	}
	
	
	# Function to run the import
	private function doImport (&$html = '')
	{
		# Start the HTML
		$html = '';
		
		# Ensure another import is not running
		if ($importHtml = $this->importInProgress ()) {
			$html .= $importHtml;
			return $html;
		}
		
		# Write the lockfile
		file_put_contents ($this->lockfile, $_SERVER['REMOTE_USER'] . ' ' . date ('Y-m-d H:i:s'));
		
		# Get the users from the local database
		if (!$users = $this->getUsersUpstream ()) {
			$html .= "\n<p>There are no users.</p>";
			unlink ($this->lockfile);
			return false;
		}
		
		# Clear any existing data from the import tables; this should have been done at the end of any previous import
		$tables = array ($this->settings['table'], 'instances', 'users');
		foreach ($tables as $table) {
			$this->databaseConnection->truncate ($this->settings['database'], "{$table}_import", true);
		}
		
		# Import the publications of each user
		foreach ($users as $username => $user) {
			
			# Get the publications of this user, or skip
			if (!$publications = $this->retrievePublicationsOfUser ($username, $html)) {continue;}
			
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
	
	
	# Function to create a formatted list of publications
	# Desired format is:
	// Batchelor, C.L., Dowdeswell, J.A. and Pietras, J.T., 2014. Evidence for multiple Quaternary ice advances and fan development from the Amundsen Gulf cross-shelf trough and slope, Canadian Beaufort Sea margin. Marine and Petroleum Geology, v. 52, p.125-143. doi:10.1016/j.marpetgeo.2013.11.005
	public function publicationsList ($publications)
	{
		# Start the HTML
		$html = '';
		
		# Featured publications
		$favourites = array ();
		foreach ($publications as $publicationId => $publication) {
			if ($publication['isFavourite']) {
				$favourites[$publicationId] = $publication['html'];
			}
		}
		if ($favourites) {
			$html .= "\n<h3>Featured publications</h3>";
			$html .= application::htmlUl ($favourites);
		}
		
		# Regroup by type
		$publicationsByType = application::regroup ($publications, 'type');
		
		# Work through each enabled type
		foreach ($this->types as $type => $label) {
			
			# Skip if none of this type
			if (!isSet ($publicationsByType[$type])) {continue;}
			
			# Create a listing for this type
			if (in_array ($type, $this->typesListingByYear)) {
				$html .= $this->publicationsListByYear ($publicationsByType[$type], $label, $type, $favourites);
			} else {
				$html .= $this->publicationsListSimple ($publicationsByType[$type], $label, $type);
			}
		}
		
		# Prepend with jQuery loading if required
		if ($this->jQueryEnabled) {
			$html = "\n" . '<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>' . $html;
		}
		
		# Surround with a div
		$html = "\n\n\n<div id=\"publicationslist\">" . "\n" . $html . "\n\n</div><!-- /#publicationslist -->\n\n";
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to render a publication group as a simple bullet-point list without grouping
	private function publicationsListSimple ($publications, $label, $type)
	{
		# Start the HTML with label
		$html  = "\n<h3>{$label}</h3>";
		
		# Determine a namespace extension for the jQuery references
		$namespace = '_' . str_replace ('-', '', $type);
		
		# Compile the list
		$oldYear = false;
		$html .= "\n<ul id=\"publications{$namespace}\">";
		foreach ($publications as $publicationId => $publication) {
			
			# If enabled, for the first old year, open a div for Javascript filtering purposes
			if (in_array ($type, $this->expandableTypes)) {
				if (!$oldYear) {		// If not already found, check
					if ($publication['publicationYear'] <= $this->firstOldYear) {
						$oldYear = true;
					}
				}
			}
			
			# Add the publication
			$html .= "\n\t<li" . ($oldYear ? ' class="oldyear"' : '') . '>' . $publication['html'] . '</li>';
		}
		$html .= "\n</ul>";
		
		# Add jQuery expandability at the end of the list
		if ($oldYear) {
			$html .= $this->showHideLinkUl ($namespace, $label);
		}
		
		# If there are book covers show these, as a block at the end of the books
		$images = array ();
		foreach ($publications as $id => $publication) {
			if ($publication['thumbnailHtml']) {
				$images[$id] = $publication['thumbnailHtml'];
			}
		}
		if ($images) {
			$html .= "\n<p class=\"bookcovers\">" . implode (' &nbsp; ', $images) . "\n</p>";
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to render a publication group as a simple bullet-point list without grouping
	private function publicationsListByYear ($publications, $label, $type, $favourites)
	{
		# End if none
		if (!$publications) {return false;}
		
		# Start the HTML
		$html  = "\n<h3>{$label}</h3>";
		
		# Add favourites indication
		if ($favourites) {
			$html .= "<p class=\"small comment\"><em>Key publications are marked with a star.</em></p>";
		}
		
		# Regroup the remaining items by year
		$publications = application::regroup ($publications, 'publicationYear', false);
		
		# Determine a namespace extension for the jQuery references
		$namespace = '_' . str_replace ('-', '', $type);
		
		# Loop through each year
		$oldYearsOpened = false;
		$canSplitIfTotal = $this->settings['canSplitIfTotal'];
		foreach ($publications as $year => $publicationsThisYear) {
			
			# If enabled, for the first old year, open a div for Javascript filtering purposes
			if (in_array ($type, $this->expandableTypes)) {
				if (!$oldYearsOpened && ($year <= $this->firstOldYear) && $canSplitIfTotal <= 0) {
					$oldYearsOpened = true;
					
					# Add a show/hide link for the div
					$html .= $this->showHideLinkDiv ($namespace, $label);
					
					# Add the div
					$html .= "\n\n<div id=\"olderpublications" . $namespace . "\">\n";
				}
			}
			
			# Loop through the publications in the year and add it to the list
			$articles = array ();
			foreach ($publicationsThisYear as $publicationId => $publication) {
				$canSplitIfTotal--;
				$articles[$publicationId] = $publication['html'];
			}
			
			# Add the list for this year
			$html .= "\n<h4>" . ($year ? $year : '[Unknown year]') . '</h4>';
			$html .= application::htmlUl ($articles);
		}
		
		# Close the old years div if it was created
		if ($oldYearsOpened) {
			$html .= "\n\n</div><!-- /#olderpublications -->\n";
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Helper function to create a show/hidden link for a list
	private function showHideLinkUl ($namespace, $label)
	{
		# Enable jQuery
		$this->jQueryEnabled = true;
		
		# Compile the HTML
		$html  = "\n\n<!-- Show/hide link -->";
		$html .= "\n" . '<script type="text/javascript">
			$(document).ready(function(){
				$("#publications' . $namespace . ' li.oldyear").hide();
				$("#publications' . $namespace . '").after("<p class=\"showall\" id=\"showall' . $namespace . '\"><a href=\"#showall' . $namespace . '\">&#9660; Show earlier ' . lcfirst ($label) . ' &hellip;</a></p>");
				$("#showall' . $namespace . ' a").click(function(e){
					e.preventDefault();
					$("#showall' . $namespace . '").hide();
					$("#publications' . $namespace . ' li.oldyear").show();
				});
			});
		</script>
		';
		
		# Return the HTML
		return $html;
	}
	
	
	# Helper function to create a show/hidden link for a div
	private function showHideLinkDiv ($namespace, $label)
	{
		# Enable jQuery
		$this->jQueryEnabled = true;
		
		# Compile the HTML
		$html  = "\n\n<!-- Show/hide link -->";
		$html .= "\n" . '<script type="text/javascript">
			$(document).ready(function(){
				$("#olderpublications' . $namespace . '").hide();
				$("#olderpublications' . $namespace . '").before("<p class=\"showall\" id=\"showall' . $namespace . '\"><a href=\"#showall' . $namespace . '\">&#9660; Show earlier ' . lcfirst ($label) . ' &hellip;</a></p>");
				$("#showall' . $namespace . ' a").click(function(e){
					e.preventDefault();
					$("#showall' . $namespace . '").hide();
					$("#olderpublications' . $namespace . '").show();
				});
			});
		</script>
		';
		
		# Return the HTML
		return $html;
	}
	
	
	# Get the users; the getUsersFunction callback function must return a datastructure like this:
	/*
		Array
		(
		    [spqr1] => Array (
		            [id] => spqr1
		            [name] => Sam Right
		        ),
		    [xyz123] => Array (
		            [id] => abc123
		            [name] => Xavier Yu
		        ),
			...
		);
	*/
	private function getUsersUpstream ()
	{
		# Run callback function
		$function = $this->settings['getUsersFunction'];
		return $function ();
	}
	
	
	# Get the groups; the getGroupsFunction callback function must return a datastructure like this:
	/*
		Array
		(
		    [widgets] => Array (
		            [id] => widgets
		            [name] => Widgets research group
		            [url] => http://www.example.com/research/widgets/
		            [ordering] => 1
		        ),
		    [sprockets] => Array (
		            [id] => sprockets
		            [name] => Sprockets research group
		            [url] => http://www.example.com/research/sprockets/
		            [ordering] => 1
		        ),
			...
		);
	*/
	private function getGroupsUpstream ()
	{
		# Run callback function
		$function = $this->settings['getGroupsFunction'];
		return $function ();
	}
	
	
	# Get the group members; the getGroupMembers callback function must return a datastructure like this:
	/*
		Array
		(
		    [spqr1] => Array (
		            [id] => spqr1
		            [name] => Sam Right
		        ),
		    [xyz123] => Array (
		            [id] => abc123
		            [name] => Xavier Yu
		        ),
			...
		);
	*/
	private function getGroupMembersUpstream ($groupUrl)
	{
		# Run callback function
		$function = $this->settings['getGroupMembers'];
		return $function ($groupUrl);
	}
	
	
	# Raw data viewer (for development purposes)
	public function data ()
	{
		# Obtain the data
		$url = $this->settings['apiHttps'] . '/objects?categories=users&detail=ref&page=1&per-page=20&groups=27';
		$url = '/publications?username=mvl22';
		$url = '/publications/356384';
		
		
		# Get details of a user
		$data = $this->getUser ('jd16');
		
		# Get publications for a user
		// $publications = $this->retrievePublicationsOfUser ('co200');
		
		# Get details of a publication
		// $publication = $this->getPublication (356384);
		
		
		
		
		# Emit the data
		application::dumpData ($data);
	}
	
	
	# Function to get data from the Symplectic API
	private function getData ($call, $format = 'xpathDom', $isFullUrl = false)
	{
		# Assemble the URL
		$url = ($isFullUrl ? '' : $this->settings['apiHttp']) . $call;
		
		# Obtain the XML if required
		require_once ('xml.php');
		$data = @file_get_contents ($url);
		
		# Delay to prevent API overload
		usleep (500000);	// 0.5 seconds is requested in documentation (page 16, "500ms")
		
		/*
		# Logging during development (uncomment to enable)
		foreach ($http_response_header as $header) {
			if (preg_match ('|^HTTP/1|i', $header)) {
				break;	// The correct header has been found
			}
		}
		file_put_contents ('/tmp/publications-api.txt', "\n" . date ('Y-m-d H:i:s') . '  ' . $url . '  ' . $header, FILE_APPEND);
		*/
		
		# If no data, check if the result was a 404, by checking the auto-created variable $http_response_header
		if (!$data) {
			
			# End if no response at all
			if (!isSet ($http_response_header)) {
				echo "\n<p class=\"warning\">No response was received for <em>{$url}</em>.</p>";
				die;
			}
			
			# Find the header which contains the HTTP response code (seemingly usually the first)
			foreach ($http_response_header as $header) {
				if (preg_match ('|^HTTP/1|i', $header)) {
					break;	// The correct header has been found
				}
			}
			
			# If the response was anything other than 404, report the error
			if (!substr_count ($header, ' 404 ')) {
				echo "\n<p class=\"warning\">An empty response was received for <em>{$url}</em>, with header response: <em>{$header}</em>.</p>";
				// application::dumpData ($http_response_header);
				die;
			}
			
			# Signal no data
			return false;
		}
		
		# Debug if required
		// application::dumpData (xml::xml2arrayWithNamespaces ($data));
		// echo $data; die;
		
		# Convert the XML to an array, maintaining namespaced objects
		if ($format == 'json' || $format == 'data') {
			$data = xml::xml2arrayWithNamespaces ($data);
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
		
		# Return an XPath DOM object if required; see: http://stackoverflow.com/a/20318801 and a good explanation of the default namespace at http://web.archive.org/web/20090414184326/http://people.ischool.berkeley.edu/~felix/xml/php-and-xmlns.html
		if ($format == 'xpathDom') {
			$dom = new DOMDocument ();
			$dom->loadXml ($data);
			$xpathDom = new DOMXpath ($dom);
			$xpathDom->registerNamespace ('default', 'http://www.w3.org/2005/Atom');
			$xpathDom->registerNamespace ('api', 'http://www.symplectic.co.uk/publications/api');
			return $xpathDom;
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
	private function retrievePublicationsOfUser ($username, &$errorHtml = '')
	{
		# Define the starting point for the call
		$call = '/users/username-' . $username . '/publications?detail=full';
		$resultsUrlPage = $this->settings['apiHttp'] . $call;
		
		# Get the user's details, or skip if they do not exist
		if (!$user = $this->getUser ($username)) {return false;}
		
		# Start an array of all publication data to return
		$publications = array ();
		
		# Loop through each page of results
		while ($resultsUrlPage) {
			
			# Obtain the data or continue to next
			if (!$xpathDom = $this->getData ($resultsUrlPage, 'xpathDom', true)) {continue;}
			
			# Extract the user's name
			$personName = $this->XPath ($xpathDom, '//default:feed/default:title');
			$personName = $this->extractPersonName ($personName);
			
			# Loop through each entry in the data; see: http://stackoverflow.com/questions/11886176/ and http://stackoverflow.com/questions/5929263/
			$publicationsNode = $xpathDom->query ('//default:feed/default:entry');
			foreach ($publicationsNode as $index => $publicationNode) {
				
				# Get the ID
				$id = $this->XPath ($xpathDom, './/api:object/@id', $publicationNode);
				
				# Ensure the publication is set to be visible
				$isVisible = ($this->XPath ($xpathDom, './/api:is-visible', $publicationNode) == 'true');
				if (!$isVisible) {continue;}
				
				# Add key details
				$publication = array (
					'id'					=> $id,
					'type'					=> $this->XPath ($xpathDom, './/api:object/@type', $publicationNode),
					'lastModifiedWhen'		=> strtotime ($this->XPath ($xpathDom, './/api:object/@last-modified-when', $publicationNode)),
					'doi'					=> $this->XPath ($xpathDom, './/api:field[@name="doi"]/api:text', $publicationNode),
					'title'					=> str_replace (array ("\n", ' '), ' ', $this->XPath ($xpathDom, './/api:field[@name="title"]/api:text', $publicationNode)),
					'journal'				=> $this->XPath ($xpathDom, './/api:field[@name="journal"]/api:text', $publicationNode),
					'publicationYear'		=> $this->XPath ($xpathDom, './/api:field[@name="publication-date"]/api:date/api:year', $publicationNode),
					'publicationMonth'		=> $this->XPath ($xpathDom, './/api:field[@name="publication-date"]/api:date/api:month', $publicationNode),
					'publicationDay'		=> $this->XPath ($xpathDom, './/api:field[@name="publication-date"]/api:date/api:day', $publicationNode),
					'volume'				=> $this->XPath ($xpathDom, './/api:field[@name="volume"]/api:text', $publicationNode),
					'pagination'			=> $this->formatPagination ($this->XPath ($xpathDom, './/api:field[@name="pagination"]/api:pagination/api:begin-page', $publicationNode), $this->XPath ($xpathDom, './/api:field[@name="pagination"]/api:pagination/api:end-page', $publicationNode)),
					'publisher'				=> $this->XPath ($xpathDom, './/api:field[@name="publisher"]/api:text', $publicationNode),
					'parentTitle'			=> $this->XPath ($xpathDom, './/api:field[@name="parent-title"]/api:text', $publicationNode),
					'edition'				=> $this->XPath ($xpathDom, './/api:field[@name="edition"]/api:text', $publicationNode),
					'number'				=> $this->XPath ($xpathDom, './/api:field[@name="number"]/api:text', $publicationNode),
					'url'					=> $this->XPath ($xpathDom, './/api:field[@name="publisher-url"]/api:text', $publicationNode),
					'isFavourite'			=> ($this->XPath ($xpathDom, './/api:is-favourite', $publicationNode) == 'false' ? NULL : 1),
				);
				
				# Get the authors
				$authorsNode = $xpathDom->query ('.//api:record[@is-preferred-record="true"]//api:field[@name="authors"]/api:people/api:person', $publicationNode);
				list ($publication['authors'], $publication['nameAppearsAsAuthor']) = $this->processContributors ($authorsNode, $xpathDom, $user, $publication['id'], 'author', $errorHtml);
				
				# Get the editors
				$editorsNode = $xpathDom->query ('.//api:record[@is-preferred-record="true"]//api:field[@name="editors"]/api:people/api:person', $publicationNode);
				list ($publication['editors'], $publication['nameAppearsAsEditor']) = $this->processContributors ($editorsNode, $xpathDom, $user, $publication['id'], 'editor', $errorHtml);
				
				# Create a compiled HTML version; highlighting is not applied at this stage, as that has to be done at listing runtime depending on the listing context (person/group/all)
				$publication['html'] = $this->compilePublicationHtml ($publication, $errorHtml);
				
				# Add this publication
				$publications[$id] = $publication;
			}
			
			# Determine the next page, if any
			$resultsUrlPage = $xpathDom->evaluate ('string(' . "//default:feed/api:pagination/api:page[@position='next']/@href" . ')');
		}
		
		# Return the array of publications
		return $publications;
	}
	
	
	# Helper function to process contributors (authors/editors)
	private function processContributors ($contributorsNode, $xpathDom, $user, $publicationId, $type, &$errorHtml)
	{
		# Process the contributors
		$contributors = array ();
		$nameAppearsAs = array ();
		foreach ($contributorsNode as $index => $contributorNode) {
			$surname	= $this->XPath ($xpathDom, './api:last-name', $contributorNode);
			$initials	= $this->XPath ($xpathDom, './api:initials', $contributorNode);
			$contributors[$index] = $this->formatContributor ($surname, $initials);
			
			# If this contributor's name appears to match, register this as a possible name match; it is unfortunate that the API seems to provide no proper match indication
			if ($this->isContributorNameMatch ($surname, $initials, $user)) {
				$nameAppearsAs[] = $contributors[$index];
			}
		}
		$contributorsString = implode ('|', $contributors);
		
		# Register what the name is formatted as, reporting any errors detected
		if (!$nameAppearsAs) {
			$errorHtml .= "\n<p class=\"warning\">The {$type}s list for <a href=\"{$this->settings['website']}viewobject.html?cid=1&amp;id={$publicationId}\" target=\"_blank\">publication #{$publicationId}</a> does not appear to contain a match for <em>{$user['displayName']}</em> even though that publication is registered to that user; the {$type}s found were: <em>" . implode ('</em>, <em>', $contributors) . "</em>.</p>";
			$nameAppearsAs = array ();
		}
		if (count ($nameAppearsAs) > 1) {
			$errorHtml .= "\n<p class=\"warning\">A single unique {$type} match for <a href=\"{$this->settings['website']}viewobject.html?cid=1&amp;id={$publicationId}\" target=\"_blank\">publication #{$publicationId}</a> could not be made against <em>{$user['displayName']}</em>; the matches were: <em>" . implode ('</em>, <em>', $nameAppearsAs) . "</em>.</p>";
			$nameAppearsAs = array ();
		}
		$nameAppearsAsString = ($nameAppearsAs ? $nameAppearsAs[0] : NULL);	// Convert the single item to a string, or the empty array to a database NULL
		
		# Return the pair
		return array ($contributorsString, $nameAppearsAsString);
	}
	
	
	# Helper function to match a contributor's name; this attempts to deal with the situation where two names are similar, e.g. the current user is "J. Smith" but the publication has "J. Smith" and "A. Smith" and "A.J. Smith"; this routine would match only on "J. Smith"
	private function isContributorNameMatch ($surname, $initials, $user)
	{
		# Normalise the surname components
		$surname = trim (strtolower ($surname));
		$user['surname'] = trim (strtolower ($user['surname']));
		
		# End if the surname does match
		if ($surname != $user['surname']) {
			return false;
		}
		
		# Normalise the initials components
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
	
	
	# Helper function to normalise initials lists, e.g. "A.B.C." "AB.C." "ABC" "A B C1", or no initials but forename "Anthony Ben Calix", each become array('A','B','C')
	private function normaliseInitials ($initials, $forename = false)
	{
		# Trim and lower-case, and remove non-alphanumeric characters
		$initials = preg_replace ('/[^a-z]/', '', trim (strtolower ($initials)));
		
		# If no initials, use the forname(s), if any
		if ($forename) {
			if (!strlen ($initials)) {
				$forenames = preg_split ('/\s+/', strtolower ($forename));
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
		$initials = str_split ($initials);
		
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
		$authors = application::commaAndListing (explode ('|', $publication['authors']));
		$editors = application::commaAndListing (explode ('|', $publication['editors']));
		
		# Compile the HTML for this publication
		$html  = '';
		if (($publication['type'] == 'book') && strlen ($publication['editors'])) {
			$html .= $editors . ' (' . (substr_count ($publication['editors'], '|') ? 'eds' : 'ed') . '.)';
		} else {
			$html .= $authors;
		}
		$html .= ($publication['publicationYear'] ? ', ' . $publication['publicationYear'] : '') . '. ';
		if (strlen ($publication['url'])) {
			$html .= '<a href="' . htmlspecialchars ($publication['url']) . '" target="_blank">';
		}
		if (($publication['type'] == 'book') || ($publication['type'] == 'internet-publication')) {
			$html .= '<em>';
		}
		$html .= "{$publication['title']}";
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
			if (strlen ($publication['publisher'])) {$html .= ", {$publication['publisher']}";}
		}
		if ($publication['type'] == 'internet-publication') {
			if ($publication['publicationYear']) {
				$html .= ' (' . $this->formatDate ($publication) . ')';
			}
		}
		$html .= '.';
		$html .= (strlen ($publication['journal']) ? " <em>{$publication['journal']}</em>," : '');
		$html .= (strlen ($publication['volume']) ? " v. {$publication['volume']}," : '');
		$html .= (strlen ($publication['pagination']) ? " {$publication['pagination']}." : '');
		$html .= (strlen ($publication['doi']) ? " <a href=\"http://doi.org/{$publication['doi']}\" title=\"Link to publication\" target=\"_blank\">doi:{$publication['doi']}</a>" : '');
		
		# Ensure ends with a dot
		if (substr ($html, -1) == ',') {$html = substr ($html, 0, -1);}
		if (substr ($html, -1) != '.') {$html .= '.';}
		
		# Return the HTML
		return $html;
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
	private function formatPagination ($begin, $end)
	{
		# End if none
		if (!$begin) {return '';}
		
		# Compile the string
		return 'p.' . implode ('-', array ($begin, $end));
	}
	
	
	# Helper function to format an author
	private function formatContributor ($surname, $initials)
	{
		# Add dots after each initials
		$initials = implode ('.', str_split ($initials)) . '.';
		
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

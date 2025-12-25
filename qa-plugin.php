<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/example-page/qa-plugin.php
	Description: Initiates example page plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


qa_register_plugin_module('page', 'qa-notice-page.php', 'qa_notice_page', 'Notice Page');
qa_register_plugin_phrases('qa-notice-lang-*.php', 'notice_page');

// Registering Admin settings
qa_register_plugin_module('module', 'qa-notice-widget-admin.php', 'qa_notice_widget_admin', 'Initialization of notice widget module');

//Creating a page for creation of notice boards events.
qa_register_plugin_module('page', 'qa-notice-board-create.php', 'notice_board_create', 'Page for creating notice boards events');
qa_register_plugin_module('page', 'notice-usersearch.php', 'notice_usersearch', 'Ajax request Usersearch Page for Notice plugin');

//Widget Registration
qa_register_plugin_module('widget','qa-noticeboard-widget.php','qa_notice_widget','Notice Board (Scrolling)');




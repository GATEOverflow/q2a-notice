<?php
/*
   Question2Answer by Gideon Greenspan and contributors
   http://www.question2answer.org/

   File: qa-plugin/example-page/qa-example-page.php
   Description: Page module class for example page plugin


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

class qa_notice_page
{
	private $directory;
	private $urltoroot;
	private $new;

	public function load_module($directory, $urltoroot)
	{
		$this->directory=$directory;
		$this->urltoroot=$urltoroot;
	}


	public function suggest_requests() // for display in admin interface
	{
		return array(
				array(
					'title' => 'Notice',
					'request' => 'notice-plugin-page',
					'nav' => 'F', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				     ),
			    );
	}
	public function match_request($request)
	{
		return $request == 'notice-plugin-page';
	}


	public function process_request($request)
	{
		$qa_content=qa_content_prepare();
		$user_level = qa_get_logged_in_level();
		if($user_level<QA_USER_LEVEL_MODERATOR)
		{
			$qa_content=qa_content_prepare();

			if($user_level==null)
				$qa_content['error']="Nothing Yet, Try Logging In, Come back, Ask me again!";
			else
				$qa_content['error']="You Don't Have The Permissions. Ask Me When you Grow Up.";

			return $qa_content;
		}

		$ok = null;
		$qa_content['title']=qa_lang('notice_page/page_title');
		$editorname = isset($in['editor']) ? $in['editor'] : qa_opt('editor_for_qs');
		if(qa_clicked('okthen'))
		{
			require_once QA_INCLUDE_DIR.'qa-db-notices.php';
			require_once QA_INCLUDE_DIR.'db/selects.php';
			$notice=qa_post_text('content');
			$level = qa_post_text('level');
			$tousers = qa_db_single_select(qa_db_users_from_level_selectspec($level));
			foreach($tousers as $user)
			{
				qa_db_usernotice_create($user['userid'], $notice, 'html', 'byadmin: '.$level);
			}
			$ok = qa_lang('notice_page/notice_sent');
		}
		require_once QA_INCLUDE_DIR.'app/users.php';
		$showoptions = array(
				QA_USER_LEVEL_BASIC =>	"Registered Users",
				QA_USER_LEVEL_EXPERT =>	"Experts",
				QA_USER_LEVEL_EDITOR =>	"Editors",
				QA_USER_LEVEL_MODERATOR =>	"Moderators",
				QA_USER_LEVEL_ADMIN =>	"Admins",
				QA_USER_LEVEL_SUPER =>	"Super Admins",
				);
		$editor = qa_load_editor(@$in['content'], @$in['format'], $editorname);

		$field = qa_editor_load_field($editor, $qa_content, @$in['content'], @$in['format'], 'content', 12, false);
		$field['label'] = qa_lang('notice_page/notice_content_label');
		$field['error'] = qa_html(@$errors['content']);

		$qa_content['form']=array(
				'tags' => 'method="post" action="'.qa_self_html().'"',

				'style' => 'wide',
				'ok' => ($ok && !isset($error)) ? $ok : null,

				'title' => qa_lang('notice_page/form_title'),

				'fields' => array(
					'content' => $field,
					'category' =>array(
						'label' => 'User Level',
						'tags' => 'name="level"',
						'type' => 'select',
						'options' => $showoptions,
						),


					),

				'buttons' => array(
						'ok' => array(
							'tags' => 'name="okthen"',
							'label' => 'Submit',
							'value' => '1',
							),
						),

				);


		return $qa_content;
	}
}

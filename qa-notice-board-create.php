<?php
if (!defined('QA_VERSION')) { header('Location: ../../'); exit; }

class notice_board_create
{
    private $urltoroot;
    private $level_options;
	private $first_card=1;

    public function load_module($directory, $urltoroot)
    {
        $this->urltoroot = $urltoroot;

        require_once QA_INCLUDE_DIR.'app/users.php';
        $this->level_options = [
            QA_USER_LEVEL_BASIC     => 'Registered Users',
            QA_USER_LEVEL_EXPERT    => 'Experts',
            QA_USER_LEVEL_EDITOR    => 'Editors',
            QA_USER_LEVEL_MODERATOR => 'Moderators',
            QA_USER_LEVEL_ADMIN     => 'Admins',
            QA_USER_LEVEL_SUPER     => 'Super Admins',
        ];
    }

    public function match_request($request)
    {
        return $request === 'notice-board';
    }

    public function process_request($request)
    {
		$required_level = qa_opt('notice_board_manage_level');
        $content = qa_content_prepare();
        $content['title'] =  qa_lang('notice_page/notice_create_page_title');
		$user_level = qa_get_logged_in_level();
		if($user_level< $required_level)
		{
			if($user_level==null)
				$content['error']=qa_lang('notice_page/notice_non_logged');
			else
				$content['error']=qa_lang('notice_page/notice_non_authorised');

			return $content;
		}

        $content['custom'] =
			'<script>
				window.QA_NOTICE_LEVELS = ' .
				json_encode($this->level_options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) .
			';
			let notice_title_label = "'.qa_lang('notice_page/notice_title_label').'";
			var notice_description_label = "'.qa_lang('notice_page/notice_description_label').'";
			var notice_URL_label = "'.qa_lang('notice_page/notice_URL_label').'";
			var notice_audience_label = "'.qa_lang('notice_page/notice_audience_label').'";
			var notice_public_label = "'.qa_lang('notice_page/notice_public_label').'";
			var notice_logged_label = "'.qa_lang('notice_page/notice_logged_label').'";
			var notice_specific_label = "'.qa_lang('notice_page/notice_specific_label').'";
			var notice_from_label = "'.qa_lang('notice_page/notice_from_label').'";
			var notice_to_label = "'.qa_lang('notice_page/notice_to_label').'";
			
			var notice_title_error_label = "'.qa_lang('notice_page/notice_title_error_label').'";
			var notice_start_date_error_label = "'.qa_lang('notice_page/notice_start_date_error_label').'";
			var notice_end_date_error_label = "'.qa_lang('notice_page/notice_end_date_error_label').'";
			var notice_start_end_date_error_label = "'.qa_lang('notice_page/notice_start_end_date_error_label').'";
			
			
			</script>';
			

		$content['css_src'][]    = $this->urltoroot . 'css/notice-create.css';
        $content['script_src'][] = $this->urltoroot . 'js/notice-create.js';

		$post_error = null;

		if (qa_clicked('save_notices')) {
			$result = $this->save_notices();
			if (is_array($result)) {
				$post_error = $result;
				$content['error'] = $result['message'];

				$content['custom'] .=
					'<script>
						window.NB_SERVER_ERROR = ' .
						json_encode($result, JSON_UNESCAPED_UNICODE) .
					';
					</script>';
			} else {
				$content['custom'] .= '<script>
					function nbShowToast(message) {
						let t = document.getElementById("nb-toast");
						if (!t) {
							t = document.createElement("div");
							t.id = "nb-toast";
							document.body.appendChild(t);
						}

						t.textContent = message;
						t.className = "nb-toast-show";

						setTimeout(() => t.classList.remove("nb-toast-show"), 3000);
					}
					nbShowToast("'.qa_lang('notice_page/notice_create_saved').'");
				</script>';
			}
		}
		$content['custom'] .= $this->render_html($post_error !== null);
        return $content;
    }

    private function render_html($use_post = false)
    {
		//If any errors present, return the error code and values. Else, DB access.
		if ($use_post && isset($_POST['title'])) {
			$rows = [];
			$count = count($_POST['title']);

			for ($i = 0; $i < $count; $i++) {
				$rows[] = [
					'notice_id'      => (int)($_POST['notice_id'][$i] ?? 0),
					'notice_title'   => $_POST['title'][$i] ?? '',
					'notice_desc'    => $_POST['desc'][$i] ?? '',
					'notice_url'     => $_POST['url'][$i] ?? '',
					'audience_type'  => $_POST['audience'][$i] ?? 'public',
					'min_level'      => $_POST['min_level'][$i] ?? null,
					'start_at'       => $_POST['start'][$i] ?? '',
					'end_at'         => $_POST['end'][$i] ?? '',
					'_post_users'    => $_POST['specific_users'][$i] ?? '',
				];
			}

		} else {
			$rows = qa_db_read_all_assoc(
				qa_db_query_sub("SELECT * FROM ^noticeboard ORDER BY position ASC")
			);
		}

        $html = '<form method="post" id="noticeForm">';
        $html .= '<div id="nbList">';
        foreach ($rows as $r) {
            $html .= $this->notice_card($r);
        }
		
		if (empty($rows)) {
			$html .= '
				<button type="button" id="nbAdd" class="qa-form-tall-button nb-add">
					➕ '.qa_lang('notice_page/notice_create_first').'
				</button>';
		}


        $html .= '</div>

        <div class="nb-save-wrap">
            <input type="submit" name="save_notices"  class="nb-save-btn"
                value="'.qa_lang('notice_page/notice_create_save').'"
                >
        </div>
        </form>';

        return $html;
    }

    private function notice_card($r = [])
    {
        $id    = (int)($r['notice_id'] ?? 0);
        $title = qa_html($r['notice_title'] ?? '');
        $desc  = qa_html($r['notice_desc'] ?? '');
        $url   = qa_html($r['notice_url'] ?? '');
        $aud   = $r['audience_type'] ?? 'public';
		$visible_text = $aud;
		if($aud == 'min_level'){
			$visible_text= "Min.Level: ".$this->level_options[$r['min_level']];
		}
		if($aud == 'specific_users'){
			$visible_text= "Specific Users";
		}
		if($aud == 'public'){
			$visible_text= "Public";
		}
        $min   = (int)($r['min_level'] ?? 0);

        $start = date('Y-m-d H:i', strtotime($r['start_at']));
        $end   = date('Y-m-d H:i', strtotime($r['end_at']));
        if (isset($r['_post_users'])) {
			$users = qa_html($r['_post_users']);
		} else {
			$users = $id ? qa_html($this->load_specific_users($id)) : '';
		}

		$addAboveBtn = '';
		if ($this->first_card) {
			$addAboveBtn =
				"<button type='button' class='nb-add-above' ".
				"title='".qa_lang('notice_page/add_notice_above')."'>++</button>";
			$this->first_card = 0;
		}

        return "
        <div class='nb-item open'>
            <input type='hidden' name='notice_id[]' value='{$id}'>

            <div class='nb-summary'>
                <div>
                    <div class='nb-title'>{$title}</div>
                    <div class='nb-meta'>
                        {$visible_text},  {$start} → {$end}
                    </div>
                </div>
				<div class='nb-actions'>
				{$addAboveBtn}
					<button type='button' class='nb-add-below' title='".qa_lang('notice_page/add_notice')."'>＋</button>
					<button type='button' class='nb-up' title='".qa_lang('notice_page/up_notice')."'>↑</button>
					<button type='button' class='nb-down' title='".qa_lang('notice_page/down_notice')."'>↓</button>
					<button type='button' class='nb-del' title='".qa_lang('notice_page/remove_notice')."'>✖</button>
				</div>

            </div>

            <div class='nb-body'>
                <label>".qa_lang('notice_page/notice_title_label')."</label>
                <input name='title[]' value='{$title}' required>

                <label>".qa_lang('notice_page/notice_description_label')."</label>
                <textarea name='desc[]' rows='2'>{$desc}</textarea>

				<label>".qa_lang('notice_page/notice_URL_label')."</label>
                <input type='url' name='url[]' value='{$url}' placeholder='Absolute link'>

                <div class='nb-grid'>
                    <div>
                        <label>".qa_lang('notice_page/notice_from_label')."</label>
                        <input type='datetime-local' name='start[]' value='{$start}'>
                    </div>
                    <div>
                        <label>".qa_lang('notice_page/notice_to_label')."</label>
                        <input type='datetime-local' name='end[]' value='{$end}'>
                    </div>
                </div>

                
                <label>".qa_lang('notice_page/notice_audience_label')."</label>
                <select name='audience[]' class='audience'>
                    <option value='public' ".($aud==='public'?'selected':'').">".qa_lang('notice_page/notice_public_label')."</option>
                    <option value='min_level' ".($aud==='min_level'?'selected':'').">".qa_lang('notice_page/notice_logged_label')."</option>
                    <option value='specific_users' ".($aud==='specific_users'?'selected':'').">".qa_lang('notice_page/notice_specific_label')."</option>
                </select>

                <div class='aud-min'>
                    {$this->render_min_level_select($min)}
                </div>

				<div class='aud-users'>
					<div class='nb-usersearch'>
						<input type='text' class='nb-user-handle-search' placeholder='Search username to add to the list...'>
						<div class='nb-user-progress' style='display:none'></div>
						<div class='nb-user-results'></div>
					</div>

					<textarea name='specific_users[]' rows='2'
						placeholder='Comma separated user handles'>{$users}
					</textarea>
				</div>

            </div>
        </div>";
    }

    private function render_min_level_select($selected)
    {
        $html = "<select name='min_level[]'>";
        foreach ($this->level_options as $lvl => $label) {
            $sel = ($selected === (int)$lvl) ? 'selected' : '';
            $html .= "<option value='{$lvl}' {$sel}>{$label}</option>";
        }
        $html .= "</select>";
        return $html;
    }

    private function save_notices()
    {
        $ids    = $_POST['notice_id'] ?? [];
        $titles = $_POST['title'] ?? [];
        $descs  = $_POST['desc'] ?? [];
        $aud    = $_POST['audience'] ?? [];
        $starts = $_POST['start'] ?? [];
        $ends   = $_POST['end'] ?? [];
        $urls   = $_POST['url'] ?? [];
        $mins   = $_POST['min_level'] ?? [];
        $users  = $_POST['specific_users'] ?? [];

        $pos = 1;
        $seen = [];

        foreach ($titles as $i => $title) {
            $title = trim($title);
            

            $id  = (int)($ids[$i] ?? 0);
            $audience = $aud[$i] ?? 'public';
            $min = ($audience === 'min_level') ? (int)($mins[$i] ?? 0) : null;
			
			$error = $this->validate_notice_row(
				$title,
				$starts[$i] ?? '',
				$ends[$i] ?? ''
			);

			if ($error) {
				return [
					'index' => $i,
					'field' => $this->guess_error_field($error),
					'message' => $error,
				];
			}


            if ($id) {
                qa_db_query_sub(
                    "UPDATE ^noticeboard
                     SET notice_title=$, notice_desc=$, audience_type=$,
                         start_at=$, end_at=$, notice_url=$,
                         min_level=$, position=$
                     WHERE notice_id=$",
                    $title, $descs[$i], $audience,
                    $starts[$i], $ends[$i], $urls[$i],
                    $min, $pos, $id
                );
                $nid = $id;
            } else {
                qa_db_query_sub(
                    "INSERT INTO ^noticeboard
                     (notice_title, notice_desc, audience_type,
                      start_at, end_at, notice_url, min_level, position)
                     VALUES ($,$,$,$,$,$,$,$)",
                    $title, $descs[$i], $audience,
                    $starts[$i], $ends[$i], $urls[$i],
                    $min, $pos
                );
                $nid = qa_db_last_insert_id();
				
				qa_report_event(
					'notice_created',
					qa_get_logged_in_userid(),
					qa_get_logged_in_handle(),
					qa_cookie_get(),
					array(
						'notice_id' => $nid,
						'title'     => $title,
						'desc' => $descs[$i],
						'url' => $urls[$i],
						'start'     => $starts[$i],
						'end'       => $ends[$i],
						'audience'  => $audience,
						'min' => $min,
						'specific_users' => $users[$i] ?? ''
					)
				);
            }

            $this->save_specific_users($nid, $audience, $users[$i] ?? '');
            $seen[] = $nid;
            $pos++;
        }

        if ($seen) {
            qa_db_query_sub(
                "DELETE FROM ^noticeboard WHERE notice_id NOT IN (#)",
                $seen
            );
        }
		
		return null;
    }

	private function save_specific_users($nid, $audience, $raw)
	{
		qa_db_query_sub("DELETE FROM ^notice_user WHERE notice_id=$", $nid);
		if ($audience !== 'specific_users') {
			return;
		}

		$parts = preg_split('/[,]+/', $raw);
		$handles = array_filter($parts);

		foreach ($handles as $h) {
			$uid = qa_db_read_one_value(
				qa_db_query_sub("SELECT userid FROM ^users WHERE handle = $ LIMIT 1", $h),
				true
			);

			if ($uid) {
				qa_db_query_sub(
					"INSERT INTO ^notice_user (notice_id, userid) VALUES ($,$)",
					$nid, $uid
				);
			}
		}
	}


    private function load_specific_users($nid)
    {
        $rows = qa_db_read_all_assoc(
            qa_db_query_sub("SELECT u.handle FROM ^notice_user n join ^users u on u.userid= n.userid WHERE n.notice_id=$", $nid)
        );
        return implode(',', array_column($rows, 'handle'));
    }
	
	private function validate_notice_row($title, $start, $end)
	{
		if (trim($title) === '') {
			return qa_lang('notice_page/notice_title_error_label');
		}

		$now   = time();
		$startTs = strtotime($start);
		$endTs   = strtotime($end);

		if (!$startTs) {
			return qa_lang('notice_page/notice_start_date_error_label');
		}

		if (!$endTs) {
			return qa_lang('notice_page/notice_end_date_error_label');
		}

		if ($endTs < $startTs) {
			return qa_lang('notice_page/notice_start_end_date_error_label');
		}

		return null; // valid
	}

	private function guess_error_field($msg)
	{
		if (stripos($msg, 'Title') !== false) return 'title';
		if (stripos($msg, 'End') !== false) return 'end';
		if (stripos($msg, 'Start') !== false) return 'start';
		return 'general';
	}
}

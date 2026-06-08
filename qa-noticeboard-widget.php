<?php
if (!defined('QA_VERSION')) { header('Location: ../../'); exit; }

class qa_notice_widget
{
	private static $assets_loaded = false;
	private $urltoroot;

    public function load_module($directory, $urltoroot)
    {
        $this->urltoroot = $urltoroot;
    }
    public function allow_template($template)
    {
        return true; // show on all pages
    }

    public function allow_region($region)
    {
        return in_array($region, ['side', 'main']);
    }

    public function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
    {
		if (!self::$assets_loaded) {
                        $v = 3.3; // version for cache busting

                        $themeobject->output(
                                '<link rel="stylesheet" href="'.$this->urltoroot.'css/qa-notice-widget.css?v='.$v.'" media="print" onload="this.media=\'all\'">'
                        );
                        $themeobject->output(
                                '<script defer src="'.$this->urltoroot.'js/qa-notice-widget.js?v='.$v.'"></script>'
                        );

                        // Pass config to JS
                        $displayMode = qa_opt('notice_board_display_mode') ?: 'cards';
                        $cardInterval = (int)(qa_opt('notice_board_card_interval') ?: 10);
                        $themeobject->output(
                                '<script>var QA_NOTICE_CONFIG={mode:'.json_encode($displayMode).',interval:'.$cardInterval.'};</script>');

			self::$assets_loaded = true;
		}


        $userid     = qa_get_logged_in_userid();
        $userlevel  = qa_get_logged_in_level();
        $notices = $this->fetch_notices($userid, $userlevel);

        // Separate static (pinned) notices from regular ones
        $staticNotices = [];
        $regularNotices = [];
        foreach (($notices ?: []) as $n) {
            if (!empty($n['is_static'])) {
                $staticNotices[] = $n;
            } else {
                $regularNotices[] = $n;
            }
        }

        $userAttr = $userid ? ' data-userid="'.qa_html($userid).'"' : '';
        $canCreate = $userid && $userlevel >= (int)qa_opt('notice_board_manage_level');
        $themeobject->output('<div class="qa-notice-widget"'.$userAttr.'>');
        $themeobject->output('<div class="qa-notice-title">');
        $themeobject->output('<span>'.qa_lang('notice_page/notice_widget_title').'</span>');
        if ($canCreate) {
            $themeobject->output(
                '<a class="qa-notice-create-btn" href="'.qa_path_html('notice-board').'">'.qa_lang('notice_page/notice_create_btn').'</a>'
            );
        }
        $themeobject->output('</div>');

        // Static notices: always visible, no dismiss, stacked
        if (!empty($staticNotices)) {
            $themeobject->output('<div class="qa-notice-static">');
            foreach ($staticNotices as $n) {
                $title = qa_html($n['notice_title']);
                $url = $n['notice_url'] ? qa_html($n['notice_url']) : null;
                $themeobject->output('<div class="qa-notice-static-item">');
                $themeobject->output(
                    $url
                        ? '<div class="qa-notice-title-text"><a href="'.$url.'" target="_blank">'.$title.'</a></div>'
                        : '<div class="qa-notice-title-text">'.$title.'</div>'
                );
                if (!empty($n['notice_desc'])) {
                    $themeobject->output('<div class="qa-notice-desc">'.qa_html($n['notice_desc']).'</div>');
                }
                $themeobject->output('</div>');
            }
            $themeobject->output('</div>');
        }

        // Regular notices (scrolling/cards)
        $themeobject->output('<div class="qa-notice-scroll">');
		$themeobject->output('<div class="qa-notice-track">');

		if (!$regularNotices && !$staticNotices) {
			$themeobject->output(
				'<div class="qa-notice-empty">'.qa_lang('notice_page/notice_widget_no_notice').'</div>'
			);
			$themeobject->output('</div>');
			$themeobject->output('</div></div>');
			return;
		}

		if (empty($regularNotices)) {
			$themeobject->output('</div></div>');
			// No card nav or footer needed if only static notices
			$themeobject->output('</div>');
			return;
		}

        foreach ($regularNotices as $n) {
            $title = qa_html($n['notice_title']);
            $url   = $n['notice_url']
                ? qa_html($n['notice_url'])
                : null;
            $nid = (int)$n['notice_id'];

			$themeobject->output('<div class="qa-notice-item" data-notice-id="'.$nid.'">');

			$themeobject->output(
				$url
					? '<div class="qa-notice-title-text"><a href="'.$url.'" target="_blank">'.$title.'</a></div>'
					: '<div class="qa-notice-title-text">'.$title.'</div>'
			);

			if (!empty($n['notice_desc'])) {
				$themeobject->output(
					'<div class="qa-notice-desc">'.qa_html($n['notice_desc']).'</div>'
				);
			}

			if ($userid) {
				$themeobject->output(
					'<button class="qa-notice-dismiss" title="'.qa_lang('notice_page/notice_mark_read').'">&times;</button>'
				);
			}

			$themeobject->output('</div>');

        }

        $themeobject->output('</div>'); // close track
        if ($userid) {
            $themeobject->output(
                '<div class="qa-notice-allread" style="display:none;">'.qa_lang('notice_page/notice_all_read').'</div>'
            );
        }
        $themeobject->output('</div>'); // close scroll

        // Card mode navigation
        $themeobject->output('<div class="qa-notice-card-nav" style="display:none;">');
        $themeobject->output('<button class="qa-notice-prev" title="Previous">&lsaquo;</button>');
        $themeobject->output('<span class="qa-notice-indicator"></span>');
        $themeobject->output('<button class="qa-notice-next" title="Next">&rsaquo;</button>');
        $themeobject->output('<button class="qa-notice-pause" title="Pause/Resume auto-advance">&#9208;</button>');
        $themeobject->output('</div>');
        if ($userid) {
            $themeobject->output('<div class="qa-notice-footer">');
            $themeobject->output(
                '<button class="qa-notice-mark-all" style="display:none;">'.qa_lang('notice_page/notice_mark_all_read').'</button>'
            );
            $themeobject->output(
                '<div class="qa-notice-show-all" style="display:none;"><a href="#">'.qa_lang('notice_page/notice_show_all').'</a></div>'
            );
            $themeobject->output('</div>');
        }
        $themeobject->output('</div>'); // close widget
    }

	private function fetch_notices($userid, $userlevel)
	{
		return qa_db_read_all_assoc(
			qa_db_query_sub(
				"
				SELECT n.*
				FROM ^noticeboard n
				LEFT JOIN ^notice_user nu
					ON nu.notice_id = n.notice_id
					AND nu.userid = $
				WHERE
					n.start_at <= NOW()
					AND n.end_at   >= NOW()
					AND (
						n.audience_type = 'public'
						OR (
							n.audience_type = 'min_level'
							AND $ IS NOT NULL
							AND n.min_level <= $
						)
						OR (
							n.audience_type = 'specific_users'
							AND $ IS NOT NULL
							AND nu.userid IS NOT NULL
						)
					)
				GROUP BY n.notice_id
				ORDER BY n.position ASC
				",
				$userid,
				$userid,
				$userlevel,
				$userid
			)
		);
	}
}

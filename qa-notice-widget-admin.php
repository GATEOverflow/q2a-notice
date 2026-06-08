<?php
if (!defined('QA_VERSION')) { header('Location: ../../'); exit; }

class qa_notice_widget_admin
{
        private $level_options;

        public function load_module($directory, $urltoroot)
        {
                require_once QA_INCLUDE_DIR.'app/users.php';

                $this->level_options = [
                        QA_USER_LEVEL_EDITOR    => 'Editors',
                        QA_USER_LEVEL_MODERATOR => 'Moderators',
                        QA_USER_LEVEL_ADMIN     => 'Admins',
                        QA_USER_LEVEL_SUPER     => 'Super Admins',
                ];
        }

        public function option_default($option)
        {
                if ($option === 'notice_board_manage_level')
                        return QA_USER_LEVEL_EDITOR;
                if ($option === 'notice_board_display_mode')
                        return 'cards';
                if ($option === 'notice_board_card_interval')
                        return 10;
        }

        public function admin_form()
        {
                $ok = null;
                if (qa_clicked('notice_save_button')) {
                        $level = (int)qa_post_text('notice_board_manage_level');
                        qa_opt('notice_board_manage_level', $level);

                        $mode = qa_post_text('notice_board_display_mode');
                        if (in_array($mode, ['cards', 'scroll']))
                                qa_opt('notice_board_display_mode', $mode);

                        $interval = (int)qa_post_text('notice_board_card_interval');
                        if ($interval < 2) $interval = 2;
                        if ($interval > 120) $interval = 120;
                        qa_opt('notice_board_card_interval', $interval);

                        $ok = qa_lang('notice_page/notice_option_saved');
                }

                $fields = [];

                $current = qa_opt('notice_board_manage_level');
                $fields[] = [
                        'label' => qa_lang('notice_page/notice_manage_level_label'),
                        'tags'  => 'name="notice_board_manage_level"',
                        'type'  => 'select',
                        'value' => $this->level_options[$current],
                        'options' => $this->level_options,
                        'note' => qa_lang('notice_page/notice_manage_level_desc'),
                ];

                $modeOptions = ['cards' => 'Cards (one at a time)', 'scroll' => 'Scroll (bottom to top)'];
                $currentMode = qa_opt('notice_board_display_mode') ?: 'cards';
                $fields[] = [
                        'label' => 'Widget display mode:',
                        'tags'  => 'name="notice_board_display_mode"',
                        'type'  => 'select',
                        'value' => $modeOptions[$currentMode],
                        'options' => $modeOptions,
                        'note' => 'Cards shows one notice at a time with navigation arrows. Scroll animates bottom-to-top.',
                ];

                $currentInterval = (int)qa_opt('notice_board_card_interval') ?: 10;
                $fields[] = [
                        'label' => 'Card auto-advance interval (seconds):',
                        'tags'  => 'name="notice_board_card_interval"',
                        'type'  => 'number',
                        'value' => $currentInterval,
                        'note' => 'How many seconds before automatically showing the next card (2-120). Users can pause this.',
                ];

                return [
                        'ok'     => $ok,
                        'fields' => $fields,
                        'buttons'=> [
                                [
                                        'label' => qa_lang('notice_page/notice_save_btn'),
                                        'tags'  => 'name="notice_save_button"',
                                ],
                        ],
                ];
        }

    public function init_queries($tableslc)
    {
        $queries = [];

        $noticeTbl = qa_db_add_table_prefix('noticeboard');
        $mapTbl    = qa_db_add_table_prefix('notice_user');

        if (!in_array($noticeTbl, $tableslc)) {
            $queries[] = "
            CREATE TABLE `$noticeTbl` (
                notice_id INT AUTO_INCREMENT PRIMARY KEY,
                notice_title VARCHAR(255) NOT NULL,
                notice_desc TEXT,
                notice_url VARCHAR(1000),
                audience_type ENUM('public','min_level','specific_users') NOT NULL DEFAULT 'public',
                min_level SMALLINT DEFAULT NULL,
                position INT NOT NULL DEFAULT 1,
                start_at DATETIME NOT NULL,
                end_at   DATETIME NOT NULL,
                INDEX (audience_type),
                INDEX (start_at, end_at),
                INDEX (position)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
        }

        if (!in_array($mapTbl, $tableslc)) {
            $queries[] = "
                CREATE TABLE `$mapTbl` (
                    notice_id INT NOT NULL,
                    userid INT NOT NULL,
                    PRIMARY KEY (notice_id, userid),
                    INDEX idx_notice_user_userid (userid),
                    CONSTRAINT fk_{$mapTbl}_notice
                        FOREIGN KEY (notice_id)
                        REFERENCES `$noticeTbl` (notice_id)
                        ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
        }

        return empty($queries) ? null : $queries;
    }
}

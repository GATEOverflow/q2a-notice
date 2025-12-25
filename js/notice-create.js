(function () {

    //Helper functions
	
	// this function is required because AJAX response calls: to_add_username(handle)
	window.to_add_username = function(handle) {
		// find the current "Specific users" card that has focus
		const activeInput = document.activeElement;

		let card;
		if (activeInput) {
			card = activeInput.closest('.nb-item');
		}

		// if not found, fallback to first open card
		if (!card) {
			card = document.querySelector('.nb-item.open') ||
				   document.querySelector('.nb-item');
		}

		if (!card) return;

		// the textarea where handles should be added
		const textarea = card.querySelector('textarea[name="specific_users[]"]');
		if (!textarea) return;

		// append handle
		const trimmed = textarea.value.trim();
		textarea.value = trimmed ? trimmed + ',' + handle : handle;
	};

	//Validation of dates
	function parseLocalDate(value) {
		if (!value) return null;
		return new Date(value.replace(' ', 'T'));
	}

	//Validation of all information of the each card.
	function validateAllCards() {
		const now = new Date();
		let firstError = null;

		document.querySelectorAll('.nb-item').forEach(card => {

			const titleInput = card.querySelector('input[name="title[]"]');
			const startInput = card.querySelector('input[name="start[]"]');
			const endInput   = card.querySelector('input[name="end[]"]');

			// reset previous error states
			card.classList.remove('nb-error');
			card.querySelectorAll('.nb-error-msg').forEach(e => e.remove());

			// TITLE Validation
			if (!titleInput || titleInput.value.trim() === '') {
				markError(card, titleInput, notice_title_error_label);
				firstError ??= card;
				return;
			}

			// DATES Validation
			const start = parseLocalDate(startInput?.value);
			const end   = parseLocalDate(endInput?.value);

			if (!start) {
				markError(card, startInput, notice_start_date_error_label);
				firstError ??= card;
				return;
			}


			if (!end) {
				markError(card, endInput, notice_end_date_error_label);
				firstError ??= card;
				return;
			}

			if (end < start) {
				markError(card, endInput,notice_start_end_date_error_label);
				firstError ??= card;
				return;
			}
		});

		if (firstError) {
			firstError.classList.add('open');
			firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
			return false;
		}

		return true;
	}

	//Showing error dilogs
	function markError(card, input, message) {
		card.classList.add('nb-error');

		if (input) {
			input.classList.add('nb-input-error');

			const msg = document.createElement('div');
			msg.className = 'nb-error-msg';
			msg.textContent = message;
			input.after(msg);
		}
	}

	//Forming the levels for selecting minimum level in the New card
    function buildMinLevelSelect(selectedValue) {
        const sel = document.createElement('select');
        sel.name = 'min_level[]';

        const def = document.createElement('option');

        if (window.QA_NOTICE_LEVELS) {
            Object.entries(window.QA_NOTICE_LEVELS).forEach(([value, label]) => {
                const opt = document.createElement('option');
                opt.value = value;
                opt.textContent = label;
                if (String(value) === String(selectedValue)) {
                    opt.selected = true;
                }
                sel.appendChild(opt);
            });
        }

        return sel;
    }
	
	//Set default dates for the new card
	function setDefaultDates(card) {
		const startInput = card.querySelector('input[name="start[]"]');
		const endInput   = card.querySelector('input[name="end[]"]');

		if (!startInput || !endInput) return;

		const now = new Date();

		const start = new Date(now);
		const end   = new Date(now);
		end.setDate(end.getDate() + 1);

		// Format as YYYY-MM-DDTHH:MM (LOCAL TIME)
		const toLocalDatetime = d => {
			const pad = n => String(n).padStart(2, '0');
			return (
				d.getFullYear() + '-' +
				pad(d.getMonth() + 1) + '-' +
				pad(d.getDate()) + 'T' +
				pad(d.getHours()) + ':' +
				pad(d.getMinutes())
			);
		};

		startInput.value = toLocalDatetime(start);
		endInput.value   = toLocalDatetime(end);
	}

	//Based on the selected audience type, show applicable feilds
    function initAudience(card) {
        const audSel = card.querySelector('.audience');
        if (!audSel) return;

        const minBox = card.querySelector('.aud-min');
        const usrBox = card.querySelector('.aud-users');

        if (minBox && !minBox.querySelector('select')) {
            minBox.appendChild(buildMinLevelSelect(''));
        }

        if (minBox) {
            minBox.style.display =
                audSel.value === 'min_level' ? 'block' : 'none';
        }

        if (usrBox) {
            usrBox.style.display =
                audSel.value === 'specific_users' ? 'block' : 'none';
        }
    }

	//Collapse all the cards initially
    function collapseAll() {
        document.querySelectorAll('.nb-item').forEach(card => {
            card.classList.remove('open');
        });
    }
	
	//Creation of new card.
    function createEmptyCard() {
        const div = document.createElement('div');
        div.className = 'nb-item';

        div.innerHTML = `
            <input type="hidden" name="notice_id[]" value="0">

            <div class="nb-summary">
                <div>
                    <div class="nb-title">New Notice</div>
                    <div class="nb-meta">Draft</div>
                </div>
                <div class="nb-actions">
                    <button type="button" class="nb-up">↑</button>
                    <button type="button" class="nb-down">↓</button>
                    <button type="button" class="nb-del">✖</button>
                </div>
            </div>

            <div class="nb-body">
                <label>`+notice_title_label+`</label>
                <input name="title[]" required>

                <label>`+notice_description_label+`</label>
                <textarea name="desc[]" rows="2"></textarea>

                <label>`+notice_URL_label+`</label>
                <input type="url" name="url[]">

                <div class="nb-grid">
                    <div>
                        <label>`+notice_from_label+`</label>
                        <input type="datetime-local" name="start[]">
                    </div>
                    <div>
                        <label>`+notice_to_label+`</label>
                        <input type="datetime-local" name="end[]">
                    </div>
                </div>

                <label>`+notice_audience_label+`</label>
                <select name="audience[]" class="audience">
                    <option value="public">`+notice_public_label+`</option>
                    <option value="min_level">`+notice_logged_label+`</option>
                    <option value="specific_users">`+notice_specific_label+`</option>
                </select>

                <div class="aud-min"></div>

				<div class="aud-users">
					<div class="nb-usersearch">
						<input type="text" class="nb-user-handle-search" placeholder="Search username to add to the list...">
						<div class="nb-user-progress" style="display:none"></div>
						<div class="nb-user-results"></div>
					</div>

					<textarea name="specific_users[]" rows="2"
						placeholder="Comma separated user handles">
					</textarea>
				</div>
            </div>
        `;

        initAudience(div);
		setDefaultDates(div);
        return div;
    }

    //Handling Clicks

    document.addEventListener('click', function (e) {
		
		//No notices available. This is the how first notice will be created.
		if (e.target.id === 'nbAdd') {
			e.preventDefault();

			const list = document.getElementById('nbList');
			const card = createEmptyCard();

			list.appendChild(card);
			card.classList.add('open');

			// optional: remove the empty-state button after first add
			e.target.closest('.nb-empty')?.remove();
			return;
		}

        const card = e.target.closest('.nb-item');
        if (!card) return;

        const list = document.getElementById('nbList');
		
		// ADD NOTICE ABOVE/BELOW CURRENT CARD
		if (e.target.classList.contains('nb-add-below') || e.target.classList.contains('nb-add-above')) {
			e.preventDefault();
			e.stopPropagation();

			const list = document.getElementById('nbList');
			const newCard = createEmptyCard();

			// insert immediately after current card
			if(e.target.classList.contains('nb-add-above')){
				list.insertBefore(newCard, card);
			}
			else if (card.nextElementSibling) {
				list.insertBefore(newCard, card.nextElementSibling);
			} else {
				list.appendChild(newCard);
			}

			newCard.classList.add('open');
			return;
		}


        if (e.target.classList.contains('nb-up')) {
            e.preventDefault();
            e.stopPropagation();
            if (card.previousElementSibling) {
                list.insertBefore(card, card.previousElementSibling);
            }
            return;
        }

        if (e.target.classList.contains('nb-down')) {
            e.preventDefault();
            e.stopPropagation();
            if (card.nextElementSibling) {
                list.insertBefore(card.nextElementSibling, card);
            }
            return;
        }

        if (e.target.classList.contains('nb-del')) {
            e.preventDefault();
            e.stopPropagation();
            card.remove();
            return;
        }

        /* SUMMARY TOGGLE */
        if (e.target.closest('.nb-summary')) {
            if (e.target.closest('.nb-actions')) return;
            card.classList.toggle('open');
        }
    });

	//Check Validations after clicking submit button
	document.addEventListener('submit', function (e) {
		if (e.target.id === 'noticeForm') {
			if (!validateAllCards()) {
				e.preventDefault();
			}
		}
	});


    //Audience change
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('audience')) return;
        const card = e.target.closest('.nb-item');
        if (card) initAudience(card);
    });

	//INITIAL STATE to check errors
	window.addEventListener('load', function () {
		collapseAll();
		document.querySelectorAll('.nb-item').forEach(initAudience);
		
		//check any errors exist
		if (!window.NB_SERVER_ERROR){
			return ;
		}

		//Confirm that errors exist
		const err = window.NB_SERVER_ERROR;
		const cards = document.querySelectorAll('.nb-item');
		

		const card = cards[err.index];
		if (!card) return;

		// open the card
		card.classList.add('open');

		// find the relevant input
		let input = null;
		if (err.field === 'title') {
			input = card.querySelector('input[name="title[]"]');
		} else if (err.field === 'start') {
			input = card.querySelector('input[name="start[]"]');
		} else if (err.field === 'end') {
			input = card.querySelector('input[name="end[]"]');
		}

		if (input) {
			input.classList.add('nb-input-error');
			input.focus();

			const msg = document.createElement('div');
			msg.className = 'nb-error-msg';
			msg.textContent = err.message;
			input.after(msg);
		}

		card.scrollIntoView({ behavior: 'smooth', block: 'center' });
	});

	// --- HANDLE AUTOCOMPLETE for Specific Users ---
	document.addEventListener('input', function (e) {
		if (!e.target.classList.contains('nb-user-handle-search')) return;

		const searchBox = e.target;
		const query = searchBox.value.trim();
		const card = searchBox.closest('.nb-item');
		const resultsBox = card.querySelector('.nb-user-results');
		const progressBox = card.querySelector('.nb-user-progress');

		if (!query) {
			resultsBox.innerHTML = '';
			resultsBox.style.display = 'none';
			return;
		}

		progressBox.style.display = 'block';

		const formData = new FormData();
		formData.append("ajax", query);

		fetch("notice-usersearch", { method: "POST", body: formData }) //"notice-usersearch" - is the relative url of the ajax page.
			.then(r => r.text())
			.then(html => {
				progressBox.style.display = 'none';
				resultsBox.innerHTML = html;
				resultsBox.style.display = 'block';

				// each result cell responds to click
				resultsBox.querySelectorAll('.q2apro_usersearch_resultfield').forEach(item => {
					item.addEventListener('click', () => {
						/*
						const handle = item.textContent.trim();
						const textarea = card.querySelector('textarea[name="specific_users[]"]');
						if (!textarea) return;

						// append handle
						const trimmed = textarea.value.trim();
						textarea.value = trimmed ? trimmed + ',' + handle : handle;
						*/
						resultsBox.innerHTML = '';
					});
				});
			});
	});
	
})();


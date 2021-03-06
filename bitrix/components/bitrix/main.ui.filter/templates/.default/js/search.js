;(function() {
	'use strict';

	BX.namespace('BX.Filter');

	/**
	 * Filter search block class
	 * @param parent
	 * @constructor
	 */
	BX.Filter.Search = function(parent)
	{
		this.parent = null;
		this.container = null;
		this.input = null;
		this.preset = null;
		this.buttonsContainer = null;
		this.delay = 800;
		this.timeout = null;
		this.init(parent);
	};
	BX.Filter.Search.prototype = {
		init: function(parent)
		{
			this.parent = parent;
			BX.bind(this.getInput(), 'input', BX.delegate(this._onInputWithoutDebounce, this));

			if (this.parent.getParam('ENABLE_LIVE_SEARCH'))
			{
				BX.bind(this.getInput(), 'input', BX.debounce(this._onInput, this.delay, this));
			}

			BX.bind(this.getInput(), 'keydown', BX.delegate(this._onKeyDown, this));
			BX.bind(this.getFindButton(), 'click', BX.delegate(this._onSearchClick, this));
			BX.bind(this.getContainer(), 'click', BX.delegate(this._onSearchContainerClick, this));
			this.removeAutofocus();
			this.firstInit = true;
		},


		/**
		 * Removes autofocus attr from search input
		 */
		removeAutofocus: function()
		{
			var input = this.getInput();

			if (!!input)
			{
				input.blur();
				input.autofocus = null;
			}
		},


		getFindButton: function()
		{
			if (!BX.type.isDomNode(this.findButton))
			{
				this.findButton = BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classSearchButton);
			}

			return this.findButton;
		},

		_onSearchClick: function()
		{
			this.apply();
		},

		selectSquare: function(square)
		{
			if (!!square && !BX.hasClass(square, 'main-ui-search-square-required'))
			{
				BX.addClass(square, this.parent.settings.classSquareSelected);
			}
		},

		selectSquares: function()
		{
			this.getSquares().forEach(this.selectSquare, this);
		},

		unselectSquare: function(square)
		{
			!!square && BX.removeClass(square, this.parent.settings.classSquareSelected);
		},

		unselectSquares: function()
		{
			this.getSquares().forEach(this.unselectSquare, this);
		},

		removeSquares: function()
		{
			this.getSquares().forEach(this.removeSquare, this);
		},

		isSquaresSelected: function()
		{
			var squares = this.getSquares();
			return squares.length && squares.every(this.isSquareSelected, this);
		},

		isSquareSelected: function(square)
		{
			return !!square && BX.hasClass(square, this.parent.settings.classSquareSelected);
		},

		getLastSquare: function()
		{
			var squares = this.getSquares();
			return !!squares ? squares[squares.length-1] : null;
		},

		isTextSelected: function()
		{
			var searchStringLength = this.getSearchString().length;
			var searchInput = this.getInput();
			var selectionStart = searchInput.selectionStart;
			var selectionEnd = searchInput.selectionEnd;

			return selectionStart === 0 && selectionEnd !== 0 && selectionEnd === searchStringLength;
		},

		isSelectionStart: function()
		{
			var searchInput = this.getInput();
			var selectionStart = searchInput.selectionStart;
			var selectionEnd = searchInput.selectionEnd;

			return selectionStart === 0 && selectionEnd === 0;
		},

		isSquareRemoveButton: function(node)
		{
			return !!node && BX.hasClass(node, this.parent.settings.classSquareDelete);
		},

		isClearButton: function(node)
		{
			return !!node && BX.hasClass(node, this.parent.settings.classClearSearchValueButton);
		},

		isSearchButton: function(node)
		{
			return !!node && BX.hasClass(node, this.parent.settings.classSearchButton);
		},


		/**
		 * Adjust focus on search input
		 */
		adjustFocus: function()
		{
			if (!BX.browser.IsMobile())
			{
				var searchInput = this.getInput();

				if (document.activeElement !== searchInput && window.scrollY < BX.pos(searchInput).top)
				{
					//Puts cursor after last character
					//noinspection SillyAssignmentJS
					searchInput.value = searchInput.value;
					searchInput.blur();
					searchInput.focus();
				}
			}
		},

		findSquareByChild: function(childNode)
		{
			return BX.findParent(childNode, {className: this.parent.settings.classSquare}, true, false);
		},

		/**
		 * @param {HTMLElement} square
		 */
		getSquareData: function(square)
		{
			var rawData = BX.data(square, 'item');
			return !!square && !!rawData ? JSON.parse(rawData) : null;
		},

		/**
		 * @param {HTMLElement} square
		 * @return {boolean}
		 */
		isSquareControl: function(square)
		{
			var squareData = this.getSquareData(square);
			return !!squareData && (squareData.type === 'control' || BX.type.isArray(squareData));
		},

		onPresetSquareRemove: function()
		{
			var Filter = this.parent;
			var Preset = Filter.getPreset();
			var currentPresetId = Preset.getCurrentPresetId();
			var isResetToDefaultMode = Filter.getParam('RESET_TO_DEFAULT_MODE');
			var isPinned = Preset.isPinned(currentPresetId);
			var squares = this.getSquares();

			if (squares.length === 1)
			{
				if ((isResetToDefaultMode && isPinned) || !isResetToDefaultMode)
				{
					var resetWithoutSearch = true;
					this.lastPromise = Filter.resetFilter(resetWithoutSearch);
					Filter.closePopup();
				}

				if (isResetToDefaultMode && !isPinned)
				{
					this.lastPromise = Filter.getPreset().applyPinnedPreset();
				}
			}

			if (squares.length > 1)
			{
				var currentPreset = Preset.getPreset(Preset.getCurrentPresetId());
				var tmpPreset = Preset.getPreset('tmp_filter');

				tmpPreset.FIELDS = BX.clone(currentPreset.ADDITIONAL);
				currentPreset.ADDITIONAL = [];
				Preset.deactivateAllPresets();
				Preset.applyPreset('tmp_filter');
				Filter.applyFilter();
			}
		},

		onControlSquareRemove: function(square)
		{
			var Filter = this.parent;
			var Preset = Filter.getPreset();
			var isResetToDefaultMode = Filter.getParam('RESET_TO_DEFAULT_MODE');

			if (isResetToDefaultMode && this.getSquares().length === 1)
			{
				this.lastPromise = Filter.getPreset().applyPinnedPreset();
			}
			else
			{
				var squareData = this.getSquareData(square);
				Filter.clearControls(squareData);
				Filter.closePopup();

				if (BX.type.isArray(squareData))
				{
					squareData.forEach(function(square) {
						Preset.removeAdditionalField(square.name);
					});
				}

				if (BX.type.isPlainObject(squareData))
				{
					Preset.removeAdditionalField(squareData.name);
				}

				this.apply();
			}
		},

		onValueRequiredSquareRemove: function()
		{
			var Filter = this.parent;
			Filter.getPreset().deactivateAllPresets();
			Filter.showPopup();
			this.adjustPlaceholder();
		},

		/**
		 * @param {HTMLElement} square
		 */
		complexSquareRemove: function(square)
		{
			var isValueRequiredMode = this.parent.getParam('VALUE_REQUIRED_MODE');
			var isPresetSquare = !this.isSquareControl(square);

			if (isValueRequiredMode)
			{
				this.onValueRequiredSquareRemove();
			}
			else
			{
				if (isPresetSquare)
				{
					this.onPresetSquareRemove();
				}
				else
				{
					this.onControlSquareRemove(square);
				}
			}

			this.removeSquare(square);
			this.adjustClearButton();
		},

		adjustClearButton: function()
		{
			!!this.getLastSquare() ? this.showClearButton() : this.hideClearButton();
		},

		/**
		 * @param {HTMLElement} square
		 */
		removeSquare: function(square)
		{
			!!square && BX.remove(square);
		},

		_onSearchContainerClick: function(event)
		{
			var Filter = this.parent;

			if (this.isClearButton(event.target))
			{
				if (!Filter.getParam('VALUE_REQUIRED_MODE'))
				{
					if (Filter.getParam('RESET_TO_DEFAULT_MODE'))
					{
						this.clearInput();
						this.lastPromise = Filter.getPreset().applyPinnedPreset();
					}
					else
					{
						Filter.resetFilter();
					}

					Filter.closePopup();
					this.adjustFocus();
				}
				else
				{
					this.removeSquares();
					Filter.showPopup();
					this.adjustPlaceholder();
					this.hideClearButton();
					Filter.getPreset().deactivateAllPresets();
				}
			}

			else if (this.isSearchButton(event.target))
			{
				this.apply();
				this.adjustFocus();
			}

			else if (this.isSquareRemoveButton(event.target))
			{
				var square = this.findSquareByChild(event.target);
				this.complexSquareRemove(square);
				this.adjustFocus();
			}

			else
			{
				if (!Filter.getPopup().isShown())
				{
					Filter.showPopup();
				}
				else
				{
					var input = this.getInput();
					var start = input.selectionStart;
					var end = input.selectionEnd;
					var searchLength = this.getSearchString().length;

					if (!(searchLength && start === 0 && end === searchLength))
					{
						Filter.closePopup();

						if (Filter.getParam('VALUE_REQUIRED_MODE'))
						{
							Filter.restoreRemovedPreset();
						}
					}
				}
			}
		},

		_onKeyDown: function(event)
		{
			var utils = BX.Filter.Utils;
			var parent = this.parent;

			if (utils.isKey(event, 'enter'))
			{
				this.apply();

				this.firstInit = false;
				this.lastSearchString = this.getSearchString();

				parent.closePopup();
			}

			if (utils.isKey(event, 'tab') || utils.isKey(event, 'downArrow'))
			{
				parent.showPopup();
				parent.adjustFocus();
				this.unselectSquares();
			}

			if (utils.isKey(event, 'upArrow'))
			{
				parent.closePopup();
				parent.getParam('VALUE_REQUIRED_MODE') && this.parent.restoreRemovedPreset();
			}

			if (utils.isKey(event, 'a') && event.metaKey || utils.isKey(event, 'a') && event.ctrlKey)
			{
				this.selectSquares();
			}

			if (utils.isKey(event, 'backspace') && this.isTextSelected() && this.isSquaresSelected())
			{
				clearTimeout(this.timeout);

				if (this.parent.getParam('RESET_TO_DEFAULT_MODE'))
				{
					this.lastPromise = this.parent.getPreset().applyPinnedPreset();
				}
				else
				{
					this.lastPromise = this.parent.resetFilter();
				}

				this.parent.closePopup()
			}

			if (utils.isKey(event, 'backspace') && this.isSelectionStart())
			{
				clearTimeout(this.timeout);
				var square = this.getLastSquare();

				if (!BX.hasClass(square, 'main-ui-search-square-required'))
				{
					if (this.isSquareSelected(square))
					{
						this.complexSquareRemove(square);
					}
					else
					{
						this.selectSquare(square)
					}
				}
			}

			if (!utils.isKey(event, 'backspace') && !event.metaKey && this.isSquaresSelected())
			{
				this.unselectSquares();
			}
		},

		getSearchString: function()
		{
			var input = this.getInput();
			return !!input ? input.value : '';
		},

		getSquares: function()
		{
			return BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classSquare, true);
		},

		adjustPlaceholder: function()
		{
			this.setInputPlaceholder(this.parent.getParam('MAIN_UI_FILTER__PLACEHOLDER' + (this.parent.getParam("DISABLE_SEARCH") || !this.parent.settings.get('SEARCH') ? '' : '_DEFAULT')));
		},

		isResolvedRequest: function()
		{
			return !this.lastPromise || !!this.lastPromise && this.lastPromise.state;
		},

		/**
		 * Calls BX.Main.Filter.applyFilter
		 * @return {BX.Promise}
		 */
		apply: function()
		{
			if (this.isResolvedRequest())
			{
				this.lastPromise = this.parent._onFindButtonClick();
			}

			return this.lastPromise;
		},

		/**
		 * Calls BX.Main.Filter.resetFilter()
		 * @return {BX.Promise}
		 */
		reset: function()
		{
			if (this.isResolvedRequest())
			{
				this.parent.getSearch().removePreset();
				this.parent.getPreset().deactivateAllPresets();
				this.parent.getPreset().resetPreset(true);

				this.timeout = setTimeout(BX.delegate(function() {
					this.lastPromise = this.parent.resetFilter();
				}, this), this.delay);
			}

			return this.lastPromise;
		},

		_onInputWithoutDebounce: function()
		{
			clearTimeout(this.timeout);

			var searchString = this.getSearchString();
			this.lastSearchString = !!this.lastSearchString ? this.lastSearchString : searchString;

			if (searchString !== this.lastSearchString &&
				(!this.parent.isIe() || !this.firstInit))
			{
				if (this.parent.getParam('ENABLE_LIVE_SEARCH'))
				{
					this.parent.showGridAnimation();
					BX.onCustomEvent(window, 'BX.Filter.Search:input', [this.parent.params.FILTER_ID, searchString]);
				}

				this.parent.getPopup().isShown() && this.parent.closePopup();
			}

			if (searchString)
			{
				this.showClearButton();
			}
			else
			{
				if (!this.getSquares().length && this.lastSearchString !== searchString)
				{
					this.hideClearButton();
					this.adjustPlaceholder();
				}
			}
		},

		_onInput: function()
		{
			var searchString = this.getSearchString();

			if (searchString !== this.lastSearchString && (!this.parent.isIe() || !this.firstInit))
			{
				this.apply();
			}

			this.firstInit = false;
			this.lastSearchString = searchString;
		},

		getButtonsContainer: function()
		{
			if (!BX.type.isDomNode(this.buttonsContainer))
			{
				this.buttonsContainer = BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classSearchButtonsContainer);
			}

			return this.buttonsContainer;
		},

		showClearButton: function()
		{
			BX.addClass(this.getButtonsContainer(), this.parent.settings.classShow);
		},

		hideClearButton: function()
		{
			BX.removeClass(this.getButtonsContainer(), this.parent.settings.classShow);
		},

		getInput: function()
		{
			var inputId;

			if (!BX.type.isDomNode(this.input))
			{
				inputId = [this.parent.getParam('FILTER_ID', ''), '_search'].join('');
				this.input = BX(inputId);
			}

			return this.input;
		},

		getContainer: function()
		{
			var containerId;

			if (!BX.type.isDomNode(this.container))
			{
				containerId = [this.parent.getParam('FILTER_ID'), '_search_container'].join('');
				this.container = BX(containerId);
			}

			return this.container;
		},

		setInputPlaceholder: function(text)
		{
			var input = this.getInput();
			input.placeholder = text;
		},

		clearInput: function()
		{
			var form = this.getInput();

			if (BX.type.isDomNode(form))
			{
				form.value = null;
			}
		},

		clearForm: function()
		{
			this.clearInput();
			this.removePreset();
		},

		makeSquares: function(squaresData, depth, additional)
		{
			var square;
			var tmpSquare = null;
			var container = this.getContainer();
			var result = {squares: [], moreSquares: []};

			squaresData.forEach(function(current, index) {
				if (index < depth)
				{
					square = BX.decl(current);
					tmpSquare = tmpSquare || square;

					if (!additional)
					{
						if (index === 0)
						{
							BX.prepend(square, container);
						}
						else
						{
							BX.insertAfter(square, tmpSquare);
						}
					}
					else
					{
						var lastSquare = BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classSquare);
						if (lastSquare)
						{
							BX.insertAfter(square, lastSquare);
						}
						else
						{
							BX.prepend(square, container);
						}
					}

					tmpSquare = square;
					result.squares.push(square);
				}
				else
				{
					result.moreSquares.push({type: 'control', name: current.value, title: current.title});
				}
			}, this);

			return result;
		},

		squares: function(fields, depth, additional)
		{
			var squaresData, moreSquares, square, squaresWidth, result;
			var squares = BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classSquare, true);

			if (additional)
			{
				squares.forEach(function(current) {
					var item = BX.data(current, 'item');

					if (item)
					{
						BX.remove(current);
					}
				});
			}
			else
			{
				squares.forEach(BX.remove);
			}

			squaresData = this.prepareSquaresData(fields);
			moreSquares = this.makeSquares(squaresData, depth, additional);
			squaresWidth = 0;
			result = {squaresData: squaresData, width: 0};

			if (moreSquares.moreSquares.length)
			{
				square = {
					block: 'main-ui-search-square',
					name: this.parent.getParam('MAIN_UI_FILTER__AND') + ' ' + this.parent.getParam('MAIN_UI_FILTER__MORE') + ' ' + moreSquares.moreSquares.length,
					item: moreSquares.moreSquares,
					title: moreSquares.moreSquares.map(function(curr) { return curr.title; }).join(', \n')
				};

				square = BX.decl(square);
				moreSquares.squares.push(square);
				BX.insertAfter(square, moreSquares.squares[moreSquares.squares.length-2]);

				squaresWidth = moreSquares.squares.reduce(function(prev, curr) {
					return prev + BX.width(curr) + (parseFloat(BX.style(curr, 'margin-right')) || 0);
				}, 0);
			}

			result.width = squaresWidth;

			return result;
		},

		setPreset: function(presetData)
		{
			var container = this.getContainer();
			var square, squares;
			var squaresResult;

			if (BX.type.isPlainObject(presetData))
			{
				squares = BX.Filter.Utils.getByClass(container, this.parent.settings.classSquare, true);
				squares.forEach(BX.remove);

				presetData = BX.clone(presetData);
				presetData.ADDITIONAL = presetData.ADDITIONAL || [];
				BX.onCustomEvent(window, 'BX.Filter.Search:beforeSquaresUpdate', [presetData, this]);

				if (presetData.ID !== 'default_filter' && presetData.ID !== 'tmp_filter')
				{
					square = BX.decl({
						block: 'main-ui-search-square',
						name: presetData.TITLE,
						value: presetData.ID,
						isPreset: true
					});

					var requiredSquires = presetData.FIELDS.filter(function(field) {
						return field.REQUIRED === true;
					});

					if (requiredSquires.length)
					{
						this.squares(requiredSquires, 1);
						BX.insertAfter(square, this.getLastSquare());
					}
					else
					{
						BX.prepend(square, container);
					}

					if ('ADDITIONAL' in presetData && BX.type.isArray(presetData.ADDITIONAL) && presetData.ADDITIONAL.length)
					{
						squaresResult = this.squares(presetData.ADDITIONAL, 1, true);

						if ((BX.width(container) - squaresResult.width) < 100)
						{
							squaresResult = this.squares(presetData.ADDITIONAL, 0, true);
						}
					}
				}
				else
				{
					if ('ADDITIONAL' in presetData && BX.type.isArray(presetData.ADDITIONAL) && presetData.ADDITIONAL.length)
					{
						presetData.ADDITIONAL.forEach(function(current, index) {
							if (!('ID' in current))
							{
								current.ID = 'ADDITIONAL_ID_'+index;
							}

							if (!('NAME' in current))
							{
								current.NAME = 'ADDITIONAL_NAME_'+index;
							}

							if (!('TYPE' in current))
							{
								current.TYPE = 'STRING';
							}

							if ('LABEL' in current && 'LABEL' in current)
							{
								presetData.FIELDS.push(current);
							}
						});
					}

					if (BX.type.isArray(presetData.FIELDS) && presetData.FIELDS.length)
					{
						squaresResult = this.squares(presetData.FIELDS, 2);

						if ((BX.width(container) - squaresResult.width) < 100)
						{
							squaresResult = this.squares(presetData.FIELDS, 1);
						}
					}
				}

				if (squaresResult && BX.type.isArray(squaresResult.squaresData) && squaresResult.squaresData.length || (presetData.ID !== 'default_filter' && presetData.ID !== 'tmp_filter'))
				{
					this.setInputPlaceholder(this.parent.getParam('MAIN_UI_FILTER__PLACEHOLDER_WITH_FILTER'));
					this.showClearButton();
				}
				else
				{
					if (this.parent.getParam("DISABLE_SEARCH") || !this.parent.settings.get('SEARCH'))
					{
						this.setInputPlaceholder(this.parent.getParam('MAIN_UI_FILTER__PLACEHOLDER'));
					}
					else
					{
						this.setInputPlaceholder(this.parent.getParam('MAIN_UI_FILTER__PLACEHOLDER_DEFAULT'));
					}
				}

				if (BX.type.isNotEmptyString(this.parent.getSearch().getInput().value))
				{
					this.showClearButton();
				}
			}
		},

		prepareSquaresData: function(fields)
		{
			var value, tmpValues, title, control;
			var result = [];

			fields.map(function(current) {
				value = null;

				switch (current.TYPE)
				{
					case this.parent.types.DATE : {
						value = current.LABEL + ': ' + current.SUB_TYPE.NAME;

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.QUARTER &&
							BX.type.isNotEmptyString(current.VALUES._quarter))
						{
							var quarter = current.QUARTERS.filter(function(curr) {
								return curr.VALUE == current.VALUES._quarter;
							}).map(function(curr) {
								return curr.NAME;
							});

							quarter = quarter.length ? quarter.join('') : '';

							value = current.LABEL + ': ' + quarter + ' ' +
								this.parent.getParam('MAIN_UI_FILTER__QUARTER').toLocaleLowerCase() + ' ' +
								current.VALUES._year;
						}

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.YEAR &&
							BX.type.isNotEmptyString(current.VALUES._year))
						{
							value = current.LABEL + ': ' + current.VALUES._year;
						}

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.MONTH &&
							BX.type.isNotEmptyString(current.VALUES._month))
						{
							var month = current.MONTHS.filter(function(curr) {
								return curr.VALUE == current.VALUES._month;
							}).map(function(curr) {
								return curr.NAME;
							});

							month = month.length ? month.join('') : '';

							value = current.LABEL + ': ' + month + ' ' + current.VALUES._year;
						}

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.EXACT &&
							BX.type.isNotEmptyString(current.VALUES._from))
						{
							value = current.LABEL + ': ' + current.VALUES._from;
						}

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.RANGE)
						{
							if (BX.type.isNotEmptyString(current.VALUES._from) && BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': ' + current.VALUES._from + '-' + current.VALUES._to;
							}
							else if (!BX.type.isNotEmptyString(current.VALUES._from) && BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': ' + this.parent.getParam('MAIN_UI_FILTER__BEFORE') + ' ' + current.VALUES._to;
							}
							else if (BX.type.isNotEmptyString(current.VALUES._from) && !BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': ' + this.parent.getParam('MAIN_UI_FILTER__AFTER') + ' ' + current.VALUES._from;
							}
						}


						if ((current.SUB_TYPE.VALUE === this.parent.dateTypes.NEXT_DAYS ||
							current.SUB_TYPE.VALUE === this.parent.dateTypes.PREV_DAYS) &&
							!BX.type.isNumber(parseInt(current.VALUES._days)))
						{
							value = null;
						}

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.NEXT_DAYS &&
							BX.type.isNumber(parseInt(current.VALUES._days)))
						{
							value = current.LABEL + ': ' + this.parent.getParam('MAIN_UI_FILTER__DATE_NEXT_DAYS_LABEL').replace('#N#', current.VALUES._days);
						}

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.PREV_DAYS &&
							BX.type.isNumber(parseInt(current.VALUES._days)))
						{
							value = current.LABEL + ': ' + this.parent.getParam('MAIN_UI_FILTER__DATE_PREV_DAYS_LABEL').replace('#N#', current.VALUES._days);
						}

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.NONE)
						{
							value = null;
						}

						break;
					}

					case this.parent.types.CUSTOM_DATE : {
						if (
							(BX.type.isArray(current.VALUE.days) && current.VALUE.days.length) ||
							(BX.type.isArray(current.VALUE.months) && current.VALUE.months.length) ||
							(BX.type.isArray(current.VALUE.years) && current.VALUE.years.length)
						)
						{
							value = current.LABEL;
						}
						break;
					}

					case this.parent.types.SELECT : {
						if ((BX.type.isPlainObject(current.VALUE) && current.VALUE.VALUE) || current.REQUIRED)
						{
							value = current.LABEL + ': ' + current.VALUE.NAME;
						}
						break;
					}

					case this.parent.types.MULTI_SELECT : {
						if ((BX.type.isArray(current.VALUE) && current.VALUE.length) || current.REQUIRED)
						{
							tmpValues = [];
							value = current.LABEL + ': ';
							current.VALUE.forEach(function(val, index) {
								if (index < 2)
								{
									tmpValues.push(val.NAME);
								}
							});

							value += tmpValues.join(', ');

							if (current.VALUE.length > 2)
							{
								title = [];

								current.VALUE.forEach(function(val) {
									title.push(val.NAME);
								});

								value = title.join(', ');
							}
						}
						break;
					}

					case this.parent.types.NUMBER : {
						if (current.SUB_TYPE.VALUE === 'exact')
						{
							if (BX.type.isNotEmptyString(current.VALUES._from))
							{
								value = current.LABEL + ': ' + current.VALUES._from;
							}
							else
							{
								value = null;
							}
						}

						if (current.SUB_TYPE.VALUE === 'range')
						{
							if (BX.type.isNotEmptyString(current.VALUES._from) && BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': ' + current.VALUES._from + '-' + current.VALUES._to;
							}
							else if (!BX.type.isNotEmptyString(current.VALUES._from) && BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': ' + this.parent.getParam('MAIN_UI_FILTER__NUMBER_LESS') + ' ' + current.VALUES._to;
							}
							else if (BX.type.isNotEmptyString(current.VALUES._from) && !BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': ' + this.parent.getParam('MAIN_UI_FILTER__NUMBER_MORE') + ' ' + current.VALUES._from;
							}
							else
							{
								value = null;
							}
						}

						if (current.SUB_TYPE.VALUE === 'more')
						{
							if (BX.type.isNotEmptyString(current.VALUES._from))
							{
								value = current.LABEL + ': > ';
								value += current.VALUES._from;
							}
						}

						if (current.SUB_TYPE.VALUE === 'less')
						{
							if (BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': < ';
								value += current.VALUES._to;
							}
						}
						break;
					}

					case this.parent.types.CUSTOM_ENTITY : {
						if (current.MULTIPLE)
						{
							var label = !!current.VALUES._label ? current.VALUES._label : [];

							if (BX.type.isPlainObject(label))
							{
								label = Object.keys(label).map(function(key) {
									return label[key];
								});
							}

							if (!BX.type.isArray(label))
							{
								label = [ label ];
							}

							if (label.length > 0)
							{
								value = current.LABEL + ': ';
								value += label.join(', ');
							}
						}
						else
						{
							if (BX.type.isNotEmptyString(current.VALUES._value) &&
								BX.type.isNotEmptyString(current.VALUES._label))
							{
								value = current.LABEL + ': ';
								value += current.VALUES._label;
							}
						}
						break;
					}

					case this.parent.types.CUSTOM : {
						value = '_VALUE' in current && BX.type.isNotEmptyString(current._VALUE) ? current.LABEL : null;
						break;
					}

					default : {
						if (BX.type.isNotEmptyString(current.VALUE))
						{
							value = current.LABEL + ': ' + current.VALUE;
						}
						break;
					}
				}

				if (value !== null)
				{
					result.push({
						block: 'main-ui-search-square',
						required: current.REQUIRED,
						name: value,
						value: current.NAME,
						item: {type: 'control', name: current.NAME},
						title: value
					});
				}
			}, this);

			return result;
		},

		getPreset: function()
		{
			var container = this.getContainer();
			var presetClass = this.parent.settings.classSquare;
			var preset = null;

			if (BX.type.isDomNode(container))
			{
				preset = BX.Filter.Utils.getByClass(container,presetClass);
			}

			return preset;
		},

		removePreset: function()
		{
			var preset = this.getPreset();

			if (BX.type.isDomNode(preset))
			{
				BX.remove(preset);

				if (this.parent.getParam("DISABLE_SEARCH") || !this.parent.settings.get('SEARCH'))
				{
					this.setInputPlaceholder(this.parent.getParam('MAIN_UI_FILTER__PLACEHOLDER'));
				}
				else
				{
					this.setInputPlaceholder(this.parent.getParam('MAIN_UI_FILTER__PLACEHOLDER_DEFAULT'));
				}
			}

			this.hideClearButton();
		},

		updatePreset: function(presetData)
		{
			this.removePreset();
			this.setPreset(presetData);
		}
	};

})();
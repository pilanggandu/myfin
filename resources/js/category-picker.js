const REGISTRY = new WeakMap();
const PICKER_SELECTOR = 'select[data-category-picker="true"], select[data-searchable-picker="true"]';

function parseRecentIds(selectEl) {
    try {
        const raw = selectEl.getAttribute('data-recent-ids');
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed.map(String) : [];
    } catch {
        return [];
    }
}

function buildOptionItem(opt, groupLabel = '') {
    const label = opt.textContent.trim();

    return {
        value: String(opt.value),
        label,
        chipLabel: (opt.getAttribute('data-chip-label') || label).trim(),
        searchText: (opt.getAttribute('data-search-text') || label).trim(),
        rawName: (opt.getAttribute('data-name') || opt.getAttribute('data-chip-label') || label).trim(),
        group: groupLabel,
        disabled: opt.disabled,
    };
}

function parseSelectData(selectEl) {
    const items = [];
    let emptyOption = null;

    Array.from(selectEl.children).forEach(child => {
        if (child.hidden) {
            return;
        }
        if (child.tagName === 'OPTGROUP') {
            const groupLabel = child.getAttribute('label') || '';
            Array.from(child.children).forEach(opt => {
                if (opt.tagName === 'OPTION' && !opt.hidden) {
                    items.push(buildOptionItem(opt, groupLabel));
                }
            });
        } else if (child.tagName === 'OPTION' && !child.hidden) {
            if (child.value === '') {
                emptyOption = {
                    value: '',
                    label: child.textContent.trim(),
                    chipLabel: child.textContent.trim(),
                    searchText: child.textContent.trim(),
                    rawName: child.textContent.trim(),
                    group: '',
                    disabled: child.disabled,
                };
            } else {
                items.push(buildOptionItem(child));
            }
        }
    });

    return { items, emptyOption };
}

function createSearchIcon() {
    return `
        <svg class="w-3.5 h-3.5 shrink-0 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
    `;
}

function createClockIcon() {
    return `
        <svg class="w-3.5 h-3.5 shrink-0 text-forest dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
    `;
}

function createCheckIcon() {
    return `
        <svg class="w-3.5 h-3.5 shrink-0 text-forest dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
        </svg>
    `;
}

export function enhanceSearchableSelect(selectEl) {
    if (!selectEl || selectEl.dataset.searchablePickerInitialized || selectEl.dataset.categoryPickerInitialized) {
        return;
    }
    selectEl.dataset.searchablePickerInitialized = 'true';
    selectEl.dataset.categoryPickerInitialized = 'true';

    const allowEmpty = selectEl.getAttribute('data-allow-empty') === 'true';
    const showRecentChips = selectEl.getAttribute('data-show-recent-chips') === 'true';
    const recentScope = selectEl.getAttribute('data-recent-scope') || '';
    const pickerKind = selectEl.getAttribute('data-picker-kind') || 'category';
    const itemNoun = selectEl.getAttribute('data-picker-noun') || (pickerKind === 'account' ? 'rekening' : 'kategori');
    const itemTitleNoun = itemNoun.charAt(0).toUpperCase() + itemNoun.slice(1);
    const allSectionLabel = selectEl.getAttribute('data-all-label') || `Semua ${itemTitleNoun}`;
    const isCompact = selectEl.classList.contains('h-9');
    const searchPlaceholder = selectEl.getAttribute('data-search-placeholder') || `Cari ${itemNoun}...`;

    let recentIds = parseRecentIds(selectEl);
    let activeIndex = -1;
    let isOpen = false;
    let searchQuery = '';

    const wrapper = document.createElement('div');
    wrapper.className = 'arus-category-picker relative w-full';
    selectEl.parentNode.insertBefore(wrapper, selectEl);
    wrapper.appendChild(selectEl);

    selectEl.style.position = 'absolute';
    selectEl.style.opacity = '0';
    selectEl.style.width = '1px';
    selectEl.style.height = '1px';
    selectEl.style.minWidth = '0';
    selectEl.style.padding = '0';
    selectEl.style.border = '0';
    selectEl.style.overflow = 'hidden';
    selectEl.style.pointerEvents = 'none';
    selectEl.style.zIndex = '-1';
    selectEl.tabIndex = -1;
    selectEl.setAttribute('aria-hidden', 'true');

    const triggerRow = document.createElement('div');
    triggerRow.className = 'relative w-full';

    const triggerBtn = document.createElement('button');
    triggerBtn.type = 'button';
    triggerBtn.className = [
        'arus-category-trigger',
        'w-full flex items-center justify-between gap-2 text-left transition cursor-pointer',
        isCompact ? 'min-h-[44px] sm:min-h-0 sm:h-9 px-3 text-xs' : 'min-h-[44px] sm:min-h-[38px] px-3 py-1.5 text-[13px]',
        selectEl.classList.contains('text-xs') ? 'text-xs' : '',
        selectEl.classList.contains('has-error') ? 'has-error' : '',
    ].filter(Boolean).join(' ');
    triggerBtn.setAttribute('aria-haspopup', 'listbox');
    triggerBtn.setAttribute('aria-expanded', 'false');

    const triggerLabel = document.createElement('span');
    triggerLabel.className = 'truncate flex-1';

    const triggerActions = document.createElement('span');
    triggerActions.className = 'flex items-center gap-1 shrink-0';

    const clearValueBtn = document.createElement('button');
    clearValueBtn.type = 'button';
    clearValueBtn.className = 'hidden w-8 h-8 sm:w-5 sm:h-5 rounded items-center justify-center text-muted hover:text-charcoal hover:bg-cream transition cursor-pointer';
    clearValueBtn.setAttribute('aria-label', `Hapus pilihan ${itemNoun}`);
    clearValueBtn.title = `Hapus pilihan ${itemNoun}`;
    clearValueBtn.innerHTML = '&times;';

    const chevron = document.createElement('span');
    chevron.className = 'text-muted transition-transform duration-150 inline-flex items-center';
    chevron.innerHTML = `
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 20 20" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 8l4 4 4-4" />
        </svg>
    `;

    triggerBtn.appendChild(triggerLabel);
    triggerBtn.appendChild(triggerActions);
    triggerActions.appendChild(chevron);

    triggerRow.appendChild(triggerBtn);
    if (allowEmpty) {
        clearValueBtn.classList.add('absolute', 'right-8', 'top-1/2', '-translate-y-1/2', 'z-10');
        triggerRow.appendChild(clearValueBtn);
    }
    wrapper.appendChild(triggerRow);

    const chipsContainer = document.createElement('div');
    chipsContainer.className = 'mt-2 hidden';
    wrapper.appendChild(chipsContainer);

    const popover = document.createElement('div');
    popover.className = 'arus-category-popover fixed z-[70] bg-panel border border-line rounded-xl shadow-2xl overflow-hidden hidden text-charcoal';
    popover.style.display = 'none';

    const popoverId = `arus-cat-listbox-${Math.random().toString(36).slice(2, 9)}`;
    triggerBtn.setAttribute('aria-controls', popoverId);

    const searchWrap = document.createElement('div');
    searchWrap.className = 'p-2 border-b border-line bg-sidebar/40';

    const searchInputBox = document.createElement('div');
    searchInputBox.className = 'relative flex items-center';

    const searchIconWrap = document.createElement('span');
    searchIconWrap.className = 'absolute left-2.5 pointer-events-none flex items-center';
    searchIconWrap.innerHTML = createSearchIcon();

    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = searchPlaceholder;
    searchInput.autocomplete = 'off';
    searchInput.setAttribute('aria-label', searchPlaceholder);
    searchInput.className = 'field-input h-10 sm:h-8.5 w-full text-xs !pl-8 !pr-9 sm:!pr-7 bg-panel';

    const clearSearchBtn = document.createElement('button');
    clearSearchBtn.type = 'button';
    clearSearchBtn.className = 'hidden absolute right-1 w-8 h-8 sm:w-6 sm:h-6 rounded flex items-center justify-center text-muted hover:text-charcoal hover:bg-cream cursor-pointer text-sm leading-none';
    clearSearchBtn.setAttribute('aria-label', `Hapus kata kunci ${itemNoun}`);
    clearSearchBtn.innerHTML = '&times;';

    searchInputBox.appendChild(searchIconWrap);
    searchInputBox.appendChild(searchInput);
    searchInputBox.appendChild(clearSearchBtn);
    searchWrap.appendChild(searchInputBox);
    popover.appendChild(searchWrap);

    const listEl = document.createElement('div');
    listEl.id = popoverId;
    listEl.setAttribute('role', 'listbox');
    listEl.className = 'max-h-[248px] overflow-y-auto overscroll-contain p-1.5 space-y-0.5';
    popover.appendChild(listEl);

    document.body.appendChild(popover);

    const valueDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
    const selectedIndexDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');
    const disabledDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'disabled');

    function getSelectValue() {
        return valueDesc?.get ? String(valueDesc.get.call(selectEl)) : String(selectEl.getAttribute('value') || '');
    }

    function setSelectValue(val, emitChange = true) {
        if (valueDesc?.set) {
            valueDesc.set.call(selectEl, val);
        } else {
            selectEl.value = val;
        }
        syncFromSelect();
        if (emitChange) {
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function isSelectDisabled() {
        return disabledDesc?.get ? Boolean(disabledDesc.get.call(selectEl)) : Boolean(selectEl.disabled);
    }

    Object.defineProperty(selectEl, 'value', {
        get() {
            return getSelectValue();
        },
        set(val) {
            if (valueDesc?.set) {
                valueDesc.set.call(selectEl, val);
            }
            syncFromSelect();
        },
        configurable: true,
    });

    Object.defineProperty(selectEl, 'selectedIndex', {
        get() {
            return selectedIndexDesc?.get ? selectedIndexDesc.get.call(selectEl) : -1;
        },
        set(idx) {
            if (selectedIndexDesc?.set) {
                selectedIndexDesc.set.call(selectEl, idx);
            }
            syncFromSelect();
        },
        configurable: true,
    });

    Object.defineProperty(selectEl, 'disabled', {
        get() {
            return isSelectDisabled();
        },
        set(val) {
            if (disabledDesc?.set) {
                disabledDesc.set.call(selectEl, val);
            }
            syncDisabledState();
        },
        configurable: true,
    });

    selectEl.focus = function () {
        triggerBtn.focus();
    };

    function syncDisabledState() {
        const disabled = isSelectDisabled();
        triggerBtn.disabled = disabled;
        triggerBtn.classList.toggle('opacity-60', disabled);
        triggerBtn.classList.toggle('cursor-not-allowed', disabled);
        triggerBtn.classList.toggle('cursor-pointer', !disabled);
        chipsContainer.querySelectorAll('button').forEach(btn => {
            btn.disabled = disabled;
        });
        if (disabled && isOpen) {
            closePopover(false);
        }
    }

    function renderChips() {
        if (!showRecentChips) {
            chipsContainer.classList.add('hidden');
            chipsContainer.innerHTML = '';
            return;
        }

        const { items } = parseSelectData(selectEl);
        const byValue = new Map(items.map(item => [item.value, item]));
        const recentItems = recentIds
            .map(id => byValue.get(String(id)))
            .filter(item => item && !item.disabled)
            .slice(0, 5);

        if (recentItems.length === 0) {
            chipsContainer.classList.add('hidden');
            chipsContainer.innerHTML = '';
            return;
        }

        const currentValue = getSelectValue();
        const isDisabled = isSelectDisabled();

        chipsContainer.classList.remove('hidden');
        chipsContainer.innerHTML = '';

        const labelRow = document.createElement('div');
        labelRow.className = 'text-[10.5px] font-semibold text-muted mb-1.5';
        labelRow.textContent = 'Terakhir dipakai:';
        chipsContainer.appendChild(labelRow);

        const row = document.createElement('div');
        row.className = 'flex items-center flex-wrap gap-1.5';

        recentItems.forEach(item => {
            const isSelected = currentValue === item.value;
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.disabled = isDisabled;
            chip.dataset.recentCategoryId = item.value;
            chip.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
            chip.className = [
                'min-h-[44px] sm:min-h-[28px] px-3 sm:px-2.5 py-2 sm:py-1 rounded-lg text-xs border transition cursor-pointer inline-flex items-center gap-1',
                isSelected
                    ? 'bg-forest-light border-forest text-forest dark:text-blue-300 font-bold'
                    : 'bg-panel border-line text-charcoal font-medium hover:bg-cream hover:border-sage',
            ].join(' ');
            chip.textContent = item.chipLabel;
            chip.title = item.label;

            chip.addEventListener('click', () => {
                setSelectValue(item.value, true);
                if (isOpen) {
                    closePopover(false);
                }
            });

            row.appendChild(chip);
        });

        chipsContainer.appendChild(row);
    }

    function syncFromSelect() {
        const { items, emptyOption } = parseSelectData(selectEl);
        const currentValue = getSelectValue();
        const currentItem = items.find(item => item.value === currentValue);

        if (currentItem) {
            triggerLabel.textContent = currentItem.label;
            triggerLabel.className = 'truncate flex-1 font-medium text-charcoal';
        } else {
            triggerLabel.textContent = emptyOption?.label || `Pilih ${itemTitleNoun}...`;
            triggerLabel.className = 'truncate flex-1 text-[#526070] dark:text-[#94A3B8]';
        }

        if (allowEmpty) {
            const hasSelection = currentValue !== '';
            clearValueBtn.classList.toggle('hidden', !hasSelection);
            clearValueBtn.classList.toggle('inline-flex', hasSelection);
            triggerBtn.classList.toggle('!pr-16', hasSelection);
            triggerBtn.classList.toggle('sm:!pr-14', hasSelection);
        }

        renderChips();
        if (isOpen) {
            renderList();
        }
    }

    function createOptionRow(item, index, currentValue, { showGroupTag = false, isRecentBadge = false } = {}) {
        const isSelected = currentValue === item.value;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('role', 'option');
        btn.setAttribute('aria-selected', isSelected ? 'true' : 'false');
        btn.dataset.pickerOption = 'true';
        btn.dataset.value = item.value;
        btn.dataset.optionIndex = String(index);
        if (item.disabled) {
            btn.disabled = true;
        }

        const isHighlighted = index === activeIndex;
        btn.className = [
            'w-full min-h-[44px] sm:min-h-0 px-3 sm:px-2.5 py-2.5 sm:py-1.5 rounded-lg text-xs text-left flex items-center justify-between gap-2 transition cursor-pointer',
            item.disabled ? 'opacity-45 cursor-not-allowed' : '',
            isSelected
                ? 'bg-forest-light text-forest dark:text-blue-300 font-bold'
                : (isHighlighted ? 'bg-cream text-charcoal font-medium' : 'text-charcoal hover:bg-cream/80'),
        ].filter(Boolean).join(' ');

        const left = document.createElement('span');
        left.className = 'min-w-0 flex items-center gap-2 flex-1';

        if (isRecentBadge) {
            const iconWrap = document.createElement('span');
            iconWrap.className = 'inline-flex items-center shrink-0';
            iconWrap.innerHTML = createClockIcon();
            left.appendChild(iconWrap);
        }

        const textSpan = document.createElement('span');
        textSpan.className = 'truncate';
        textSpan.textContent = item.label;
        left.appendChild(textSpan);

        btn.appendChild(left);

        const right = document.createElement('span');
        right.className = 'flex items-center gap-1.5 shrink-0';

        if (showGroupTag && item.group) {
            const tag = document.createElement('span');
            tag.className = 'text-[10px] font-semibold px-1.5 py-0.5 rounded bg-cream text-muted border border-line/70';
            tag.textContent = item.group;
            right.appendChild(tag);
        }

        if (isSelected) {
            const checkWrap = document.createElement('span');
            checkWrap.className = 'inline-flex items-center';
            checkWrap.innerHTML = createCheckIcon();
            right.appendChild(checkWrap);
        }

        if (right.children.length > 0) {
            btn.appendChild(right);
        }

        btn.addEventListener('mouseenter', () => {
            activeIndex = index;
            updateHighlightOnly();
        });

        btn.addEventListener('click', ev => {
            ev.stopPropagation();
            if (item.disabled) return;
            setSelectValue(item.value, true);
            closePopover(true);
        });

        return btn;
    }

    function updateHighlightOnly() {
        const optionBtns = listEl.querySelectorAll('[data-picker-option]');
        const currentValue = getSelectValue();

        optionBtns.forEach(btn => {
            const idx = Number(btn.dataset.optionIndex);
            const isSelected = btn.dataset.value === currentValue;
            const isHighlighted = idx === activeIndex;

            btn.classList.toggle('bg-cream', !isSelected && isHighlighted);
            btn.classList.toggle('font-medium', !isSelected && isHighlighted);
        });
    }

    function renderSectionHeader(title, accent = false) {
        const header = document.createElement('div');
        header.className = [
            'px-2.5 pt-2 pb-1 text-[10px] font-bold tracking-wider uppercase select-none',
            accent ? 'text-forest dark:text-blue-300' : 'text-muted',
        ].join(' ');
        header.textContent = title;
        return header;
    }

    function renderList() {
        const { items, emptyOption } = parseSelectData(selectEl);
        const currentValue = getSelectValue();
        const query = searchQuery.trim().toLowerCase();

        clearSearchBtn.classList.toggle('hidden', searchQuery.length === 0);
        clearSearchBtn.classList.toggle('flex', searchQuery.length > 0);

        listEl.innerHTML = '';

        const byValue = new Map(items.map(item => [item.value, item]));
        const recentSet = new Set(recentIds.map(String));
        const recentItems = recentIds
            .map(id => byValue.get(String(id)))
            .filter(item => item && !item.disabled);

        const renderedOptions = [];

        if (query !== '') {
            const matches = items.filter(item => {
                return item.searchText.toLowerCase().includes(query)
                    || (item.group && item.group.toLowerCase().includes(query));
            });

            matches.sort((a, b) => {
                const aStarts = a.rawName.toLowerCase().startsWith(query) ? 0 : 1;
                const bStarts = b.rawName.toLowerCase().startsWith(query) ? 0 : 1;
                if (aStarts !== bStarts) return aStarts - bStarts;
                const aRecent = recentSet.has(a.value) ? 0 : 1;
                const bRecent = recentSet.has(b.value) ? 0 : 1;
                return aRecent - bRecent;
            });

            if (matches.length === 0) {
                activeIndex = -1;
                const emptyBox = document.createElement('div');
                emptyBox.className = 'p-4 text-center space-y-2';

                const msg = document.createElement('p');
                msg.className = 'text-xs text-muted';
                msg.textContent = `${itemTitleNoun} "${searchQuery.trim()}" tidak ditemukan.`;

                const resetBtn = document.createElement('button');
                resetBtn.type = 'button';
                resetBtn.className = 'btn-arus-secondary min-h-[44px] sm:min-h-[32px] py-1 px-3 text-xs font-semibold cursor-pointer';
                resetBtn.textContent = 'Reset pencarian';
                resetBtn.addEventListener('click', ev => {
                    ev.stopPropagation();
                    searchQuery = '';
                    searchInput.value = '';
                    activeIndex = 0;
                    renderList();
                    searchInput.focus();
                });

                emptyBox.appendChild(msg);
                emptyBox.appendChild(resetBtn);
                listEl.appendChild(emptyBox);
                return;
            }

            const hasGroups = matches.some(item => item.group !== '');
            matches.forEach(item => {
                const idx = renderedOptions.length;
                renderedOptions.push(item);
                listEl.appendChild(createOptionRow(item, idx, currentValue, {
                    showGroupTag: hasGroups,
                    isRecentBadge: recentSet.has(item.value),
                }));
            });

            if (activeIndex < 0 || activeIndex >= renderedOptions.length) {
                activeIndex = 0;
                updateHighlightOnly();
            }
            return;
        }

        if (allowEmpty && emptyOption) {
            const idx = renderedOptions.length;
            renderedOptions.push(emptyOption);
            listEl.appendChild(createOptionRow(emptyOption, idx, currentValue));
        }

        if (recentItems.length > 0) {
            listEl.appendChild(renderSectionHeader('Terakhir Digunakan', true));
            const hasGroups = items.some(item => item.group !== '');
            recentItems.forEach(item => {
                const idx = renderedOptions.length;
                renderedOptions.push(item);
                listEl.appendChild(createOptionRow(item, idx, currentValue, {
                    showGroupTag: hasGroups,
                    isRecentBadge: true,
                }));
            });
        }

        const hasGroups = items.some(item => item.group !== '');
        if (hasGroups) {
            let lastGroup = null;
            items.forEach(item => {
                if (item.group && item.group !== lastGroup) {
                    listEl.appendChild(renderSectionHeader(item.group));
                    lastGroup = item.group;
                }
                const idx = renderedOptions.length;
                renderedOptions.push(item);
                listEl.appendChild(createOptionRow(item, idx, currentValue));
            });
        } else if (items.length > 0) {
            if (recentItems.length > 0) {
                listEl.appendChild(renderSectionHeader(allSectionLabel));
            }
            items.forEach(item => {
                const idx = renderedOptions.length;
                renderedOptions.push(item);
                listEl.appendChild(createOptionRow(item, idx, currentValue));
            });
        } else {
            const noItem = document.createElement('div');
            noItem.className = 'p-3 text-center text-xs text-muted';
            noItem.textContent = `Belum ada ${itemNoun} aktif tersedia.`;
            listEl.appendChild(noItem);
        }

        const firstSelectable = renderedOptions.findIndex(item => item.value === currentValue && !item.disabled);
        const fallbackSelectable = renderedOptions.findIndex(item => !item.disabled);
        activeIndex = firstSelectable >= 0 ? firstSelectable : fallbackSelectable;
        updateHighlightOnly();
    }

    function positionPopover() {
        const rect = triggerBtn.getBoundingClientRect();
        const vv = window.visualViewport;
        const viewportW = vv ? vv.width : window.innerWidth;
        const viewportH = vv ? vv.height : window.innerHeight;
        const vvTop = vv ? vv.offsetTop : 0;
        const vvLeft = vv ? vv.offsetLeft : 0;
        const width = Math.min(Math.max(rect.width, 240), viewportW - 16);

        popover.style.width = `${width}px`;

        const spaceBelow = (vvTop + viewportH) - rect.bottom - 10;
        const spaceAbove = rect.top - vvTop - 10;
        const openUpwards = spaceBelow < 180 && spaceAbove > spaceBelow;
        const availableSpace = Math.max(120, (openUpwards ? spaceAbove : spaceBelow) - 62);
        listEl.style.maxHeight = `${Math.min(248, availableSpace)}px`;

        const popHeight = popover.offsetHeight || 280;
        let top = rect.bottom + 6;
        if (openUpwards) {
            top = Math.max(vvTop + 8, rect.top - popHeight - 6);
        }

        let left = rect.left;
        if (left + width > vvLeft + viewportW - 8) {
            left = Math.max(vvLeft + 8, vvLeft + viewportW - width - 8);
        }
        if (left < vvLeft + 8) {
            left = vvLeft + 8;
        }

        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
    }

    function openPopover() {
        if (isSelectDisabled() || isOpen) return;

        document.querySelectorAll('.arus-category-popover').forEach(el => {
            if (el !== popover && el.style.display === 'block') {
                el.dispatchEvent(new CustomEvent('arus:force-close'));
            }
        });

        isOpen = true;
        searchQuery = '';
        searchInput.value = '';
        renderList();

        popover.classList.remove('hidden');
        popover.style.display = 'block';
        positionPopover();

        triggerBtn.setAttribute('aria-expanded', 'true');
        chevron.classList.add('rotate-180');

        setTimeout(() => {
            searchInput.focus();
        }, 10);
    }

    function closePopover(returnFocus = false) {
        if (!isOpen) return;
        isOpen = false;
        popover.style.display = 'none';
        popover.classList.add('hidden');
        triggerBtn.setAttribute('aria-expanded', 'false');
        chevron.classList.remove('rotate-180');
        if (returnFocus) {
            triggerBtn.focus();
        }
    }

    function selectActiveOption() {
        const optionBtns = listEl.querySelectorAll('[data-picker-option]');
        if (optionBtns.length === 0) return;

        const targetBtn = optionBtns[activeIndex] || optionBtns[0];
        if (!targetBtn || targetBtn.disabled) return;

        setSelectValue(targetBtn.dataset.value, true);
        closePopover(true);
    }

    function moveHighlight(delta) {
        const optionBtns = listEl.querySelectorAll('[data-picker-option]');
        if (optionBtns.length === 0) return;

        let candidate = activeIndex;
        for (let step = 0; step < optionBtns.length; step++) {
            candidate += delta;
            if (candidate < 0) candidate = optionBtns.length - 1;
            if (candidate >= optionBtns.length) candidate = 0;
            if (!optionBtns[candidate].disabled) {
                activeIndex = candidate;
                updateHighlightOnly();
                optionBtns[activeIndex]?.scrollIntoView({ block: 'nearest' });
                return;
            }
        }
    }

    triggerBtn.addEventListener('click', ev => {
        ev.stopPropagation();
        if (isOpen) {
            closePopover(false);
        } else {
            openPopover();
        }
    });

    triggerBtn.addEventListener('keydown', ev => {
        if (ev.key === 'ArrowDown' || ev.key === 'Enter' || ev.key === ' ') {
            ev.preventDefault();
            openPopover();
        }
    });

    clearValueBtn.addEventListener('click', ev => {
        ev.stopPropagation();
        setSelectValue('', true);
        triggerBtn.focus();
    });

    searchInput.addEventListener('input', () => {
        searchQuery = searchInput.value;
        activeIndex = 0;
        renderList();
        positionPopover();
    });

    searchInput.addEventListener('keydown', ev => {
        if (ev.key === 'ArrowDown') {
            ev.preventDefault();
            moveHighlight(1);
        } else if (ev.key === 'ArrowUp') {
            ev.preventDefault();
            moveHighlight(-1);
        } else if (ev.key === 'Enter') {
            ev.preventDefault();
            ev.stopPropagation();
            selectActiveOption();
        } else if (ev.key === 'Escape') {
            ev.preventDefault();
            ev.stopPropagation();
            if (searchQuery !== '') {
                searchQuery = '';
                searchInput.value = '';
                activeIndex = 0;
                renderList();
            } else {
                closePopover(true);
            }
        } else if (ev.key === 'Tab') {
            closePopover(false);
        }
    });

    clearSearchBtn.addEventListener('click', ev => {
        ev.stopPropagation();
        searchQuery = '';
        searchInput.value = '';
        activeIndex = 0;
        renderList();
        searchInput.focus();
    });

    popover.addEventListener('click', ev => {
        ev.stopPropagation();
    });

    popover.addEventListener('arus:force-close', () => {
        closePopover(false);
    });

    selectEl.addEventListener('change', () => {
        syncFromSelect();
    });

    selectEl.addEventListener('arus:update-recent', ev => {
        const nextIds = ev.detail?.recentIds;
        if (Array.isArray(nextIds)) {
            recentIds = nextIds.map(String);
            selectEl.setAttribute('data-recent-ids', JSON.stringify(nextIds));
            renderChips();
            if (isOpen) {
                renderList();
            }
        }
    });

    selectEl.form?.addEventListener('reset', () => {
        setTimeout(() => {
            syncFromSelect();
        }, 0);
    });

    document.addEventListener('click', ev => {
        if (isOpen && !wrapper.contains(ev.target) && !popover.contains(ev.target)) {
            closePopover(false);
        }
    });

    document.addEventListener('keydown', ev => {
        if (isOpen && ev.key === 'Escape') {
            ev.preventDefault();
            ev.stopPropagation();
            if (ev.target === searchInput && searchQuery !== '') {
                searchQuery = '';
                searchInput.value = '';
                activeIndex = 0;
                renderList();
                return;
            }
            closePopover(true);
        }
    }, true);

    window.addEventListener('resize', () => {
        if (isOpen) {
            positionPopover();
        }
    });

    window.visualViewport?.addEventListener('resize', () => {
        if (isOpen) {
            positionPopover();
        }
    });

    window.visualViewport?.addEventListener('scroll', () => {
        if (isOpen) {
            positionPopover();
        }
    });

    window.addEventListener('scroll', () => {
        if (isOpen) {
            positionPopover();
        }
    }, true);

    REGISTRY.set(selectEl, {
        syncFromSelect,
        recentScope,
        updateRecentIds(nextIds) {
            recentIds = Array.isArray(nextIds) ? nextIds.map(String) : [];
            selectEl.setAttribute('data-recent-ids', JSON.stringify(recentIds));
            renderChips();
            if (isOpen) {
                renderList();
            }
        },
    });

    const optionObserver = new MutationObserver(() => {
        syncFromSelect();
    });
    optionObserver.observe(selectEl, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['disabled', 'hidden'],
    });

    syncFromSelect();
    syncDisabledState();
}

export function initSearchablePickers(container = document) {
    const selects = container.querySelectorAll(PICKER_SELECTOR);
    selects.forEach(select => enhanceSearchableSelect(select));
}

export function updateSearchablePickersRecentIds(recentByScope) {
    if (!recentByScope || typeof recentByScope !== 'object') return;

    document.querySelectorAll(PICKER_SELECTOR).forEach(select => {
        const scope = select.getAttribute('data-recent-scope');
        if (!scope || !Array.isArray(recentByScope[scope])) return;

        const instance = REGISTRY.get(select);
        if (instance) {
            instance.updateRecentIds(recentByScope[scope]);
        }
    });
}

export const enhanceCategorySelect = enhanceSearchableSelect;
export const initCategoryPickers = initSearchablePickers;
export const updateCategoryPickersRecentIds = updateSearchablePickersRecentIds;

window.initSearchablePickers = initSearchablePickers;
window.enhanceSearchableSelect = enhanceSearchableSelect;
window.updateSearchablePickersRecentIds = updateSearchablePickersRecentIds;
window.initCategoryPickers = initCategoryPickers;
window.enhanceCategorySelect = enhanceCategorySelect;
window.updateCategoryPickersRecentIds = updateCategoryPickersRecentIds;

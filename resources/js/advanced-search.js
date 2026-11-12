const RECENT_STORAGE_PREFIX = 'arus_recent_searches_';
const MAX_RECENT_ITEMS = 6;

export function initAdvancedSearch() {
    const modal = document.getElementById('globalAdvancedSearchModal');
    if (!modal || modal.dataset.initialized === 'true') {
        return;
    }
    modal.dataset.initialized = 'true';

    const endpoint = modal.dataset.searchEndpoint || '/search';
    const baseTransactionsUrl = modal.dataset.transactionsUrl || '/transactions';
    const spaceId = modal.dataset.spaceId || '0';
    const recentStorageKey = `${RECENT_STORAGE_PREFIX}${spaceId}`;

    const queryInput = document.getElementById('advSearchQueryInput');
    const clearQueryBtn = document.getElementById('advSearchClearQueryBtn');
    const filterToggleBtn = document.getElementById('advSearchFilterToggleBtn');
    const filterPanel = document.getElementById('advSearchFilterPanel');
    const filterBadge = document.getElementById('advSearchFilterBadge');

    const filterType = document.getElementById('advFilterType');
    const filterStatus = document.getElementById('advFilterStatus');
    const filterAccount = document.getElementById('advFilterAccount');
    const filterCategory = document.getElementById('advFilterCategory');
    const filterMember = document.getElementById('advFilterMember');
    const filterStartDate = document.getElementById('advFilterStartDate');
    const filterEndDate = document.getElementById('advFilterEndDate');
    const filterMinAmount = document.getElementById('advFilterMinAmount');
    const filterMaxAmount = document.getElementById('advFilterMaxAmount');
    const filterSort = document.getElementById('advFilterSort');
    const filterIncludeFuture = document.getElementById('advFilterIncludeFuture');

    const resetFiltersBtn = document.getElementById('advSearchResetFiltersBtn');
    const openTransactionsLink = document.getElementById('advSearchOpenTransactionsLink');

    const initialState = document.getElementById('advSearchInitialState');
    const loadingState = document.getElementById('advSearchLoadingState');
    const errorState = document.getElementById('advSearchErrorState');
    const errorDetail = document.getElementById('advSearchErrorDetail');
    const emptyState = document.getElementById('advSearchEmptyState');
    const emptyReason = document.getElementById('advSearchEmptyReason');
    const emptyTxLink = document.getElementById('advSearchEmptyTxLink');
    const emptyResetBtn = document.getElementById('advSearchEmptyResetBtn');
    const retryBtn = document.getElementById('advSearchRetryBtn');
    const resultsContent = document.getElementById('advSearchResultsContent');

    const recentSection = document.getElementById('advRecentSearchesSection');
    const recentList = document.getElementById('advRecentSearchesList');
    const clearRecentBtn = document.getElementById('advClearRecentBtn');

    const topbarInput = document.getElementById('globalSearchInput');
    const mobileToggle = document.getElementById('mobileSearchToggle');

    let currentScope = 'all';
    let debounceTimer = null;
    let activeAbortController = null;
    let activeResultIndex = -1;
    let lastReturnFocusEl = null;

    function getRecentSearches() {
        try {
            const raw = localStorage.getItem(recentStorageKey);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed.filter((item) => typeof item === 'string' && item.trim() !== '') : [];
        } catch {
            return [];
        }
    }

    function saveRecentSearch(term) {
        const cleaned = (term || '').trim();
        if (cleaned.length < 2) {
            return;
        }
        try {
            const existing = getRecentSearches().filter((item) => item.toLowerCase() !== cleaned.toLowerCase());
            const updated = [cleaned, ...existing].slice(0, MAX_RECENT_ITEMS);
            localStorage.setItem(recentStorageKey, JSON.stringify(updated));
            renderRecentSearches();
        } catch {
            // Ignore storage quota errors
        }
    }

    function clearRecentSearches() {
        try {
            localStorage.removeItem(recentStorageKey);
        } catch {
            // Ignore
        }
        renderRecentSearches();
    }

    function renderRecentSearches() {
        if (!recentSection || !recentList) {
            return;
        }
        const items = getRecentSearches();
        recentList.innerHTML = '';

        if (items.length === 0) {
            recentSection.classList.add('hidden');
            return;
        }

        recentSection.classList.remove('hidden');
        items.forEach((term) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'min-h-[36px] sm:min-h-[28px] px-3 py-1 rounded-lg border border-line bg-panel hover:bg-cream text-xs font-medium text-charcoal transition cursor-pointer inline-flex items-center gap-1.5';
            btn.textContent = term;
            btn.addEventListener('click', () => {
                if (queryInput) {
                    queryInput.value = term;
                    syncClearButton();
                    executeSearch();
                    queryInput.focus();
                }
            });
            recentList.appendChild(btn);
        });
    }

    function setFilterPanelOpen(open) {
        if (!filterPanel || !filterToggleBtn) {
            return;
        }
        filterPanel.classList.toggle('hidden', !open);
        filterToggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        filterToggleBtn.classList.toggle('border-forest', open);
        filterToggleBtn.classList.toggle('bg-forest-light/40', open);
    }

    function getActiveFilterCount() {
        let count = 0;
        if (filterType?.value) count++;
        if (filterStatus && filterStatus.value !== 'posted') count++;
        if (filterAccount?.value) count++;
        if (filterCategory?.value) count++;
        if (filterMember?.value) count++;
        if (filterStartDate?.value) count++;
        if (filterEndDate?.value) count++;
        if (filterMinAmount?.value.trim()) count++;
        if (filterMaxAmount?.value.trim()) count++;
        if (filterSort && filterSort.value !== 'date_desc') count++;
        if (filterIncludeFuture && !filterIncludeFuture.checked) count++;
        return count;
    }

    function updateFilterBadge() {
        const count = getActiveFilterCount();
        if (filterBadge) {
            filterBadge.textContent = String(count);
            filterBadge.classList.toggle('hidden', count === 0);
        }
        if (openTransactionsLink) {
            openTransactionsLink.href = buildTransactionsUrl();
        }
        if (emptyTxLink) {
            emptyTxLink.href = buildTransactionsUrl();
        }
    }

    function syncClearButton() {
        if (!clearQueryBtn || !queryInput) {
            return;
        }
        const hasText = queryInput.value.trim().length > 0;
        clearQueryBtn.classList.toggle('hidden', !hasText);
        clearQueryBtn.classList.toggle('inline-flex', hasText);
    }

    function buildQueryParams() {
        const params = new URLSearchParams();
        const q = (queryInput?.value || '').trim();
        if (q) params.set('q', q);
        if (currentScope && currentScope !== 'all') params.set('scope', currentScope);
        if (filterType?.value) params.set('type', filterType.value);
        if (filterStatus?.value && filterStatus.value !== 'posted') params.set('status', filterStatus.value);
        if (filterAccount?.value) params.set('account_id', filterAccount.value);
        if (filterCategory?.value) params.set('category_id', filterCategory.value);
        if (filterMember?.value) params.set('member_id', filterMember.value);
        if (filterStartDate?.value) params.set('start_date', filterStartDate.value);
        if (filterEndDate?.value) params.set('end_date', filterEndDate.value);
        if (filterMinAmount?.value.trim()) params.set('min_amount', filterMinAmount.value.trim());
        if (filterMaxAmount?.value.trim()) params.set('max_amount', filterMaxAmount.value.trim());
        if (filterSort?.value && filterSort.value !== 'date_desc') params.set('sort', filterSort.value);
        if (filterIncludeFuture) {
            params.set('include_future', filterIncludeFuture.checked ? '1' : '0');
        }
        return params;
    }

    function buildTransactionsUrl() {
        const params = new URLSearchParams();
        const q = (queryInput?.value || '').trim();
        if (q) params.set('search', q);
        if (filterType?.value) params.set('type', filterType.value);
        if (filterStatus?.value && filterStatus.value !== 'posted') params.set('status', filterStatus.value);
        if (filterAccount?.value) params.set('account_id', filterAccount.value);
        if (filterCategory?.value) params.set('category_id', filterCategory.value);
        if (filterMember?.value) params.set('member_id', filterMember.value);
        if (filterStartDate?.value) params.set('start_date', filterStartDate.value);
        if (filterEndDate?.value) params.set('end_date', filterEndDate.value);
        if (filterMinAmount?.value.trim()) params.set('min_amount', filterMinAmount.value.trim());
        if (filterMaxAmount?.value.trim()) params.set('max_amount', filterMaxAmount.value.trim());
        if (filterSort?.value && filterSort.value !== 'date_desc') params.set('sort', filterSort.value);

        const qs = params.toString();
        return qs ? `${baseTransactionsUrl}?${qs}` : baseTransactionsUrl;
    }

    function hasAnyActiveInput() {
        return (queryInput?.value || '').trim() !== '' || getActiveFilterCount() > 0;
    }

    function setVisibleState(stateName) {
        initialState?.classList.toggle('hidden', stateName !== 'initial');
        loadingState?.classList.toggle('hidden', stateName !== 'loading');
        errorState?.classList.toggle('hidden', stateName !== 'error');
        emptyState?.classList.toggle('hidden', stateName !== 'empty');
        resultsContent?.classList.toggle('hidden', stateName !== 'results');
    }

    function updateScopeCounts(counts) {
        const totalAll = counts
            ? (counts.transactions || 0) + (counts.accounts || 0) + (counts.plans || 0) + (counts.other || 0) + (counts.pages || 0)
            : 0;

        document.querySelectorAll('[data-adv-scope-count]').forEach((badge) => {
            const key = badge.getAttribute('data-adv-scope-count');
            if (!counts || !hasAnyActiveInput()) {
                badge.classList.add('hidden');
                badge.textContent = '0';
                return;
            }
            const val = key === 'all' ? totalAll : (counts[key] || 0);
            badge.textContent = String(val);
            badge.classList.toggle('hidden', val === 0);
        });
    }

    function setScope(scopeKey, shouldSearch = true) {
        currentScope = scopeKey || 'all';
        document.querySelectorAll('.adv-scope-tab').forEach((tab) => {
            const isSelected = tab.getAttribute('data-adv-scope') === currentScope;
            tab.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            tab.classList.toggle('bg-forest', isSelected);
            tab.classList.toggle('text-white', isSelected);
            tab.classList.toggle('border-forest', isSelected);
            tab.classList.toggle('bg-panel', !isSelected);
            tab.classList.toggle('text-charcoal', !isSelected);
            tab.classList.toggle('border-line', !isSelected);
        });

        if (shouldSearch && hasAnyActiveInput()) {
            executeSearch();
        }
    }

    function createHighlightedNode(text, term) {
        const container = document.createDocumentFragment();
        const source = String(text ?? '');
        const needle = (term || '').trim();

        if (!needle || needle.length < 2) {
            container.appendChild(document.createTextNode(source));
            return container;
        }

        const escaped = needle.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${escaped})`, 'gi');
        const parts = source.split(regex);

        parts.forEach((part) => {
            if (!part) return;
            if (part.toLowerCase() === needle.toLowerCase()) {
                const mark = document.createElement('mark');
                mark.className = 'bg-forest-light text-forest dark:text-blue-200 rounded px-0.5 font-semibold';
                mark.textContent = part;
                container.appendChild(mark);
            } else {
                container.appendChild(document.createTextNode(part));
            }
        });

        return container;
    }

    function createBadge(text, classes) {
        const span = document.createElement('span');
        span.className = `inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold shrink-0 ${classes}`;
        span.textContent = text;
        return span;
    }

    function createResultRow({ url, title, subtitle, badges = [], rightText = null, rightToneClass = 'text-charcoal', isPrivacyAmount = false, query = '' }) {
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('data-adv-search-item', 'true');
        link.className = 'adv-result-row flex items-center justify-between gap-3 p-2.5 sm:px-3 rounded-xl border border-line bg-panel hover:bg-cream hover:border-sage transition min-h-[48px] focus-visible:outline-2 focus-visible:outline-forest';

        link.addEventListener('click', () => {
            if (queryInput?.value) {
                saveRecentSearch(queryInput.value);
            }
        });

        const left = document.createElement('div');
        left.className = 'min-w-0 flex-1 space-y-1';

        const topRow = document.createElement('div');
        topRow.className = 'flex items-center gap-1.5 flex-wrap';

        badges.forEach((b) => topRow.appendChild(createBadge(b.text, b.classes)));

        const titleEl = document.createElement('span');
        titleEl.className = 'text-xs font-bold text-charcoal truncate';
        titleEl.appendChild(createHighlightedNode(title, query));
        topRow.appendChild(titleEl);

        left.appendChild(topRow);

        if (subtitle) {
            const subEl = document.createElement('div');
            subEl.className = 'text-[11px] text-muted truncate';
            subEl.appendChild(createHighlightedNode(subtitle, query));
            left.appendChild(subEl);
        }

        link.appendChild(left);

        if (rightText !== null) {
            const rightEl = document.createElement('div');
            rightEl.className = `text-xs font-mono font-bold shrink-0 text-right ${rightToneClass}${isPrivacyAmount ? ' privacy-balance' : ''}`;
            rightEl.textContent = rightText;
            link.appendChild(rightEl);
        }

        return link;
    }

    function renderResults(data) {
        if (!resultsContent) {
            return;
        }

        resultsContent.innerHTML = '';
        activeResultIndex = -1;
        updateScopeCounts(data.counts);

        const groups = data.groups || {};
        const query = data.query || '';
        const totalReturned = data.total_returned || 0;
        const totalTx = data.counts?.transactions || 0;

        if (totalReturned === 0) {
            if (emptyReason) {
                emptyReason.textContent = query
                    ? `Tidak ditemukan data yang cocok untuk "${query}". Coba kata kunci lain atau sesuaikan filter.`
                    : 'Tidak ada transaksi yang sesuai dengan kombinasi filter saat ini.';
            }
            if (emptyTxLink && data.transactions_index_url) {
                emptyTxLink.href = data.transactions_index_url;
            }
            setVisibleState('empty');
            return;
        }

        // Summary bar
        const summaryBar = document.createElement('div');
        summaryBar.className = 'flex items-center justify-between gap-2 pb-2 border-b border-line/70';

        const summaryText = document.createElement('div');
        summaryText.className = 'text-xs text-muted';
        const txNote = totalTx > (groups.transactions?.length || 0)
            ? ` (menampilkan ${groups.transactions.length} dari ${totalTx} transaksi)`
            : '';
        summaryText.textContent = `Ditemukan ${totalReturned} item relevan${txNote}`;
        summaryBar.appendChild(summaryText);
        resultsContent.appendChild(summaryBar);

        const unifiedItems = [];

        // Transactions
        (groups.transactions || []).forEach((tx) => {
            const typeBadgeClass = tx.type === 'income'
                ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'
                : tx.type === 'expense'
                    ? 'bg-rose-500/15 text-rose-700 dark:text-rose-300'
                    : 'bg-forest-light text-forest dark:text-blue-300';

            const badges = [{ text: 'Transaksi', classes: typeBadgeClass }];
            if (tx.status === 'void') {
                badges.push({ text: 'Void', classes: 'bg-amber-500/15 text-amber-800 dark:text-amber-300' });
            } else if (tx.is_upcoming) {
                badges.push({ text: 'Mendatang', classes: 'bg-cream text-muted border border-line' });
            }

            const metaParts = [tx.type_label, tx.formatted_date, tx.account_summary];
            if (tx.category_name) metaParts.push(tx.category_name);
            if (tx.member_name) metaParts.push(tx.member_name);

            const amountTone = tx.status === 'void'
                ? 'text-muted line-through'
                : tx.type === 'income'
                    ? 'text-emerald-700 dark:text-emerald-300'
                    : tx.type === 'expense'
                        ? 'text-rose-700 dark:text-rose-300'
                        : 'text-charcoal';

            unifiedItems.push({
                url: tx.url,
                title: tx.description || '(Tanpa keterangan)',
                subtitle: metaParts.filter(Boolean).join(' · '),
                badges,
                rightText: tx.formatted_amount,
                rightToneClass: amountTone,
                isPrivacyAmount: true,
            });
        });

        // Accounts
        (groups.accounts || []).forEach((acc) => {
            const badges = [{ text: 'Rekening', classes: 'bg-sky-500/15 text-sky-700 dark:text-sky-300' }];
            if (acc.is_archived) {
                badges.push({ text: 'Arsip', classes: 'bg-cream text-muted border border-line' });
            }

            const metaParts = [acc.type_label];
            if (acc.is_default) metaParts.push('Rekening Utama');
            metaParts.push('Detail mutasi & saldo');

            unifiedItems.push({
                url: acc.url,
                title: acc.name,
                subtitle: metaParts.filter(Boolean).join(' · '),
                badges,
                rightText: acc.formatted_balance,
                rightToneClass: acc.balance < 0 ? 'text-rose-700 dark:text-rose-300' : 'text-charcoal',
                isPrivacyAmount: true,
            });
        });

        // Goals
        (groups.goals || []).forEach((goal) => {
            unifiedItems.push({
                url: goal.url,
                title: goal.name,
                subtitle: `${goal.status_label} · ${goal.formatted_current} / ${goal.formatted_target}${goal.target_date ? ' · Target ' + goal.target_date : ''}`,
                badges: [{ text: 'Target', classes: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' }],
                rightText: `${goal.percentage}%`,
                rightToneClass: 'text-forest dark:text-blue-300',
            });
        });

        // Short Plans
        (groups.short_plans || []).forEach((plan) => {
            unifiedItems.push({
                url: plan.url,
                title: plan.title,
                subtitle: `${plan.status_label}${plan.target_date ? ' · Target ' + plan.target_date : ' · Rencana jangka pendek'}`,
                badges: [{ text: 'Short Plan', classes: 'bg-teal-500/15 text-teal-700 dark:text-teal-300' }],
                rightText: plan.formatted_amount,
                isPrivacyAmount: true,
            });
        });

        // Recurring Schedules
        (groups.recurring || []).forEach((rec) => {
            unifiedItems.push({
                url: rec.url,
                title: rec.name,
                subtitle: `${rec.frequency_label}${rec.next_due_date ? ' · Berikutnya ' + rec.next_due_date : ''}`,
                badges: [{ text: 'Jadwal Berulang', classes: 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300' }],
                rightText: rec.formatted_amount,
                isPrivacyAmount: true,
            });
        });

        // Credit & Installments
        (groups.credit || []).forEach((item) => {
            unifiedItems.push({
                url: item.url,
                title: item.name,
                subtitle: item.meta,
                badges: [{ text: item.kind_label || 'Kredit', classes: 'bg-amber-500/15 text-amber-800 dark:text-amber-300' }],
            });
        });

        // Categories
        (groups.categories || []).forEach((cat) => {
            unifiedItems.push({
                url: cat.url,
                title: cat.name,
                subtitle: `${cat.type_label} · Lihat transaksi kategori ini`,
                badges: [{ text: 'Kategori', classes: 'bg-violet-500/15 text-violet-700 dark:text-violet-300' }],
            });
        });

        // Budgets
        (groups.budgets || []).forEach((budget) => {
            unifiedItems.push({
                url: budget.url,
                title: `Anggaran ${budget.category_name}`,
                subtitle: `${budget.period} · Batas ${budget.formatted_limit}`,
                badges: [{ text: 'Anggaran', classes: 'bg-cyan-500/15 text-cyan-700 dark:text-cyan-300' }],
            });
        });

        // Members
        (groups.members || []).forEach((member) => {
            unifiedItems.push({
                url: member.url,
                title: member.name,
                subtitle: 'Partisipan · Lihat transaksi member ini',
                badges: [{ text: 'Member', classes: 'bg-fuchsia-500/15 text-fuchsia-700 dark:text-fuchsia-300' }],
            });
        });

        // Reminders
        (groups.reminders || []).forEach((rem) => {
            unifiedItems.push({
                url: rem.url,
                title: rem.title,
                subtitle: `${rem.type_label}${rem.due_date ? ' · ' + rem.due_date : ''}`,
                badges: [{ text: rem.is_active ? 'Pengingat' : 'Pengingat Nonaktif', classes: 'bg-orange-500/15 text-orange-800 dark:text-orange-300' }],
            });
        });

        // Menu & General Pages
        (groups.pages || []).forEach((page) => {
            const label = page.kind_label || 'Menu';
            const badgeClass = label === 'General'
                ? 'bg-cream text-muted border border-line'
                : 'bg-forest-light text-forest dark:text-blue-300';

            unifiedItems.push({
                url: page.url,
                title: page.title,
                subtitle: page.subtitle,
                badges: [{ text: label, classes: badgeClass }],
            });
        });

        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-1 sm:grid-cols-2 gap-2';

        unifiedItems.forEach((item) => {
            grid.appendChild(createResultRow({
                ...item,
                query,
            }));
        });

        resultsContent.appendChild(grid);

        setVisibleState('results');

        if (typeof window.syncPrivacyContainer === 'function') {
            window.syncPrivacyContainer(resultsContent);
        }
    }

    async function executeSearch() {
        updateFilterBadge();

        if (!hasAnyActiveInput()) {
            if (activeAbortController) {
                activeAbortController.abort();
                activeAbortController = null;
            }
            updateScopeCounts(null);
            renderRecentSearches();
            setVisibleState('initial');
            return;
        }

        if (activeAbortController) {
            activeAbortController.abort();
        }
        activeAbortController = new AbortController();

        setVisibleState('loading');

        try {
            const params = buildQueryParams();
            const response = await fetch(`${endpoint}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeAbortController.signal,
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            renderResults(data);
        } catch (err) {
            if (err && err.name === 'AbortError') {
                return;
            }
            if (errorDetail) {
                errorDetail.textContent = 'Terjadi gangguan saat mengambil hasil pencarian. Silakan coba lagi.';
            }
            setVisibleState('error');
        }
    }

    function scheduleSearch(delay = 220) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => executeSearch(), delay);
    }

    function resetAllFiltersAndQuery(resetQuery = true) {
        if (resetQuery && queryInput) {
            queryInput.value = '';
            if (topbarInput) {
                topbarInput.value = '';
            }
            syncClearButton();
        }
        if (filterType) filterType.value = '';
        if (filterStatus) filterStatus.value = 'posted';
        if (filterAccount) filterAccount.value = '';
        if (filterCategory) filterCategory.value = '';
        if (filterMember) filterMember.value = '';
        if (filterStartDate) filterStartDate.value = '';
        if (filterEndDate) filterEndDate.value = '';
        if (filterMinAmount) filterMinAmount.value = '';
        if (filterMaxAmount) filterMaxAmount.value = '';
        if (filterSort) filterSort.value = 'date_desc';
        if (filterIncludeFuture) filterIncludeFuture.checked = true;

        setScope('all', false);
        updateFilterBadge();
        executeSearch();
    }

    function applyDatePreset(preset) {
        const today = modal.dataset.today || '';
        const monthStart = modal.dataset.monthStart || '';
        const monthEnd = modal.dataset.monthEnd || '';
        const lastMonthStart = modal.dataset.lastMonthStart || '';
        const lastMonthEnd = modal.dataset.lastMonthEnd || '';
        const sevenDaysAgo = modal.dataset.sevenDaysAgo || '';

        if (!filterStartDate || !filterEndDate) return;

        if (preset === 'today') {
            filterStartDate.value = today;
            filterEndDate.value = today;
        } else if (preset === 'last_7_days') {
            filterStartDate.value = sevenDaysAgo;
            filterEndDate.value = today;
        } else if (preset === 'this_month') {
            filterStartDate.value = monthStart;
            filterEndDate.value = monthEnd;
        } else if (preset === 'last_month') {
            filterStartDate.value = lastMonthStart;
            filterEndDate.value = lastMonthEnd;
        } else if (preset === 'clear') {
            filterStartDate.value = '';
            filterEndDate.value = '';
        }

        executeSearch();
    }

    function applyQuickFilter(key) {
        resetAllFiltersAndQuery(false);
        setFilterPanelOpen(true);

        if (key === 'expense_month') {
            if (filterType) filterType.value = 'expense';
            setScope('transactions', false);
            applyDatePreset('this_month');
            return;
        }
        if (key === 'income_month') {
            if (filterType) filterType.value = 'income';
            setScope('transactions', false);
            applyDatePreset('this_month');
            return;
        }
        if (key === 'transfer_all') {
            if (filterType) filterType.value = 'transfer';
            setScope('transactions', false);
            executeSearch();
            return;
        }
        if (key === 'large_amount') {
            if (filterMinAmount) filterMinAmount.value = '500000';
            setScope('transactions', false);
            executeSearch();
            return;
        }
        if (key === 'void_only') {
            if (filterStatus) filterStatus.value = 'void';
            setScope('transactions', false);
            executeSearch();
            return;
        }
        if (key === 'open_custom_filters') {
            setScope('transactions', false);
            filterType?.focus();
        }
    }

    function getNavigableItems() {
        const container = resultsContent && !resultsContent.classList.contains('hidden')
            ? resultsContent
            : initialState;
        if (!container) return [];
        return Array.from(container.querySelectorAll('[data-adv-search-item="true"]'));
    }

    function highlightItemAt(index) {
        const items = getNavigableItems();
        if (items.length === 0) {
            activeResultIndex = -1;
            return;
        }

        const bounded = ((index % items.length) + items.length) % items.length;
        activeResultIndex = bounded;

        items.forEach((el, i) => {
            const isActive = i === bounded;
            el.classList.toggle('ring-2', isActive);
            el.classList.toggle('ring-forest', isActive);
            el.classList.toggle('bg-cream', isActive);
            if (isActive) {
                el.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    window.openAdvancedSearchModal = function ({ expandFilters = false, initialQuery = null } = {}) {
        lastReturnFocusEl = document.activeElement;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        mobileToggle?.setAttribute('aria-expanded', 'true');

        if (initialQuery !== null && queryInput) {
            queryInput.value = initialQuery;
        } else if (topbarInput && topbarInput.value && queryInput && !queryInput.value) {
            queryInput.value = topbarInput.value;
        }

        if (expandFilters) {
            setFilterPanelOpen(true);
        }

        syncClearButton();
        updateFilterBadge();
        renderRecentSearches();

        if (hasAnyActiveInput()) {
            executeSearch();
        } else {
            setVisibleState('initial');
        }

        setTimeout(() => {
            queryInput?.focus();
            if (queryInput && queryInput.value) {
                const len = queryInput.value.length;
                queryInput.setSelectionRange(len, len);
            }
        }, 30);
    };

    window.closeAdvancedSearchModal = function () {
        if (modal.classList.contains('hidden')) {
            return;
        }
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        mobileToggle?.setAttribute('aria-expanded', 'false');
        if (lastReturnFocusEl && typeof lastReturnFocusEl.focus === 'function') {
            lastReturnFocusEl.focus();
        }
    };

    // Event bindings inside modal
    queryInput?.addEventListener('input', () => {
        if (topbarInput) {
            topbarInput.value = queryInput.value;
        }
        syncClearButton();
        scheduleSearch(220);
    });

    clearQueryBtn?.addEventListener('click', () => {
        if (queryInput) {
            queryInput.value = '';
        }
        if (topbarInput) {
            topbarInput.value = '';
        }
        syncClearButton();
        executeSearch();
        queryInput?.focus();
    });

    filterToggleBtn?.addEventListener('click', () => {
        const isCurrentlyHidden = filterPanel?.classList.contains('hidden');
        setFilterPanelOpen(Boolean(isCurrentlyHidden));
    });

    [filterType, filterStatus, filterAccount, filterCategory, filterMember, filterStartDate, filterEndDate, filterSort, filterIncludeFuture].forEach((el) => {
        el?.addEventListener('change', () => executeSearch());
    });

    [filterMinAmount, filterMaxAmount].forEach((el) => {
        el?.addEventListener('input', () => scheduleSearch(260));
    });

    resetFiltersBtn?.addEventListener('click', () => resetAllFiltersAndQuery(false));
    emptyResetBtn?.addEventListener('click', () => {
        resetAllFiltersAndQuery(true);
        queryInput?.focus();
    });
    retryBtn?.addEventListener('click', () => executeSearch());
    clearRecentBtn?.addEventListener('click', () => clearRecentSearches());

    document.querySelectorAll('.adv-scope-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            setScope(tab.getAttribute('data-adv-scope') || 'all');
        });
    });

    document.querySelectorAll('.adv-date-preset').forEach((btn) => {
        btn.addEventListener('click', () => {
            applyDatePreset(btn.getAttribute('data-adv-date-preset') || 'clear');
        });
    });

    document.querySelectorAll('.adv-quick-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            applyQuickFilter(btn.getAttribute('data-adv-quick-filter') || '');
        });
    });

    // Keyboard navigation inside modal
    modal.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation();
            window.closeAdvancedSearchModal();
            return;
        }

        if (e.key === 'ArrowDown') {
            if (document.activeElement?.tagName === 'SELECT') return;
            e.preventDefault();
            highlightItemAt(activeResultIndex + 1);
            return;
        }

        if (e.key === 'ArrowUp') {
            if (document.activeElement?.tagName === 'SELECT') return;
            e.preventDefault();
            highlightItemAt(activeResultIndex <= 0 ? getNavigableItems().length - 1 : activeResultIndex - 1);
            return;
        }

        if (e.key === 'Enter' && document.activeElement === queryInput) {
            const items = getNavigableItems();
            if (activeResultIndex >= 0 && items[activeResultIndex]) {
                e.preventDefault();
                items[activeResultIndex].click();
            } else if (items.length > 0 && hasAnyActiveInput()) {
                e.preventDefault();
                items[0].click();
            } else if (hasAnyActiveInput()) {
                e.preventDefault();
                if (queryInput.value) {
                    saveRecentSearch(queryInput.value);
                }
                window.location.href = buildTransactionsUrl();
            }
        }
    });
}

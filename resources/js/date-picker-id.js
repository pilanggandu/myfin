const MONTH_NAMES_ID = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const DAY_NAMES_ID = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

function isoToId(isoStr) {
    if (!isoStr || typeof isoStr !== 'string') return '';
    const match = isoStr.trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return '';
    const [, y, m, d] = match;
    return `${d}/${m}/${y}`;
}

function idToIso(idStr) {
    if (!idStr || typeof idStr !== 'string') return '';
    const match = idStr.trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (!match) return '';
    const [, d, m, y] = match;
    const dNum = parseInt(d, 10);
    const mNum = parseInt(m, 10);
    const yNum = parseInt(y, 10);
    if (mNum < 1 || mNum > 12) return '';
    if (dNum < 1 || dNum > 31) return '';
    if (yNum < 1900 || yNum > 2100) return '';
    const dateObj = new Date(yNum, mNum - 1, dNum);
    if (dateObj.getFullYear() !== yNum || dateObj.getMonth() !== mNum - 1 || dateObj.getDate() !== dNum) {
        return '';
    }
    return `${yNum}-${String(mNum).padStart(2, '0')}-${String(dNum).padStart(2, '0')}`;
}

export function enhanceDateInput(el) {
    if (!el || el.dataset.idDatePickerInitialized) return;
    el.dataset.idDatePickerInitialized = 'true';

    const originalValue = el.value || '';
    const isRequired = el.hasAttribute('required');
    if (isRequired) {
        el.removeAttribute('required');
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'arus-date-picker-wrapper relative inline-block w-full';
    el.parentNode.insertBefore(wrapper, el);
    wrapper.appendChild(el);

    el.style.position = 'absolute';
    el.style.opacity = '0';
    el.style.width = '1px';
    el.style.height = '1px';
    el.style.pointerEvents = 'none';
    el.style.zIndex = '-1';
    el.tabIndex = -1;

    const visibleInput = document.createElement('input');
    visibleInput.type = 'text';
    visibleInput.inputMode = 'numeric';
    visibleInput.placeholder = 'tgl/bln/thn';
    visibleInput.maxLength = 10;

    let baseClasses = el.className || 'field-input';
    visibleInput.className = baseClasses + ' pr-10 cursor-pointer';
    if (isRequired) {
        visibleInput.required = true;
    }
    if (el.disabled) visibleInput.disabled = true;
    if (el.readOnly) visibleInput.readOnly = true;

    visibleInput.value = isoToId(originalValue);

    const calendarBtn = document.createElement('button');
    calendarBtn.type = 'button';
    calendarBtn.className = 'absolute right-2.5 top-1/2 -translate-y-1/2 text-muted hover:text-forest transition-colors p-1 cursor-pointer focus:outline-none';
    calendarBtn.title = 'Pilih Tanggal (tgl/bln/thn)';
    calendarBtn.innerHTML = `
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
        </svg>
    `;

    wrapper.appendChild(visibleInput);
    wrapper.appendChild(calendarBtn);

    const popup = document.createElement('div');
    popup.className = 'arus-calendar-popup fixed z-50 bg-panel border border-line rounded-2xl shadow-2xl p-3.5 w-[280px] hidden text-charcoal select-none';
    popup.style.display = 'none';
    document.body.appendChild(popup);

    let viewYear = new Date().getFullYear();
    let viewMonth = new Date().getMonth();

    if (originalValue) {
        const parts = originalValue.split('-');
        if (parts.length === 3) {
            viewYear = parseInt(parts[0], 10);
            viewMonth = parseInt(parts[1], 10) - 1;
        }
    }

    function setOriginalValue(isoStr, triggerEvents = true) {
        const nativeSetter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')?.set;
        if (nativeSetter) {
            nativeSetter.call(el, isoStr);
        } else {
            el.value = isoStr;
        }
        visibleInput.value = isoToId(isoStr);
        if (triggerEvents) {
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    const nativeSetter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')?.set;
    const nativeGetter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')?.get;
    Object.defineProperty(el, 'value', {
        get() {
            return nativeGetter ? nativeGetter.call(el) : el.getAttribute('value');
        },
        set(val) {
            if (nativeSetter) {
                nativeSetter.call(el, val);
            }
            visibleInput.value = isoToId(val);
        },
        configurable: true
    });

    function positionPopup() {
        const rect = visibleInput.getBoundingClientRect();
        const popupWidth = 280;
        const popupHeight = 320;

        let top = rect.bottom + 6;
        let left = rect.left;

        if (top + popupHeight > window.innerHeight) {
            top = Math.max(10, rect.top - popupHeight - 6);
        }
        if (left + popupWidth > window.innerWidth) {
            left = Math.max(10, window.innerWidth - popupWidth - 10);
        }

        popup.style.top = `${top}px`;
        popup.style.left = `${left}px`;
    }

    function renderCalendar() {
        popup.innerHTML = '';

        const header = document.createElement('div');
        header.className = 'flex items-center justify-between pb-2 mb-2 border-b border-line text-xs font-bold';

        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'w-7 h-7 rounded-lg flex items-center justify-center hover:bg-cream transition cursor-pointer text-muted hover:text-charcoal';
        prevBtn.innerHTML = '‹';
        prevBtn.addEventListener('click', (ev) => {
            ev.stopPropagation();
            viewMonth--;
            if (viewMonth < 0) {
                viewMonth = 11;
                viewYear--;
            }
            renderCalendar();
        });

        const title = document.createElement('div');
        title.className = 'text-charcoal font-bold';
        title.textContent = `${MONTH_NAMES_ID[viewMonth]} ${viewYear}`;

        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'w-7 h-7 rounded-lg flex items-center justify-center hover:bg-cream transition cursor-pointer text-muted hover:text-charcoal';
        nextBtn.innerHTML = '›';
        nextBtn.addEventListener('click', (ev) => {
            ev.stopPropagation();
            viewMonth++;
            if (viewMonth > 11) {
                viewMonth = 0;
                viewYear++;
            }
            renderCalendar();
        });

        header.appendChild(prevBtn);
        header.appendChild(title);
        header.appendChild(nextBtn);
        popup.appendChild(header);

        const daysHeader = document.createElement('div');
        daysHeader.className = 'grid grid-cols-7 gap-1 text-center font-bold text-[10px] text-muted mb-1.5';
        DAY_NAMES_ID.forEach(name => {
            const dayEl = document.createElement('div');
            dayEl.textContent = name;
            daysHeader.appendChild(dayEl);
        });
        popup.appendChild(daysHeader);

        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-7 gap-1 text-center text-xs';

        const firstDayIndex = (new Date(viewYear, viewMonth, 1).getDay() + 6) % 7;
        const totalDays = new Date(viewYear, viewMonth + 1, 0).getDate();
        const prevMonthTotalDays = new Date(viewYear, viewMonth, 0).getDate();

        const currentIso = el.value || '';
        const today = new Date();
        const todayIso = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

        for (let i = firstDayIndex - 1; i >= 0; i--) {
            const cell = document.createElement('div');
            cell.className = 'h-7 flex items-center justify-center text-[11px] text-muted opacity-30 select-none';
            cell.textContent = prevMonthTotalDays - i;
            grid.appendChild(cell);
        }

        for (let d = 1; d <= totalDays; d++) {
            const cell = document.createElement('button');
            cell.type = 'button';
            const cellIso = `${viewYear}-${String(viewMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const isSelected = cellIso === currentIso;
            const isToday = cellIso === todayIso;

            cell.className = 'h-7 rounded-lg flex items-center justify-center text-xs font-semibold cursor-pointer transition-colors';
            if (isSelected) {
                cell.className += ' bg-forest text-white font-bold shadow-xs';
            } else if (isToday) {
                cell.className += ' border border-forest text-forest font-bold hover:bg-forest-light';
            } else {
                cell.className += ' hover:bg-cream text-charcoal';
            }
            cell.textContent = d;

            cell.addEventListener('click', (ev) => {
                ev.stopPropagation();
                setOriginalValue(cellIso, true);
                closePopup();
            });

            grid.appendChild(cell);
        }

        const remainingCells = 42 - (firstDayIndex + totalDays);
        if (remainingCells < 7) {
            for (let i = 1; i <= remainingCells; i++) {
                const cell = document.createElement('div');
                cell.className = 'h-7 flex items-center justify-center text-[11px] text-muted opacity-30 select-none';
                cell.textContent = i;
                grid.appendChild(cell);
            }
        }

        popup.appendChild(grid);

        const footer = document.createElement('div');
        footer.className = 'flex items-center justify-between pt-2.5 mt-2.5 border-t border-line text-xs';

        const todayBtn = document.createElement('button');
        todayBtn.type = 'button';
        todayBtn.className = 'btn-arus-secondary text-[11px] py-1 px-2.5 font-bold';
        todayBtn.textContent = 'Hari Ini';
        todayBtn.addEventListener('click', (ev) => {
            ev.stopPropagation();
            setOriginalValue(todayIso, true);
            viewYear = today.getFullYear();
            viewMonth = today.getMonth();
            closePopup();
        });

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'text-[11px] text-muted hover:text-charcoal px-2 py-1';
        clearBtn.textContent = 'Batal';
        clearBtn.addEventListener('click', (ev) => {
            ev.stopPropagation();
            closePopup();
        });

        footer.appendChild(todayBtn);
        footer.appendChild(clearBtn);
        popup.appendChild(footer);
    }

    function openPopup() {
        if (popup.style.display === 'block') {
            closePopup();
            return;
        }
        document.querySelectorAll('.arus-calendar-popup').forEach(p => {
            p.style.display = 'none';
        });

        if (el.value) {
            const parts = el.value.split('-');
            if (parts.length === 3) {
                viewYear = parseInt(parts[0], 10);
                viewMonth = parseInt(parts[1], 10) - 1;
            }
        }
        renderCalendar();
        positionPopup();
        popup.style.display = 'block';
    }

    function closePopup() {
        popup.style.display = 'none';
    }

    calendarBtn.addEventListener('click', (ev) => {
        ev.stopPropagation();
        openPopup();
    });

    visibleInput.addEventListener('click', (ev) => {
        ev.stopPropagation();
        openPopup();
    });

    visibleInput.addEventListener('input', () => {
        let v = visibleInput.value.replace(/\D/g, '');
        if (v.length > 8) v = v.substring(0, 8);

        let formatted = '';
        if (v.length > 0) {
            formatted += v.substring(0, 2);
        }
        if (v.length >= 3) {
            formatted += '/' + v.substring(2, 4);
        }
        if (v.length >= 5) {
            formatted += '/' + v.substring(4, 8);
        }

        visibleInput.value = formatted;

        if (formatted.length === 10) {
            const iso = idToIso(formatted);
            if (iso) {
                setOriginalValue(iso, true);
                const parts = iso.split('-');
                viewYear = parseInt(parts[0], 10);
                viewMonth = parseInt(parts[1], 10) - 1;
            }
        } else if (formatted.length === 0) {
            setOriginalValue('', true);
        }
    });

    visibleInput.addEventListener('blur', () => {
        if (visibleInput.value.length > 0 && visibleInput.value.length < 10) {
            if (el.value) {
                visibleInput.value = isoToId(el.value);
            } else {
                visibleInput.value = '';
            }
        }
    });

    document.addEventListener('click', (ev) => {
        if (!wrapper.contains(ev.target) && !popup.contains(ev.target)) {
            closePopup();
        }
    });

    window.addEventListener('resize', () => {
        if (popup.style.display === 'block') {
            positionPopup();
        }
    });

    window.addEventListener('scroll', () => {
        if (popup.style.display === 'block') {
            positionPopup();
        }
    }, true);

    document.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape' && popup.style.display === 'block') {
            closePopup();
        }
    });
}

export function initIndonesianDatePickers(container = document) {
    const inputs = container.querySelectorAll('input[type="date"]');
    inputs.forEach(input => enhanceDateInput(input));
}

window.initIndonesianDatePickers = initIndonesianDatePickers;
window.enhanceDateInput = enhanceDateInput;

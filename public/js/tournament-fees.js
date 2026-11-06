(() => {
    'use strict';

    // Dobna skupina dolazi sa servera; spol natjecateljske kategorije se ne mijenja.
    window.SKDEntryFees = {
        amount(rates, group) {
            const rate = rates.find(item => Array.isArray(item.kategorije)
                && (item.kategorije.includes(group) || (rates.length === 1 && item.kategorije.length === 7)));
            return rate && rate.iznos !== null && rate.iznos !== '' ? String(rate.iznos) : '';
        },
        label(amount) {
            const number = Number(String(amount).replace(',', '.'));
            return amount !== '' && Number.isFinite(number)
                ? number.toLocaleString('hr-HR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' EUR'
                : '';
        },
    };

    function initEditors() {
        document.querySelectorAll('[data-fee-editor]').forEach((editor, editorIndex) => {
            const labels = JSON.parse(editor.dataset.categories);
            const keys = Object.keys(labels);
            const initial = JSON.parse(editor.dataset.rates);
            const states = initial.map(rate => ({groups: [...(rate.kategorije || [])], amount: rate.iznos || '', all: false}));
            let remaining = [...keys];
            states.forEach(state => {
                state.all = remaining.every(key => state.groups.includes(key));
                remaining = remaining.filter(key => !state.groups.includes(key));
            });
            const form = editor.closest('form');
            const method = form.querySelector('[name="kotizacija_nacin"]');
            const rows = [];

            for (let i = 0; i < keys.length; i++) {
                const row = document.createElement('div');
                row.className = 'row g-2 align-items-end mb-2';
                const amountCol = document.createElement('div');
                amountCol.className = 'col-lg-3';
                const amountLabel = document.createElement('label');
                amountLabel.className = 'form-label mb-1';
                const id = 'fee-' + editorIndex + '-' + i;
                amountLabel.htmlFor = id;
                amountLabel.textContent = 'Kotizacija ' + (i + 1) + ' (EUR)';
                const amount = document.createElement('input');
                amount.type = 'text'; amount.inputMode = 'decimal'; amount.className = 'form-control';
                amount.id = id; amount.name = `kotizacija_razredi[${i}][iznos]`;
                amount.placeholder = 'npr. 15,00';
                amount.value = states[i]?.amount || '';
                amount.addEventListener('input', () => { states[i].amount = amount.value; });
                amountCol.append(amountLabel, amount);
                const categoryCol = document.createElement('div');
                categoryCol.className = 'col-lg-6';
                const categoryLabel = document.createElement('label');
                categoryLabel.className = 'form-label mb-1'; categoryLabel.textContent = 'Kategorije';
                const dropdown = document.createElement('div'); dropdown.className = 'dropdown';
                const button = document.createElement('button');
                button.type = 'button'; button.className = 'btn btn-outline-secondary dropdown-toggle w-100 text-start';
                button.id = id + '-categories'; categoryLabel.htmlFor = button.id;
                button.dataset.bsToggle = 'dropdown'; button.dataset.bsAutoClose = 'outside';
                button.setAttribute('aria-expanded', 'false');
                const menu = document.createElement('div');
                menu.className = 'dropdown-menu p-3 w-100';
                menu.style.maxHeight = '22rem'; menu.style.overflowY = 'auto';
                const checkboxes = [];
                [null, ...keys].forEach(key => {
                    const label = document.createElement('label');
                    label.className = 'd-flex align-items-center gap-2 py-1';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox'; checkbox.className = 'form-check-input m-0';
                    if (key !== null) {
                        checkbox.name = `kotizacija_razredi[${i}][kategorije][]`; checkbox.value = key;
                    }
                    const text = document.createElement('span');
                    text.textContent = key === null ? (i === 0 ? 'Sve' : 'Sve preostale') : labels[key];
                    label.append(checkbox, text); menu.append(label);
                    checkboxes.push({key, checkbox, label});
                    checkbox.addEventListener('change', () => {
                        const used = states.slice(0, i).flatMap(state => state.groups);
                        const available = keys.filter(group => !used.includes(group));
                        if (key === null) {
                            states[i].all = checkbox.checked;
                            states[i].groups = checkbox.checked ? available : [];
                        } else {
                            states[i].groups = available.filter(group => checkboxes.some(item => item.key === group && item.checkbox.checked));
                            states[i].all = available.every(group => states[i].groups.includes(group));
                        }
                        update();
                    });
                });
                dropdown.append(button, menu); categoryCol.append(categoryLabel, dropdown);
                row.append(amountCol, categoryCol); editor.querySelector('[data-fee-rows]').append(row);
                rows.push({row, amount, button, checkboxes});
            }

            function update() {
                const enabled = ['bank', 'cash'].includes(method.value);
                editor.classList.toggle('d-none', !enabled);
                let available = [...keys];
                let active = true;
                rows.forEach(({row, amount, button, checkboxes}, i) => {
                    const visible = active && available.length > 0;
                    row.hidden = !visible;
                    if (visible) {
                        if (!states[i]) states[i] = {groups: [...available], amount: '', all: true};
                        const state = states[i];
                        state.groups = state.all ? [...available] : state.groups.filter(key => available.includes(key));
                        const all = available.every(key => state.groups.includes(key));
                        button.textContent = all ? (i === 0 ? 'Sve' : 'Sve preostale') : state.groups.map(key => labels[key]).join(', ') || 'Odaberi kategorije';
                        checkboxes.forEach(({key, checkbox, label}) => {
                            label.hidden = key !== null && !available.includes(key);
                            label.classList.toggle('d-none', label.hidden);
                            checkbox.disabled = !enabled || label.hidden;
                            checkbox.checked = key === null ? all : state.groups.includes(key);
                        });
                        amount.disabled = !enabled;
                        amount.value = state.amount;
                        active = state.groups.length > 0;
                        available = available.filter(key => !state.groups.includes(key));
                    } else {
                        amount.disabled = true;
                        checkboxes.forEach(({checkbox}) => {checkbox.disabled = true;});
                        states.splice(i);
                    }
                });
                const visibleCount = rows.filter(({row}) => !row.hidden).length;
                rows.forEach(({row, amount}) => {amount.required = enabled && !row.hidden && visibleCount > 1;});
            }
            method.addEventListener('change', update);
            update();
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initEditors);
    else initEditors();
})();

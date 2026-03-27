{{-- Widget "Klupski zid" (AJAX poruke) za naslovnicu. --}}
@php
    $mozePisatiKlupskiZid = $mozePisatiKlupskiZid ?? (auth()->check() && auth()->user()->imaPravoAdminMemberOrSchool());
    $mozeModeriratiKlupskiZid = $mozeModeriratiKlupskiZid ?? (auth()->check() && (int)auth()->user()->rola === 1);
    $klupskiZidNaslov = $klupskiZidNaslov ?? 'Klupski zid';
    $headerClass = $headerClass ?? 'row justify-content-center p-2 shadow bg-danger fw-bolder';
    $bodyClass = $bodyClass ?? 'row justify-content-start mb-3 pt-3 pb-2 shadow bg-white';
    $headerColumnClass = $headerColumnClass ?? 'col-lg-12 text-white';
    $bodyColumnClass = $bodyColumnClass ?? 'col-lg-12 justify-content-start';
@endphp

<div class="js-club-wall-widget club-wall-widget"
     data-list-url="{{ route('javno.klupski_zid.index') }}"
     data-store-url="{{ route('javno.klupski_zid.store') }}"
     data-destroy-url-template="{{ route('javno.klupski_zid.destroy', ['message' => '__ID__']) }}"
     data-highlight-url-template="{{ route('javno.klupski_zid.highlight', ['message' => '__ID__']) }}"
     data-can-post="{{ $mozePisatiKlupskiZid ? '1' : '0' }}"
     data-can-moderate="{{ $mozeModeriratiKlupskiZid ? '1' : '0' }}">
    <div class="{{ $headerClass }}">
        <div class="{{ $headerColumnClass }}">
            {{ $klupskiZidNaslov }}
        </div>
    </div>

    <div class="{{ $bodyClass }}">
        <div class="{{ $bodyColumnClass }}">
            <div class="club-wall-feed js-club-wall-feed mb-3" aria-live="polite">
                <div class="small text-muted">Učitavanje poruka...</div>
            </div>

            @if($mozePisatiKlupskiZid)
                <form class="js-club-wall-form mb-2" autocomplete="off">
                    <div class="input-group mb-2">
                        <input type="text"
                               class="form-control js-club-wall-input"
                               name="message"
                               maxlength="1000"
                               placeholder="Upišite poruku .... (max. 1000 znakova)"
                               required>
                        <button type="submit"
                                class="btn btn-danger js-club-wall-submit club-wall-send-btn"
                                aria-label="Pošalji poruku"
                                title="Pošalji poruku">➤</button>
                    </div>
                </form>
            @else
                <div class="alert alert-secondary py-2 px-3 mb-3">
                    <div class="small mb-0">Pisanje je omogućeno članovima, polaznicima škole, roditeljima i administratorima.</div>
                </div>
            @endif
            <div class="small text-muted mt-2 js-club-wall-status"></div>
        </div>
    </div>
</div>

@once
    <style>
        .club-wall-feed {
            height: 16rem;
            overflow-y: auto;
            padding-right: .2rem;
            border: 1px solid rgba(0, 0, 0, .12);
            border-radius: .35rem;
            padding: .45rem;
            background-color: rgba(0, 0, 0, .02);
        }

        .club-wall-item {
            border-bottom: 1px solid rgba(0, 0, 0, .14);
            padding: .45rem .2rem .55rem;
            margin: 0;
            background-color: transparent;
        }

        .club-wall-item-highlighted {
            background-color: rgba(var(--bs-primary-rgb), .08);
        }

        .club-wall-send-btn {
            min-width: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }

        .club-wall-item-highlighted .club-wall-message-text {
            font-weight: 700;
        }

        .club-wall-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .club-wall-author {
            font-weight: 700;
            margin: 0;
            line-height: 1.25;
        }

        .club-wall-time {
            font-size: .8rem;
            margin: 0;
            white-space: nowrap;
        }

        .club-wall-meta {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin-left: auto;
        }

        .club-wall-admin-actions {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .club-wall-icon-btn {
            width: 1.3rem;
            height: 1.3rem;
            border-radius: 999px;
            border: 1px solid rgba(0, 0, 0, .26);
            background: transparent;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .78rem;
            line-height: 1;
            color: #6c757d;
        }

        .club-wall-icon-btn:hover {
            background: rgba(0, 0, 0, .06);
        }

        .club-wall-icon-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .club-wall-icon-highlight.is-active {
            color: #198754;
            border-color: rgba(25, 135, 84, .55);
            background: rgba(25, 135, 84, .12);
        }

        .club-wall-icon-delete:hover {
            color: #dc3545;
            border-color: rgba(220, 53, 69, .5);
            background: rgba(220, 53, 69, .08);
        }

        .club-wall-message-text {
            margin-top: .35rem;
            line-height: 1.45;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .theme-dark .club-wall-item {
            border-bottom-color: rgba(255, 255, 255, .2);
            background-color: transparent;
        }

        .theme-dark .club-wall-feed {
            border-color: rgba(255, 255, 255, .2);
            background-color: rgba(255, 255, 255, .04);
        }

        .theme-dark .club-wall-item-highlighted {
            background-color: rgba(var(--bs-primary-rgb), .2);
        }

        .theme-dark .club-wall-icon-btn {
            border-color: rgba(255, 255, 255, .32);
            color: rgba(255, 255, 255, .72);
        }

        .theme-dark .club-wall-icon-btn:hover {
            background: rgba(255, 255, 255, .12);
        }

        .theme-dark .club-wall-icon-highlight.is-active {
            color: #4ade80;
            border-color: rgba(74, 222, 128, .55);
            background: rgba(74, 222, 128, .18);
        }

        .theme-dark .club-wall-icon-delete:hover {
            color: #f87171;
            border-color: rgba(248, 113, 113, .55);
            background: rgba(248, 113, 113, .16);
        }

        @media (min-width: 1400px) {
            .club-wall-time {
                display: none;
            }
        }
    </style>

    <script>
        (function () {
            const initClubWallWidget = () => {
                const widgets = Array.from(document.querySelectorAll('.js-club-wall-widget'));
                if (widgets.length === 0) {
                    return;
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const pollIntervalMs = 5000;
                let pollTimerId = null;
                let latestSignature = '';
                let fetchInFlight = false;

                const isVisible = (element) => {
                    if (!element || !element.isConnected) {
                        return false;
                    }

                    return element.offsetParent !== null;
                };

                const pickActiveWidget = () => {
                    for (const widget of widgets) {
                        if (isVisible(widget)) {
                            return widget;
                        }
                    }

                    return widgets[0] || null;
                };

                const setStatusMessage = (text) => {
                    for (const widget of widgets) {
                        const status = widget.querySelector('.js-club-wall-status');
                        if (status) {
                            status.textContent = text || '';
                        }
                    }
                };

                const setFormsEnabled = (enabled) => {
                    for (const widget of widgets) {
                        const form = widget.querySelector('.js-club-wall-form');
                        if (!form) {
                            continue;
                        }

                        const input = form.querySelector('.js-club-wall-input');
                        const submit = form.querySelector('.js-club-wall-submit');
                        if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {
                            input.disabled = !enabled;
                        }
                        if (submit instanceof HTMLButtonElement) {
                            submit.disabled = !enabled;
                        }
                    }
                };

                const urlWithId = (template, id) => template.replace('__ID__', String(id));

                const appendPlainTextWithNewlines = (parent, text) => {
                    const parts = String(text).split('\n');
                    parts.forEach((line, index) => {
                        if (line.length > 0) {
                            parent.appendChild(document.createTextNode(line));
                        }
                        if (index < parts.length - 1) {
                            parent.appendChild(document.createElement('br'));
                        }
                    });
                };

                const appendLinkifiedText = (parent, text) => {
                    const input = String(text || '');
                    const regex = /https?:\/\/[^\s]+/gi;
                    let cursor = 0;
                    let match;

                    while ((match = regex.exec(input)) !== null) {
                        const start = match.index;
                        const rawUrl = match[0];

                        if (start > cursor) {
                            appendPlainTextWithNewlines(parent, input.slice(cursor, start));
                        }

                        let cleanUrl = rawUrl;
                        let suffix = '';
                        while (/[),.!?;:\]]$/.test(cleanUrl)) {
                            suffix = cleanUrl.slice(-1) + suffix;
                            cleanUrl = cleanUrl.slice(0, -1);
                        }

                        let validUrl = false;
                        try {
                            const parsed = new URL(cleanUrl);
                            validUrl = parsed.protocol === 'http:' || parsed.protocol === 'https:';
                        } catch (error) {
                            validUrl = false;
                        }

                        if (validUrl) {
                            const link = document.createElement('a');
                            link.href = cleanUrl;
                            link.target = '_blank';
                            link.rel = 'noopener noreferrer';
                            link.textContent = cleanUrl;
                            parent.appendChild(link);
                        } else {
                            parent.appendChild(document.createTextNode(rawUrl));
                        }

                        if (suffix.length > 0) {
                            parent.appendChild(document.createTextNode(suffix));
                        }

                        cursor = start + rawUrl.length;
                    }

                    if (cursor < input.length) {
                        appendPlainTextWithNewlines(parent, input.slice(cursor));
                    }
                };

                const setHighlightButtonState = (button, highlighted) => {
                    const active = Boolean(highlighted);
                    button.classList.toggle('is-active', active);
                    button.title = active ? 'Makni isticanje' : 'Istakni poruku';
                    button.setAttribute('aria-label', active ? 'Makni isticanje poruke' : 'Istakni poruku');
                };

                const createAdminActions = (widget, message) => {
                    const actions = document.createElement('div');
                    actions.className = 'club-wall-admin-actions';

                    const highlightButton = document.createElement('button');
                    highlightButton.type = 'button';
                    highlightButton.className = 'club-wall-icon-btn club-wall-icon-highlight';
                    highlightButton.textContent = '✎';
                    setHighlightButtonState(highlightButton, message.highlighted);
                    highlightButton.addEventListener('click', async () => {
                        const template = widget.dataset.highlightUrlTemplate || '';
                        if (template === '') {
                            return;
                        }

                        highlightButton.disabled = true;
                        try {
                            await postAction(urlWithId(template, message.id));
                        } finally {
                            highlightButton.disabled = false;
                        }
                    });

                    const deleteButton = document.createElement('button');
                    deleteButton.type = 'button';
                    deleteButton.className = 'club-wall-icon-btn club-wall-icon-delete';
                    deleteButton.textContent = '✕';
                    deleteButton.title = 'Obriši poruku';
                    deleteButton.setAttribute('aria-label', 'Obriši poruku');
                    deleteButton.addEventListener('click', async () => {
                        const template = widget.dataset.destroyUrlTemplate || '';
                        if (template === '') {
                            return;
                        }

                        if (!window.confirm('Obrisati ovu poruku?')) {
                            return;
                        }

                        deleteButton.disabled = true;
                        try {
                            await postAction(urlWithId(template, message.id));
                        } finally {
                            deleteButton.disabled = false;
                        }
                    });

                    actions.appendChild(highlightButton);
                    actions.appendChild(deleteButton);

                    return actions;
                };

                const renderMessagesOnWidget = (widget, payload) => {
                const feed = widget.querySelector('.js-club-wall-feed');
                if (!feed) {
                    return;
                }

                feed.innerHTML = '';
                const messages = Array.isArray(payload.messages) ? payload.messages : [];
                if (messages.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'small text-muted';
                    empty.textContent = 'Nema poruka.';
                    feed.appendChild(empty);
                    return;
                }

                const canModerate = Boolean(payload.canModerate);
                for (const message of messages) {
                    const item = document.createElement('div');
                    item.className = 'club-wall-item';
                    if (message.highlighted) {
                        item.classList.add('club-wall-item-highlighted');
                    }

                    const top = document.createElement('div');
                    top.className = 'd-flex align-items-start justify-content-between gap-2';

                    const author = document.createElement(message.authorProfileUrl ? 'a' : 'span');
                    author.className = 'club-wall-author';
                    author.textContent = message.authorName || 'Nepoznati korisnik';
                    if (message.authorProfileUrl) {
                        author.href = message.authorProfileUrl;
                    }

                    const meta = document.createElement('div');
                    meta.className = 'club-wall-meta';

                    const time = document.createElement('p');
                    time.className = 'club-wall-time text-muted';
                    time.textContent = message.createdAt || '';

                    top.appendChild(author);
                    meta.appendChild(time);
                    if (canModerate) {
                        meta.appendChild(createAdminActions(widget, message));
                    }
                    top.appendChild(meta);
                    item.appendChild(top);

                    const text = document.createElement('div');
                    text.className = 'club-wall-message-text';
                    appendLinkifiedText(text, message.text || '');
                    item.appendChild(text);

                    feed.appendChild(item);
                }
                };

                const applyPayloadToAll = (payload) => {
                if (!payload || typeof payload !== 'object') {
                    return;
                }

                const signature = String(payload.signature || '');
                if (signature !== '' && signature === latestSignature) {
                    return;
                }

                latestSignature = signature;
                for (const widget of widgets) {
                    renderMessagesOnWidget(widget, payload);
                }

                if (payload.disabled) {
                    setFormsEnabled(false);
                    setStatusMessage(payload.disabledReason || 'Klupski zid trenutno nije dostupan.');
                } else {
                    setFormsEnabled(true);
                }
                };

                const parseJson = async (response) => {
                let data = null;
                try {
                    data = await response.json();
                } catch (error) {
                    data = null;
                }

                if (!response.ok) {
                    let message = data?.message || '';
                    if (message === '') {
                        if (response.status === 401) {
                            message = 'Potrebna je prijava korisnika.';
                        } else if (response.status === 403) {
                            message = 'Nemate pravo za ovu akciju.';
                        } else if (response.status === 419) {
                            message = 'Sesija je istekla. Osvježite stranicu i pokušajte ponovno.';
                        } else if (response.status >= 500) {
                            message = 'Greška na serveru. Pokušajte ponovno kroz nekoliko sekundi.';
                        } else {
                            message = 'Zahtjev nije uspio.';
                        }
                    }
                    throw new Error(message);
                }

                return data || {};
                };

                const loadMessages = async (loudError = false) => {
                if (fetchInFlight) {
                    return;
                }

                const widget = pickActiveWidget();
                if (!widget) {
                    return;
                }

                const listUrl = widget.dataset.listUrl || '';
                if (listUrl === '') {
                    return;
                }

                fetchInFlight = true;
                try {
                    const response = await fetch(listUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        cache: 'no-store',
                    });

                    const payload = await parseJson(response);
                    if (!Array.isArray(payload.messages)) {
                        throw new Error('Neispravan odgovor servera.');
                    }
                    applyPayloadToAll(payload);
                    setStatusMessage('');
                } catch (error) {
                    if (loudError) {
                        setStatusMessage(error instanceof Error ? error.message : 'Greška pri učitavanju poruka.');
                    }
                } finally {
                    fetchInFlight = false;
                }
                };

                const postAction = async (url, body = {}) => {
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(body),
                });

                const payload = await parseJson(response);
                if (!Array.isArray(payload.messages)) {
                    throw new Error('Neispravan odgovor servera.');
                }
                applyPayloadToAll(payload);
                setStatusMessage('');
                };

                const bindForms = () => {
                    for (const widget of widgets) {
                        const form = widget.querySelector('.js-club-wall-form');
                        if (!form) {
                            continue;
                        }

                        const input = form.querySelector('.js-club-wall-input');
                        const submitButton = form.querySelector('.js-club-wall-submit');
                        if (!(input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) || !(submitButton instanceof HTMLButtonElement)) {
                            continue;
                        }

                        form.addEventListener('submit', async (event) => {
                            event.preventDefault();

                            const storeUrl = widget.dataset.storeUrl || '';
                            const message = input.value.trim();
                            if (storeUrl === '' || message === '') {
                                return;
                            }

                            input.disabled = true;
                            submitButton.disabled = true;
                            try {
                                await postAction(storeUrl, {message});
                                input.value = '';
                                setStatusMessage('');
                            } catch (error) {
                                setStatusMessage(error instanceof Error ? error.message : 'Spremanje poruke nije uspjelo.');
                            } finally {
                                input.disabled = false;
                                submitButton.disabled = false;
                                input.focus();
                            }
                        });
                    }
                };

                bindForms();
                void loadMessages(true);

                pollTimerId = window.setInterval(() => {
                    void loadMessages(false);
                }, pollIntervalMs);

                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        void loadMessages(false);
                    }
                });

                window.addEventListener('beforeunload', () => {
                    if (pollTimerId !== null) {
                        window.clearInterval(pollTimerId);
                    }
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initClubWallWidget, {once: true});
            } else {
                initClubWallWidget();
            }
        })();
    </script>
@endonce

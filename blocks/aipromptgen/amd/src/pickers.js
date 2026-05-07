define([], function() {
    const $ = function(sel, root) {
        root = root || document;
        return root.querySelector(sel);
    };
    const $$ = function(sel, root) {
        root = root || document;
        return Array.from(root.querySelectorAll(sel));
    };

    const attachPicker = function(config) {
        const openId = config.openId;
        const modalId = config.modalId;
        const closeId = config.closeId;
        const cancelId = config.cancelId;
        const itemSelector = config.itemSelector;
        const targetId = config.targetId;
        const backdropId = config.backdropId || 'ai4t-modal-backdrop';

        const openBtn = document.getElementById(openId);
        const modal = document.getElementById(modalId);
        const backdrop = document.getElementById(backdropId);
        const closeBtn = document.getElementById(closeId);
        const cancelBtn = document.getElementById(cancelId);
        const target = document.getElementById(targetId);

        const open = function() {
            if (modal && backdrop) {
                modal.style.display = 'block';
                backdrop.style.display = 'block';
                modal.focus();
            }
        };
        const close = function() {
            if (modal && backdrop) {
                modal.style.display = 'none';
                backdrop.style.display = 'none';
            }
        };
        const pick = function(el) {
            const v = el.getAttribute('data-value');
            if (target && v !== null) {
                target.value = v;
                target.dispatchEvent(new Event('input', {bubbles: true}));
            }
            close();
        };

        if (openBtn) {
            openBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                open();
            });
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', close);
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', close);
        }
        if (backdrop) {
            backdrop.addEventListener('click', close);
        }
        document.addEventListener('keydown', function(ev) {
            if (ev.key === 'Escape') {
                close();
            }
        });
        $$(itemSelector).forEach(function(li) {
            li.addEventListener('click', function() {
                pick(li);
            });
            li.addEventListener('keydown', function(ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    pick(li);
                }
            });
        });
    };

    const attachOutcomesModal = function() {
        const openBtn = $('#ai4t-outcomes-browse');
        const modal = $('#ai4t-outcomes-modal');
        const backdrop = $('#ai4t-modal-backdrop');
        const closeBtn = $('#ai4t-outcomes-modal-close');
        const cancelBtn = $('#ai4t-outcomes-modal-cancel');
        const insertBtn = $('#ai4t-outcomes-modal-insert');
        const ta = $('#id_outcomes');

        const open = function() {
            if (modal && backdrop) {
                modal.style.display = 'block';
                backdrop.style.display = 'block';
                modal.focus();
            }
        };
        const close = function() {
            if (modal && backdrop) {
                modal.style.display = 'none';
                backdrop.style.display = 'none';
            }
        };

        if (openBtn) {
            openBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                open();
            });
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', close);
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', close);
        }
        if (insertBtn) {
            insertBtn.addEventListener('click', function() {
                if (!ta) {
                    close();
                    return;
                }
                const vals = $$('.ai4t-outcome-checkbox:checked').map(function(cb) {
                    return cb.value;
                }).filter(Boolean);
                if (!vals.length) {
                    close();
                    return;
                }
                let cur = ta.value || '';
                if (cur && !/\n$/.test(cur)) {
                    cur += '\n';
                }
                ta.value = cur + vals.join('\n');
                ta.dispatchEvent(new Event('input', {bubbles: true}));
                close();
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', close);
        }
    };

    const initLanguageModal = function() {
        const openBtn = $('#ai4t-language-browse');
        const modal = $('#ai4t-language-modal');
        const backdrop = $('#ai4t-modal-backdrop');
        const closeBtn = $('#ai4t-language-modal-close');
        const cancelBtn = $('#ai4t-language-modal-cancel');
        const input = $('#id_language');
        const codeEl = $('#id_languagecode');

        const open = function() {
            if (modal && backdrop) {
                modal.style.display = 'block';
                backdrop.style.display = 'block';
                modal.focus();
            }
        };
        const close = function() {
            if (modal && backdrop) {
                modal.style.display = 'none';
                backdrop.style.display = 'none';
            }
        };
        const sync = function() {
            if (!input || !codeEl) {
                return;
            }
            const t = (input.value || '').trim();
            if (!t) {
                return;
            }
            const m = t.match(/\(([a-z]{2,3}(?:[_-][a-z]{2,3})?)\)/i);
            if (m) {
                codeEl.value = m[1].replace('-', '_').toLowerCase();
                return;
            }
            $$('.ai4t-language-item').some(function(li) {
                const name = li.getAttribute('data-name') || '';
                if (name.toLowerCase() === t.toLowerCase()) {
                    codeEl.value = li.getAttribute('data-code');
                    return true;
                }
                return false;
            });
        };

        if (openBtn) {
            openBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                open();
            });
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', close);
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', close);
        }
        if (backdrop) {
            backdrop.addEventListener('click', close);
        }
        $$('.ai4t-language-item').forEach(function(li) {
            li.addEventListener('click', function() {
                if (input) {
                    input.value = li.getAttribute('data-name') || '';
                    input.dispatchEvent(new Event('input', {bubbles: true}));
                }
                if (codeEl) {
                    codeEl.value = li.getAttribute('data-code') || '';
                }
                close();
            });
        });
        if (input) {
            input.addEventListener('blur', sync);
            input.addEventListener('change', sync);
        }
    };

    return {
        attachPicker: attachPicker,
        attachOutcomesModal: attachOutcomesModal,
        initLanguageModal: initLanguageModal
    };
});

define([], function() {
    const $ = function(sel, root) {
        root = root || document;
        return root.querySelector(sel);
    };

    const SimpleModal = function(id) {
        this.node = document.getElementById(id);
        this.backdrop = document.getElementById('ai4t-modal-backdrop');
    };
    SimpleModal.prototype.open = function(prefill) {
        if (!this.node) {
            return;
        }
        if (typeof prefill === 'function') {
            try {
                prefill();
            } catch (e) {
                // Silent fail.
            }
        }
        this.node.style.display = 'block';
        if (this.backdrop) {
            this.backdrop.style.display = 'block';
        }
        this.node.focus();
    };
    SimpleModal.prototype.close = function() {
        if (!this.node) {
            return;
        }
        this.node.style.display = 'none';
        if (this.backdrop) {
            this.backdrop.style.display = 'none';
        }
    };

    const initAgeModal = function() {
        const modal = new SimpleModal('ai4t-age-modal');
        const openBtn = $('#ai4t-age-browse');
        const closeBtn = $('#ai4t-age-modal-close');
        const cancelBtn = $('#ai4t-age-modal-cancel');
        const insertBtn = $('#ai4t-age-modal-insert');
        const input = $('#id_agerange');
        const exact = $('#ai4t-age-exact');
        const from = $('#ai4t-age-from');
        const to = $('#ai4t-age-to');
        const modeExact = $('#ai4t-age-mode-exact');
        const modeRange = $('#ai4t-age-mode-range');

        const syncEnabled = function() {
            const useExact = modeExact && modeExact.checked;
            if (useExact) {
                if (exact) {
                    exact.removeAttribute('disabled');
                }
                if (from) {
                    from.setAttribute('disabled', 'disabled');
                }
                if (to) {
                    to.setAttribute('disabled', 'disabled');
                }
            } else {
                if (exact) {
                    exact.setAttribute('disabled', 'disabled');
                }
                if (from) {
                    from.removeAttribute('disabled');
                }
                if (to) {
                    to.removeAttribute('disabled');
                }
            }
        };

        const prefill = function() {
            if (!input) {
                return;
            }
            const v = (input.value || '').trim();
            if (!v) {
                if (modeExact) {
                    modeExact.checked = true;
                }
                if (exact) {
                    exact.value = '';
                }
                if (from) {
                    from.value = '';
                }
                if (to) {
                    to.value = '';
                }
                syncEnabled();
                return;
            }
            if (/^\d+$/.test(v)) {
                if (exact) {
                    exact.value = v;
                }
                if (from) {
                    from.value = '';
                }
                if (to) {
                    to.value = '';
                }
                if (modeExact) {
                    modeExact.checked = true;
                }
                syncEnabled();
                return;
            }
            const m = v.match(/^\s*(\d+)\s*[-\u2013]\s*(\d+)\s*$/u);
            if (m) {
                if (exact) {
                    exact.value = '';
                }
                if (from) {
                    from.value = m[1];
                }
                if (to) {
                    to.value = m[2];
                }
                if (modeRange) {
                    modeRange.checked = true;
                }
                syncEnabled();
                return;
            }
        };

        const onInsert = function() {
            if (!input) {
                modal.close();
                return;
            }
            const ev = (exact && exact.value || '').trim();
            const fv = (from && from.value || '').trim();
            const tv = (to && to.value || '').trim();
            const useExact = modeExact && modeExact.checked;
            if (useExact && ev) {
                input.value = ev;
                modal.close();
                return;
            }
            if (!useExact && fv && tv) {
                let a = parseInt(fv, 10);
                let b = parseInt(tv, 10);
                if (!isNaN(a) && !isNaN(b)) {
                    if (a > b) {
                        const tmp = a;
                        a = b;
                        b = tmp;
                    }
                    input.value = a + '-' + b;
                    modal.close();
                    return;
                }
            }
            modal.close();
        };

        if (openBtn) {
            openBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                modal.open(prefill);
            });
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.close();
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                modal.close();
            });
        }
        if (insertBtn) {
            insertBtn.addEventListener('click', onInsert);
        }
        if (modeExact) {
            modeExact.addEventListener('change', syncEnabled);
        }
        if (modeRange) {
            modeRange.addEventListener('change', syncEnabled);
        }
        document.addEventListener('keydown', function(ev) {
            if (ev.key === 'Escape') {
                modal.close();
            }
        });
    };

    return {
        initAgeModal: initAgeModal
    };
});

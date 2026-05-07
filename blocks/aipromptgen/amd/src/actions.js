define(['core/str'], function(Str) {
    const $ = function(sel, root) {
        root = root || document;
        return root.querySelector(sel);
    };

    const attachCopyDownload = function() {
        const copyBtn = $('#ai4t-copy');
        const dlBtn = $('#ai4t-download');
        const sendBtn = $('#ai4t-sendtochat');
        const ta = $('#ai4t-generated');
        const form = document.querySelector('form.mform');
        const copied = $('#ai4t-copied');

        if (copyBtn) {
            copyBtn.addEventListener('click', function() {
                if (!ta) {
                    return;
                }
                ta.select();
                ta.setSelectionRange(0, ta.value.length);
                const copyPromise = navigator.clipboard && navigator.clipboard.writeText
                    ? navigator.clipboard.writeText(ta.value)
                    : Promise.resolve(document.execCommand('copy'));
                copyPromise.catch(function() {
                    // Silent fail.
                });
                if (copied) {
                    Str.get_string('form:copied', 'block_aipromptgen').then(function(s) {
                        copied.textContent = s;
                        copied.style.display = 'inline';
                        setTimeout(function() {
                            copied.style.display = 'none';
                        }, 1500);
                        return true;
                    }).catch(function() {
                        // Silent fail.
                    });
                }
            });
        }

        if (dlBtn) {
            dlBtn.addEventListener('click', function() {
                if (!ta) {
                    return;
                }
                const title = (document.querySelector('title') && document.querySelector('title').textContent) || 'prompt';
                const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                const blob = new Blob([ta.value || ''], {type: 'text/plain'});
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = slug + '-ai-prompt.txt';
                document.body.appendChild(a);
                a.click();
                setTimeout(function() {
                    URL.revokeObjectURL(a.href);
                    a.remove();
                }, 0);
            });
        }

        if (sendBtn) {
            sendBtn.addEventListener('click', function() {
                if (!form) {
                    return;
                }
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = 'sendtochat';
                i.value = '1';
                form.appendChild(i);
                form.submit();
            });
        }
    };

    return {
        attachCopyDownload: attachCopyDownload
    };
});

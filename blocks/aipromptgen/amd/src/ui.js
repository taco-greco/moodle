define([], function() {
    return {
        init: function() {
            require([
                'block_aipromptgen/age',
                'block_aipromptgen/pickers',
                'block_aipromptgen/actions',
                'block_aipromptgen/stream',
                'block_aipromptgen/markdown'
            ], function(Age, Pickers, Actions, Stream, Markdown) {

                // Tracks the currently selected quick template string (null = use default).
                var activeTemplate = null;

                var getFieldValues = function() {
                    var getValue = function(id) {
                        var el = document.getElementById(id);
                        return el ? el.value : '';
                    };
                    var subject = getValue('id_subject');
                    if (!subject) {
                        var subjectEl = document.getElementById('id_subject');
                        if (subjectEl && subjectEl.placeholder) {
                            subject = subjectEl.placeholder;
                        }
                    }
                    return {
                        subject: subject,
                        topic: getValue('id_topic'),
                        lesson: getValue('id_lesson'),
                        audience: getValue('id_audience') || getValue('id_agerange'),
                        outcomes: getValue('id_outcomes'),
                        style: getValue('id_classtype'),
                        purpose: getValue('id_purpose'),
                        language: getValue('id_language') || 'English',
                        age: getValue('id_agerange'),
                        count: getValue('id_lessoncount') || '1',
                        duration: getValue('id_lessonduration') || '45'
                    };
                };

                var applyFieldReplacements = function(template, fields) {
                    var result = template;
                    var replacements = {
                        'subject': fields.subject,
                        'topic': fields.topic,
                        'lesson': fields.lesson,
                        'audience': fields.audience,
                        'outcomes': fields.outcomes,
                        'style': fields.style,
                        'purpose': fields.purpose,
                        'language': fields.language
                    };
                    for (var key in replacements) {
                        var val = replacements[key] || '[' + key + ']';
                        var regex = new RegExp('\\{' + key + '\\}', 'gi');
                        result = result.replace(regex, val);
                    }
                    result = result.replace(/\\n/g, '\n');
                    return result;
                };

                var updatePrompt = function() {
                    var gen = document.getElementById('ai4t-generated');
                    if (!gen) {
                        return;
                    }
                    var fields = getFieldValues();
                    var p;
                    if (activeTemplate) {
                        // Re-apply the selected quick template with updated field values.
                        p = applyFieldReplacements(activeTemplate, fields);

                        // If the template itself doesn't explicitly mention the language placeholder,
                        // append the language instruction to the end.
                        if (activeTemplate.indexOf('{language}') === -1 && fields.language) {
                            p += "\n\nLanguage: " + fields.language;
                        }
                    } else {
                        p = "You are an expert teacher. Create a detailed lesson plan.\n";
                        p += "Subject: " + fields.subject + "\n";
                        if (fields.age) {
                            p += "Student Age: " + fields.age + " years old\n";
                        }
                        if (fields.topic) {
                            p += "Topic: " + fields.topic + "\n";
                        }
                        if (fields.lesson) {
                            p += "Lesson Title: " + fields.lesson + "\n";
                        }
                        p += "Number of lessons: " + fields.count + "\n";
                        p += "Duration per lesson: " + fields.duration + " minutes\n";
                        if (fields.style) {
                            p += "Class Type: " + fields.style + "\n";
                        }
                        if (fields.purpose) {
                            p += "Purpose: " + fields.purpose + "\n";
                        }
                        if (fields.audience) {
                            p += "Target Audience: " + fields.audience + "\n";
                        }
                        if (fields.outcomes) {
                            p += "Learning Outcomes/Competencies:\n" + fields.outcomes + "\n";
                        }
                        p += "Language: " + fields.language + "\n";
                        p += "\nPlease provide a structured lesson plan with objectives, activities, and timeline.";
                    }

                    gen.value = p;
                    // Trigger input event to update valid state of buttons.
                    gen.dispatchEvent(new Event('input', {bubbles: true}));
                };

                var initAutoUpdate = function() {
                    var ids = [
                        'id_subject', 'id_agerange', 'id_topic', 'id_lesson',
                        'id_lessoncount', 'id_lessonduration', 'id_classtype',
                        'id_purpose', 'id_audience', 'id_outcomes', 'id_language'
                    ];

                    ids.forEach(function(id) {
                        var el = document.getElementById(id);
                        if (el) {
                            el.addEventListener('input', updatePrompt);
                            el.addEventListener('change', updatePrompt);
                        }
                    });

                    // Initial update — wait for DOM to be fully rendered.
                    setTimeout(updatePrompt, 500);
                };


                // Editor detection logic removed.

                var initProviderSend = function() {
                    var sendBtn = document.getElementById('ai4t-sendtoai');
                    var select = document.getElementById('ai4t-provider');
                    var gen = document.getElementById('ai4t-generated');
                    var hidden = document.getElementById('ai4t-sendto');

                    if (!hidden && sendBtn) {
                        var form = document.getElementById('mform1') ||
                                   document.getElementById('promptform') ||
                                   sendBtn.closest('form');
                        if (form) {
                            hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'sendto';
                            hidden.id = 'ai4t-sendto';
                            form.appendChild(hidden);
                        }
                    }

                    if (!sendBtn || !select || !gen) {
                        return;
                    }

                    var refreshState = function() {
                        var opt = select.options[select.selectedIndex];
                        var unconfigured = opt && /✕\s*$/.test(opt.textContent || '');
                        sendBtn.disabled = (!gen.value.trim() || unconfigured);
                    };

                    select.addEventListener('change', refreshState);
                    gen.addEventListener('input', refreshState);

                    sendBtn.addEventListener('click', function(e) {
                        if (sendBtn.disabled) {
                            return;
                        }
                        var provider = select.value;
                        var form = document.getElementById('ai4t-send-form');

                        if (['ollama', 'gemini', 'claude', 'deepseek', 'custom'].indexOf(provider) !== -1) {
                            e.preventDefault();
                            var resp = document.getElementById('ai4t-airesponse-body') ||
                                       document.getElementById('ai4t-airesponse');

                            Stream.startStream(function() {
                                return form;
                            }, gen, hidden, resp, function() {
                                // No-op scroll.
                            });
                            return;
                        }

                        hidden.value = provider;
                        if (form) {
                            form.submit();
                        }
                    });
                    refreshState();
                };

                var initResponseModal = function() {
                    var modal = document.getElementById('ai4t-airesponse-modal');
                    if (!modal) {
                        return;
                    }

                    var bodyRaw = document.getElementById('ai4t-airesponse-body');
                    var bodyText = document.getElementById('ai4t-airesponse-text');
                    var bodyHtml = document.getElementById('ai4t-airesponse-html');
                    var bodyCode = document.getElementById('ai4t-airesponse-code');
                    var backdrop = document.getElementById('ai4t-modal-backdrop');

                    var setView = function(view) {
                        var btns = ['raw', 'text', 'html', 'rich'].map(function(v) {
                            return document.getElementById('ai4t-btn-' + v);
                        });
                        var bodies = [bodyRaw, bodyText, bodyHtml, bodyCode];

                        btns.forEach(function(btn) {
                            if (btn) {
                                btn.classList.remove('btn-secondary');
                                btn.classList.add('btn-outline-secondary');
                            }
                        });
                        bodies.forEach(function(b) {
                            if (b) {
                                b.style.display = 'none';
                            }
                        });

                        applyView(view, btns, bodies, bodyRaw, bodyText, bodyCode, bodyHtml, Markdown);
                    };

                    document.addEventListener('aipromptgen:open', function() {
                        // We might need to handle the case where the modal content is not yet generated.
                        // Actually, the user wants to GENERATE a prompt, so we should probably open
                        // the main prompt builder interface, NOT the result modal.
                        // Wait, looking at the block structure, the prompt builder is inline in the
                        // block content. If we are in the editor, we probably want a modal that
                        // CONTAINS the prompt builder form. For now, let's assume we are just opening
                        // the current interface if it was a modal, but the prompt builder is not a modal.

                        // Let's check if we have a "Prompt Builder Modal". We don't.
                        // We have an "AI Response Modal". To support editor integration properly,
                        // we need to move the form INTO a modal or similar.
                        // Currently the form is in the block content.

                        // Let's simulate clicking the "Open AI Prompt Builder" link if it exists
                        // (but that link usually leads to view.php).
                        // If we are on view.php, we are fine.
                        // If we are on a course page, the block is there.

                        // Creating a full modal for the form is a bigger refactor.
                        // Let's throw an alert/notice for now or try to focus the block.
                        var block = document.querySelector('.block_aipromptgen');
                        if (block) {
                            block.scrollIntoView({behavior: 'smooth'});
                            block.style.border = '2px solid #007bff';
                            setTimeout(function() {
                                block.style.border = '';
                            }, 2000);
                        } else {
                            // Fallback: If we are not on a page with the block, we can't easily generate.
                            // We should probably open a popup window to view.php?courseid=...
                            if (window.M && window.M.cfg && window.M.cfg.wwwroot) {
                                // We need a course ID.
                                var cid = '1'; // Default or try to find
                                if (window.M.cfg.courseId) {
                                    cid = window.M.cfg.courseId;
                                }

                                var url = window.M.cfg.wwwroot + '/blocks/aipromptgen/view.php?courseid=' + cid;
                                window.open(url, 'aipromptgen', 'width=800,height=800,scrollbars=yes,resizable=yes');
                            }
                        }
                    });

                    document.addEventListener('click', function(e) {
                        handleModalClick(e, modal, backdrop, {
                            bodyRaw: bodyRaw, bodyText: bodyText, bodyHtml: bodyHtml, bodyCode: bodyCode,
                            setView: setView, showStatus: showStatus, copyRichText: copyRichText
                        });
                    });

                    if (bodyRaw && bodyRaw.textContent.trim().length > 0) {
                        modal.style.display = 'block';
                        if (backdrop) {
                            backdrop.style.display = 'block';
                        }
                        setView('rich');

                    }
                };

                var applyView = function(view, btns, bodies, bodyRaw, bodyText, bodyCode, bodyHtml, Markdown) {
                    var map = {raw: 0, text: 1, html: 2, rich: 3};
                    var idx = map[view];
                    if (btns[idx]) {
                        btns[idx].classList.remove('btn-outline-secondary');
                        btns[idx].classList.add('btn-secondary');
                    }

                    if (view === 'raw' && bodyRaw) {
                        bodyRaw.style.display = 'block';
                    } else if (view === 'text' && bodyText) {
                        bodyText.style.display = 'block';
                        bodyText.textContent = Markdown.renderText(bodyRaw.textContent);
                    } else if (view === 'html' && bodyCode) {
                        bodyCode.style.display = 'block';
                        bodyCode.textContent = Markdown.renderMarkdown(bodyRaw.textContent);
                    } else if (view === 'rich' && bodyHtml) {
                        bodyHtml.style.display = 'block';
                        try {
                            bodyHtml.innerHTML = Markdown.renderMarkdown(bodyRaw.textContent);
                        } catch (e) {
                            bodyHtml.innerHTML = '<p>Error rendering Markdown.</p>';
                        }
                    }
                };

                var handleModalClick = function(e, modal, backdrop, refs) {
                    var btn = e.target.closest('button');
                    var t = e.target;
                    if (btn) {
                        if (btn.id === 'ai4t-btn-raw') {
                            refs.setView('raw');
                        } else if (btn.id === 'ai4t-btn-text') {
                            refs.setView('text');
                        } else if (btn.id === 'ai4t-btn-html') {
                            refs.setView('html');
                        } else if (btn.id === 'ai4t-btn-rich') {
                            refs.setView('rich');
                        } else if (btn.id === 'ai4t-airesponse-modal-close-btn') {
                            modal.style.display = 'none';
                            if (backdrop) {
                                backdrop.style.display = 'none';
                            }
                        } else if (btn.id === 'ai4t-airesponse-modal-copy-btn') {
                            handleCopy(refs);
                        } else if (btn.id === 'ai4t-airesponse-modal-insert-btn') {
                            handleInsert(refs, modal, backdrop);
                        }
                    }
                    if (t && t.id === 'ai4t-airesponse-modal-close') {
                        modal.style.display = 'none';
                        if (backdrop) {
                            backdrop.style.display = 'none';
                        }
                    }
                };

                var handleCopy = function(refs) {
                    if (refs.bodyHtml && refs.bodyHtml.style.display !== 'none') {
                        if (refs.copyRichText(refs.bodyHtml)) {
                            refs.showStatus('Copied as Rich Text!');
                        } else {
                            refs.showStatus('Copy failed');
                        }
                    } else {
                        var text = '';
                        if (refs.bodyRaw && refs.bodyRaw.style.display !== 'none') {
                            text = refs.bodyRaw.textContent;
                        } else if (refs.bodyText && refs.bodyText.style.display !== 'none') {
                            text = refs.bodyText.textContent;
                        } else if (refs.bodyCode && refs.bodyCode.style.display !== 'none') {
                            text = refs.bodyCode.textContent;
                        }

                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(text).then(function() {
                                refs.showStatus('Copied to clipboard!');
                                return;
                            }).catch(function() {
                                // Silent fail.
                            });
                        } else {
                            // Fallback
                            var ta = document.createElement('textarea');
                            ta.value = text;
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);
                            refs.showStatus('Copied!');
                        }
                    }
                };

                var showStatus = function(msg) {
                    var status = document.getElementById('ai4t-modal-copy-status');
                    if (status) {
                        status.textContent = msg;
                        status.style.display = 'inline';
                        setTimeout(function() {
                            status.style.display = 'none';
                        }, 2000);
                    } else {
                        window.console.log(msg);
                    }
                };

                var handleInsert = function() {
                    // Deprecated: Editor insertion logic removed.
                };

                var initTemplates = function() {
                    var templateSelect = document.getElementById('ai4t-template-select');
                    if (!templateSelect) {
                        return;
                    }

                    templateSelect.addEventListener('change', function() {
                        var templateIdx = templateSelect.value;

                        // If user picks "Choose", clear template and regenerate default.
                        if (!templateIdx) {
                            activeTemplate = null;
                            updatePrompt();
                            return;
                        }

                        var promptsDataStr = templateSelect.getAttribute('data-prompts');
                        if (!promptsDataStr) {
                            return;
                        }

                        var promptsData;
                        try {
                            promptsData = JSON.parse(promptsDataStr);
                        } catch (e) {
                            return;
                        }

                        var template = promptsData[templateIdx];
                        if (!template) {
                            return;
                        }

                        // Set active template and trigger updatePrompt to apply it.
                        activeTemplate = template;
                        updatePrompt();
                    });
                };


                var copyRichText = function(el) {
                    try {
                        var range = document.createRange();
                        range.selectNode(el);
                        var selection = window.getSelection();
                        selection.removeAllRanges();
                        selection.addRange(range);
                        var successful = document.execCommand('copy');
                        selection.removeAllRanges();
                        return successful;
                    } catch (e) {
                        return false;
                    }
                };

                // Initialize all modules
                var inits = [
                    function() {
                        Age.initAgeModal();
                    },
                    function() {
                        Pickers.attachPicker({
                            openId: 'ai4t-lesson-browse', modalId: 'ai4t-modal',
                            closeId: 'ai4t-modal-close', cancelId: 'ai4t-modal-cancel',
                            itemSelector: '.ai4t-lesson-item', targetId: 'id_lesson'
                        });
                        Pickers.attachPicker({
                            openId: 'ai4t-topic-browse', modalId: 'ai4t-topic-modal',
                            closeId: 'ai4t-topic-modal-close', cancelId: 'ai4t-topic-modal-cancel',
                            itemSelector: '.ai4t-topic-item', targetId: 'id_topic'
                        });
                        Pickers.attachOutcomesModal();
                        Pickers.initLanguageModal();
                        ['purpose', 'audience', 'classtype'].forEach(function(k) {
                            Pickers.attachPicker({
                                openId: 'ai4t-' + k + '-browse', modalId: 'ai4t-' + k + '-modal',
                                closeId: 'ai4t-' + k + '-modal-close', cancelId: 'ai4t-' + k + '-modal-cancel',
                                itemSelector: '.ai4t-' + k + '-item', targetId: 'id_' + k
                            });
                        });
                    },
                    function() {
                        Actions.attachCopyDownload();
                    },
                    function() {
                        initProviderSend();
                    },
                    function() {
                        initAutoUpdate();
                    },
                    function() {
                        initResponseModal();
                    },
                    function() {
                        initTemplates();
                    }
                ];

                inits.forEach(function(fn) {
                    try {
                        fn();
                    } catch (e) {
                        /* Silent fail */
                    }
                });
            });
        }
    };
});

define([], function() {
    /**
     * Helper to inject newlines before markdown blocks and fix common AI output issues.
     *
     * @param {string} text
     * @returns {string}
     */
    const autofixMarkdown = function(text) {
        if (!text) {
            return text;
        }
        let res = text;

        // 1. Fix clumped numbers like "cars.2. Machine" -> "cars.\n2. Machine"
        res = res.replace(/([.!?])(\s*)(\d+\.\s+)/g, '$1\n$3');

        // 2. Fix bold markers that wrap newlines or have spaces inside like "** Title **" or "**Title\n**"
        res = res.replace(/(\*\*)(?:\s*\n+\s*|\s+)/g, '$1'); // Remove space/newline after opening **
        res = res.replace(/(?:\s*\n+\s*|\s+)(\*\*)/g, '$1'); // Remove space/newline before closing **

        // 3. Fix broken bold markers from AI like "* *" -> "**"
        res = res.replace(/\*\s+\*/g, '**');

        // 4. Force newlines before list items or headers if they are clumped with text
        res = res.replace(/([a-z\u00C0-\u00FF0-9.!?])(\s*)([*\-+] |\d+\. |#{1,6} )/g, '$1\n$3');

        // 5. Convert plus-sign lists to standard asterisk lists
        res = res.replace(/^\s*\+\s+/gm, '* ');

        // 4. Existing fixes
        res = res.replace(/([^\n])(\*\*\*\*.*?\*\*\*\*)/g, '$1\n\n$2');
        res = res.replace(/(\*\*\*\*.*?\*\*\*\*)([^\n])/g, '$1\n\n$2');
        res = res.replace(/(:)(\*\*\*)/g, '$1**\n* ');
        res = res.replace(/([^\n])(\*\*\*)(\s)/g, '$1**\n*$3');
        res = res.replace(/([.?!)])\s*(\* )/g, '$1\n$2');
        res = res.replace(/([^\n])(\*\*\*\*)(?=\S)/g, '**\n\n**');
        res = res.replace(/([^\n])(\*\*|__)(?=[a-zA-Z0-9\u00C0-\u00FF])/g, '$1\n$2');
        res = res.replace(/([^\n])(^|\s)([*\-+] )/g, '$1\n$3');
        res = res.replace(/([^\n])(#{1,6} )/g, '$1\n$2');
        res = res.replace(/([^\n])(^|\s)(\*\*\*|---|___)(\s|$)/gm, '$1\n$3');
        res = res.replace(/([^\n])(\s)([IVX]+|[ivx]+)(\.)/g, '$1\n$3$4');

        // Clean up excessive newlines
        res = res.replace(/\n{3,}/g, '\n\n');
        return res;
    };

    /**
     * Process inline elements like images, links, bold, italic.
     *
     * @param {string} text
     * @returns {string}
     */
    const processInline = function(text) {
        if (!text) {
            return '';
        }
        return text
            .replace(/!\[(.*?)\]\((.*?)\)/g, '<img src="$2" alt="$1" style="max-width:100%;height:auto;">')
            .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>')
            .replace(/\*\*\*\*(.*?)\*\*\*\*/g, '<h3>$1</h3>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code style="background:#eee;padding:2px 4px;border-radius:3px;">$1</code>');
    };

    /**
     * Helper to close an open list.
     *
     * @param {string} html Current HTML
     * @param {string|null} listType Current list type
     * @returns {string}
     */
    const closeList = function(html, listType) {
        if (!listType) {
            return html;
        }
        return html + (listType === 'ul' ? '</ul>' : '</ol>');
    };

    /**
     * Process a single line of Markdown.
     *
     * @param {Object} state Current state
     * @param {string} line The line to process
     */
    const processLine = function(state, line) {
        if (line.trim().startsWith('```')) {
            if (state.inCodeBlock) {
                state.inCodeBlock = false;
                state.html += '</code></pre>';
            } else {
                state.html = closeList(state.html, state.listType);
                state.inList = false;
                state.listType = null;
                const style = 'display:block;background:#f4f4f4;padding:10px;border-radius:5px;' +
                            'overflow-x:auto;font-family:monospace;';
                state.html += '<pre><code style="' + style + '">';
                state.inCodeBlock = true;
            }
            return;
        }

        if (state.inCodeBlock) {
            state.html += line + '\n';
            return;
        }

        const trimmed = line.trim();
        if (trimmed === '') {
            state.html = closeList(state.html, state.listType);
            state.inList = false;
            state.listType = null;
            state.html += '<br>';
            return;
        }

        const hdrs = [
            {prefix: '###### ', tag: 'h6'}, {prefix: '##### ', tag: 'h5'},
            {prefix: '#### ', tag: 'h4'}, {prefix: '### ', tag: 'h3'},
            {prefix: '## ', tag: 'h2'}, {prefix: '# ', tag: 'h1'}
        ];

        for (let i = 0; i < hdrs.length; i++) {
            if (trimmed.startsWith(hdrs[i].prefix)) {
                state.html = closeList(state.html, state.listType);
                state.inList = false;
                state.listType = null;
                state.html += '<' + hdrs[i].tag + '>' + processInline(trimmed.substring(hdrs[i].prefix.length)) +
                            '</' + hdrs[i].tag + '>';
                return;
            }
        }

        if (trimmed.match(/^(\*{3,}|-{3,}|_{3,})$/)) {
            state.html = closeList(state.html, state.listType);
            state.inList = false;
            state.listType = null;
            state.html += '<hr>';
            return;
        }

        if (processListItems(state, trimmed)) {
            return;
        }

        if (trimmed.startsWith('> ')) {
            state.html = closeList(state.html, state.listType);
            state.inList = false;
            state.listType = null;
            const bstyle = 'border-left:4px solid #ccc;padding-left:10px;color:#666;';
            state.html += '<blockquote style="' + bstyle + '">' + processInline(trimmed.substring(2)) + '</blockquote>';
        } else {
            state.html = closeList(state.html, state.listType);
            state.inList = false;
            state.listType = null;
            state.html += '<p>' + processInline(trimmed.replace(/^\*(?!\*)\s*/, '')) + '</p>';
        }
    };

    /**
     * Process list items specifically.
     *
     * @param {Object} state
     * @param {string} trimmed
     * @returns {boolean}
     */
    const processListItems = function(state, trimmed) {
        let m = trimmed.match(/^([IVX]+)\.\s+(.*)/);
        if (m) {
            if (!state.inList || state.listType !== 'ol_roman') {
                state.html = closeList(state.html, state.listType);
                state.html += '<ol type="I">';
                state.inList = true;
                state.listType = 'ol_roman';
            }
            state.html += '<li>' + processInline(m[2]) + '</li>';
            return true;
        }

        m = trimmed.match(/^[*\-+]\s+(.*)/);
        if (m) {
            if (!state.inList || state.listType !== 'ul') {
                state.html = closeList(state.html, state.listType);
                state.html += '<ul>';
                state.inList = true;
                state.listType = 'ul';
            }
            state.html += '<li>' + processInline(m[1]) + '</li>';
            return true;
        }

        m = trimmed.match(/^\d+\.\s+(.*)/);
        if (m) {
            if (!state.inList || state.listType !== 'ol') {
                state.html = closeList(state.html, state.listType);
                state.html += '<ol>';
                state.inList = true;
                state.listType = 'ol';
            }
            state.html += '<li>' + processInline(m[1]) + '</li>';
            return true;
        }
        return false;
    };

    /**
     * Render Markdown to HTML.
     *
     * @param {string} md
     * @returns {string}
     */
    const renderMarkdown = function(md) {
        if (!md) {
            return '';
        }
        const text = autofixMarkdown(md).replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const lines = text.split(/\r?\n/);
        const state = {
            html: '',
            inList: false,
            inCodeBlock: false,
            listType: null
        };

        lines.forEach(function(line) {
            processLine(state, line);
        });

        state.html = closeList(state.html, state.listType);
        return state.html;
    };

    /**
     * Clean markdown for plain text view.
     *
     * @param {string} md
     * @returns {string}
     */
    const renderText = function(md) {
        if (!md) {
            return '';
        }
        var txt = autofixMarkdown(md);
        txt = txt.replace(/\*\*\*\*(.*?)\*\*\*\*/g, '$1');
        var prev = '';
        while (txt !== prev) {
            prev = txt;
            txt = txt.replace(/(\*\*|__)(.*?)\1/g, '$2').replace(/(\*|_)(.*?)\1/g, '$2');
        }
        txt = txt.replace(/^#+\s+(.*)$/gm, '\n$1\n' + '-'.repeat(20));
        txt = txt.replace(/^[*\-+]\s+/gm, '- ');
        txt = txt.replace(/```/g, '');
        txt = txt.replace(/^- \s*\*+/gm, '');
        txt = txt.replace(/\n{3,}/g, '\n\n');
        return txt.trim();
    };

    return {
        autofixMarkdown: autofixMarkdown,
        renderMarkdown: renderMarkdown,
        renderText: renderText
    };
});

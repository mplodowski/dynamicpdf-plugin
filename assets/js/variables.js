oc.registerControl('dynamicpdf-variables', class extends oc.ControlBase {
    connect() {
        this.$form = $(this.element.closest('form'));

        /** The code editor announces edits with a jQuery event on its textarea, never a change on the field, so dependsOn would not see them. */
        if (this.element.dataset.sourceField) {
            this.$source = this.$form.find(`[data-field-name="${this.element.dataset.sourceField}"]`);
            this.$source.on('oc.codeEditorChange.dynamicpdfVariables', () => this.$source.trigger('change'));
        }

        this.attachCompleter();
    }

    disconnect() {
        this.$source?.off('.dynamicpdfVariables');
    }

    attachCompleter() {
        const field = this.$form.find(`[data-field-name="${this.element.dataset.targetField}"]`);
        const editor = field.find('.field-codeeditor').data('oc.codeEditor')?.editor;

        if (!editor) {
            /** The editor announces itself ready before it is stored on its element. */
            field.one('oc.codeEditorReady', () => setTimeout(() => this.attachCompleter()));
            return;
        }

        if (editor.dynamicpdfCompleter) {
            return;
        }

        ace.require('ace/ext/language_tools');
        const completer = new TwigCompleter(this.$form);
        editor.dynamicpdfCompleter = completer;
        editor.completers = [completer];
        editor.setOptions({ enableBasicAutocompletion: true, enableLiveAutocompletion: true });
        editor.commands.on('exec', (event) => {
            completer.requested = event.command.name === 'startAutocomplete';
        });
        editor.commands.on('afterExec', (event) => {
            if (event.command.name === 'insertstring' && /^[|.{ ]$/.test(event.args) && TwigCompleter.contextAt(editor) !== null) {
                editor.execCommand('startAutocomplete');
            }
        });
    }
});

class TwigCompleter {
    static FILTERS = {
        abs: null, batch: '(${1:3})', capitalize: null, column: "('${1:name}')", date: "('${1:Y-m-d}')",
        date_modify: "('${1:+1 day}')", default: "('${1:}')", e: null, escape: null, first: null,
        format: '(${1:value})', join: "('${1:, }')", json_encode: null, keys: null, last: null, length: null,
        lower: null, merge: '(${1:[]})', nl2br: null, number_format: "(2, '.', ' ')", raw: null,
        replace: "({'${1:from}': '${2:to}'})", reverse: null, round: '(${1:2})', slice: '(${1:0}, ${2:1})',
        sort: null, split: "('${1:,}')", striptags: null, title: null, trim: null, upper: null, url_encode: null,
    };

    static FUNCTIONS = { cycle: '(${1:values}, ${2:position})', max: '(${1})', min: '(${1})', random: '(${1})', range: '(${1:1}, ${2:10})' };

    static TAGS = {
        if: 'if ${1:condition} %}$0{% endif', elseif: 'elseif ${1:condition}', else: 'else', endif: 'endif',
        for: 'for ${1:item} in ${2:items} %}$0{% endfor', endfor: 'endfor', set: 'set ${1:name} = ${2:value}',
        apply: 'apply ${1:upper} %}$0{% endapply', endapply: 'endapply', verbatim: 'verbatim %}$0{% endverbatim',
    };

    constructor($form) {
        this.$form = $form;
        this.identifierRegexps = [/\w/];
        this.parsedData = {};
    }

    static contextAt(editor) {
        const position = editor.getCursorPosition();
        const before = editor.session.getTextRange({ start: { row: Math.max(0, position.row - 20), column: 0 }, end: position });
        const open = Math.max(before.lastIndexOf('{{'), before.lastIndexOf('{%'));

        if (open === -1 || Math.max(before.lastIndexOf('}}'), before.lastIndexOf('%}')) > open) {
            return null;
        }

        return { tag: before[open + 1] === '%', code: before.slice(open + 2), before };
    }

    panel() {
        return this.$form.find('[data-control="dynamicpdf-variables"]').get(0);
    }

    getCompletions(editor, session, position, prefix, callback) {
        const context = TwigCompleter.contextAt(editor);
        const panel = this.panel();

        if (!panel || (context === null && !this.requested)) {
            callback(null, []);
            return;
        }

        const kinds = this.parsed(panel, 'kinds');
        const variables = this.parsed(panel, 'variables');

        if (context === null) {
            callback(null, this.everything(variables, kinds));
            return;
        }

        const code = context.code.slice(0, context.code.length - prefix.length);

        if (/\|\s*$/.test(code)) {
            callback(null, this.filters(variables, kinds));
            return;
        }

        const path = code.match(/([\w.]+)\.$/);

        if (path) {
            callback(null, this.children(this.resolve(path[1], context.before), variables, kinds));
            return;
        }

        if (context.tag && /^\s*$/.test(code)) {
            callback(null, Object.entries(TwigCompleter.TAGS).map(([name, snippet]) => ({ caption: name, snippet, meta: kinds.tag, score: 100 })));
            return;
        }

        callback(null, [...this.children('', variables, kinds), ...this.loopVariables(context.before, kinds), ...this.functions(variables, kinds)]);
    }

    parsed(panel, name) {
        const json = panel.dataset[name] || '{}';

        if (this.parsedData[name]?.json !== json) {
            this.parsedData[name] = { json, value: JSON.parse(json) };
        }

        return this.parsedData[name].value;
    }

    everything(variables, kinds) {
        const kindOf = (group, label) => (group !== 'helpers' ? kinds.variable : label.startsWith('|') ? kinds.filter : kinds.function);
        const entries = ['layout', 'sample', 'globals', 'helpers'].flatMap((group) => (variables[group] || []).map((entry) => ({
            caption: entry.label,
            value: entry.snippet,
            meta: kindOf(group, entry.label),
            docText: entry.description || '',
            score: 200,
        })));
        const tags = Object.entries(TwigCompleter.TAGS)
            .filter(([name]) => !/^(end|else)/.test(name))
            .map(([name, snippet]) => ({ caption: `{% ${name} %}`, snippet: `{% ${snippet} %}`, meta: kinds.tag, score: 100 }));

        return [...entries, ...tags];
    }

    helpers(variables, prefix) {
        return (variables.helpers || []).filter((entry) => entry.label.startsWith(prefix));
    }

    filters(variables, kinds) {
        const plugin = this.helpers(variables, '|').map((entry) => ({
            caption: entry.label.slice(1),
            snippet: entry.snippet.replace(/^.*?\|/, '').replace(/ }}$/, ''),
            meta: kinds.filter,
            docText: entry.description,
            score: 200,
        }));
        const names = new Set(plugin.map((item) => item.caption));
        const twig = Object.entries(TwigCompleter.FILTERS)
            .filter(([name]) => !names.has(name))
            .map(([name, args]) => ({ caption: name, snippet: name + (args || ''), meta: kinds.filter, score: 100 }));

        return [...plugin, ...twig];
    }

    functions(variables, kinds) {
        const plugin = this.helpers(variables, 'pdf_').map((entry) => {
            const call = entry.snippet.match(/\{\{ (\w+\(.*?\)) }}/);

            return { caption: entry.label, snippet: call ? call[1] : entry.label, meta: kinds.function, docText: entry.description, score: 150 };
        });
        const twig = Object.entries(TwigCompleter.FUNCTIONS).map(([name, args]) => ({ caption: name + '()', snippet: name + args, meta: kinds.function, score: 50 }));

        return [...plugin, ...twig];
    }

    resolve(path, before) {
        const loops = [...before.matchAll(/\{%-?\s*for\s+(?:\w+\s*,\s*)?(\w+)\s+in\s+([\w.]+)/g)].reverse();
        const [head, ...rest] = path.split('.');
        const loop = loops.find((match) => match[1] === head);

        if (!loop) {
            return path;
        }

        return [this.resolve(loop[2], before) + '[]', ...rest].join('.');
    }

    loopVariables(before, kinds) {
        return [...before.matchAll(/\{%-?\s*for\s+(?:(\w+)\s*,\s*)?(\w+)\s+in\s+/g)]
            .flatMap((match) => [match[1], match[2]])
            .filter(Boolean)
            .map((name) => ({ caption: name, value: name, meta: kinds.variable, score: 300 }));
    }

    children(path, variables, kinds) {
        const entries = [...(variables.layout || []), ...(variables.sample || []), ...(variables.globals || [])];
        const prefix = path === '' ? '' : path + '.';
        const seen = new Map();

        entries.forEach((entry) => {
            if (!entry.label.startsWith(prefix)) {
                return;
            }

            const name = entry.label.slice(prefix.length).split('.')[0].replace(/\[]$/, '');

            if (/^\w+$/.test(name) && !seen.has(name)) {
                const leaf = entry.label === prefix + name;
                seen.set(name, { caption: name, value: name, meta: kinds.variable, docText: leaf ? entry.description || '' : '', score: 250 });
            }
        });

        return [...seen.values()];
    }
}

(function($) {

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');
	var htmlize = PhpDebugBar.Widgets.htmlize = function(text) {
		return text.replace(/\n/g, '<br>').replace(/\s/g, "&nbsp;")
	};
	var highlight = PhpDebugBar.Widgets.highlight = function(code, lang) {
		if (typeof(code) === 'string') {
			if (typeof(hljs) === 'undefined') {
				return htmlize(code);
			}
			if (lang) {
				return hljs.highlight(lang, code).value;
			}
			return hljs.highlightAuto(code).value;
		}

		if (typeof(hljs) === 'object') {
			code.each(function(i, e) { hljs.highlightBlock(e); });
		}
		return code;
	};
	var createCodeBlock = PhpDebugBar.Widgets.createCodeBlock = function(code, lang) {
		var pre = $('<pre />');
		$('<code />').text(code).appendTo(pre);
		if (lang) {
			pre.addClass("language-" + lang);
		}
		highlight(pre);
		return pre;
	};

    /**
     * Widget for the displaying templates data
     *
     * Options:
     *  - data
     */
    var TemplatesWidget = PhpDebugBar.Widgets.TemplatesWidget = PhpDebugBar.Widget.extend({

        className: csscls('templates'),

        render: function() {
            this.$status = $('<div />').addClass(csscls('status')).appendTo(this.$el);

            this.$list = new  PhpDebugBar.Widgets.ListWidget({
	            itemRenderer: function(li, tpl) {
					$('<span />').addClass(csscls('name')).text(tpl.name).appendTo(li);
					if (tpl.render_time_str) {
						$('<span title="Render time" />').addClass(csscls('render-time')).text(tpl.render_time_str).appendTo(li);
					}
					if (tpl.memory_str) {
						$('<span title="Memory usage" />').addClass(csscls('memory')).text(tpl.memory_str).appendTo(li);
					}
					if (typeof(tpl.param_count) != 'undefined') {
						$('<span title="Parameter count" />').addClass(csscls('param-count')).text(tpl.param_count).appendTo(li);
					}
					if (typeof(tpl.type) != 'undefined' && tpl.type) {
						$('<span title="Engine" />').addClass(csscls('type')).text(tpl.type).appendTo(li);
					}
					if (tpl.params && !$.isEmptyObject(tpl.params)) {
						var table = $('<table><tr><th colspan="2">Params</th></tr></table>').addClass(csscls('params')).appendTo(li);
						for (var key in tpl.params) {
							if (typeof tpl.params[key] !== 'function') {
								table.append('<tr><td class="' + csscls('name') + '">' + key + '</td><td class="' + csscls('value') +
								'"><pre><code>' + tpl.params[key] + '</code></pre></td></tr>');
							}
						}
						li.css('cursor', 'pointer').click(function() {
							if (table.is(':visible')) {
								table.hide();
							} else {
								table.show();
							}
						});
					}
				}
            });
            this.$list.$el.appendTo(this.$el);


            this.bindAttr('data', function(data) {
                this.$list.set('data', data.templates);
                this.$status.empty();

	            if ( data.vars && data.vars!='null' && !$.isEmptyObject(data.vars)) {
		            var dl = $('<dl/>').addClass(csscls('kvlist')).addClass('varlist').appendTo(this.$el);
		            var dt = $('<dt><span>View Variables:</span></dt>').addClass(csscls('key')).appendTo(dl);
		            var dd = $('<dd/>').addClass(csscls('value')).insertAfter(dt);
		            var v = data.vars;
		            if (v && v.length > 100) {
			            v = v.substr(0, 100) + "...";
		            }
		            var prettyVal = null;
		            dd.text(v).click(function() {
			            if (dd.hasClass(csscls('pretty'))) {
				            dd.text(v).removeClass(csscls('pretty'));
			            } else {
				            prettyVal = prettyVal || createCodeBlock(data.vars);
				            dd.addClass(csscls('pretty')).empty().append(prettyVal);
			            }
		            });

	            }

                var sentence = data.sentence || "templates were rendered";
                $('<span />').text(data.templates.length + " " + sentence).appendTo(this.$status);

                if (data.accumulated_render_time_str) {
                    this.$status.append($('<span title="Accumulated render time" />').addClass(csscls('render-time')).text(data.accumulated_render_time_str));
                }
                if (data.memory_usage_str) {
                    this.$status.append($('<span title="Memory usage" />').addClass(csscls('memory')).text(data.memory_usage_str));
                }
            });
        }

    });

})(PhpDebugBar.$);

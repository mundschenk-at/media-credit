// Inspired by http://core.svn.wordpress.org/trunk/wp-includes/js/tinymce/plugins/wpeditimage/editor_plugin.dev.js
(function() {
	tinymce.create('tinymce.plugins.mediaCredit', {

		init : function(ed, url) {
			var t = this;
			t.url = url;

			ed.onInit.add(function(ed) {
				ed.dom.events.add(ed.getBody(), 'mousedown', function(e) {
					var parent;
		
					if ( e.target.nodeName == 'IMG' && ( parent = ed.dom.getParent(e.target, 'div.mceMediaCredit') ) ) {
						if ( tinymce.isGecko )
							ed.selection.select(parent);
						else if ( tinymce.isWebKit )
							ed.dom.events.prevent(e);
					}
				});
			});
			
			//replace shortcode as its inserted into editor (which uses the exec command)
			ed.onExecCommand.add(function(ed, cmd) {
			    if (cmd ==='mceInsertContent'){
					//tinyMCE.activeEditor.setContent( t._do_shcode(tinyMCE.activeEditor.getContent()) );
			    	tinyMCE.activeEditor.setContent(tinyMCE.activeEditor.getContent());
				}
			});
			
			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = t._do_shcode(o.content);
			});

			ed.onPostProcess.add(function(ed, o) {
				if (o.get)
					o.content = t._get_shcode(o.content);
			});
		},

		_do_shcode : function(co) {
			// changed regexp to mirror the shortcode for wp-caption
			return co.replace(/(?:<p>)?\[media-credit([^\]]+)\]([\s\S]+?)\[\/media-credit\](?:<\/p>)?/g, function(a,b,c){
				
				var id, name, cls, w, credit, div_cls;
				
				b = b.replace(/\\'|\\&#39;|\\&#039;/g, '&#39;').replace(/\\"|\\&quot;/g, '&quot;');
				c = c.replace(/\\&#39;|\\&#039;/g, '&#39;').replace(/\\&quot;/g, '&quot;');
				id = b.match(/id=([0-9]+)/i);
				w = b.match(/width=['"]([0-9]+)/);
				name = b.match(/name=['"]([^'"]+)/i);
				cls = b.match(/align=['"]([^'"]+)/i);
				w = b.match(/width=['"]([0-9]+)/);

				id = ( id && id[1] ) ? id[1] : '';
				name = ( name && name[1] ) ? name[1] : '';
				credit = name ? name : ($mediaCredit.id[id] + $mediaCredit.separator + $mediaCredit.organization);
				cls = ( cls && cls[1] ) ? cls[1] : 'alignnone';
				w = ( w && w[1] ) ? w[1] : '';
				if ( ! w || ! credit ) return c;
				
				div_cls = (cls == 'aligncenter') ? 'mceMediaCredit mceIEcenter' : 'mceMediaCredit';

				return '<span class="'+div_cls+'"><span name="'+name+'" id="'+id+'" class="media-credit-mce '+cls+'" style="width: '+(10+parseInt(w))+
				'px"><span class="media-credit-dt">'+c+'</span><span class="media-credit-dd">'+credit+'</span></span></span>';
			});
		},

		_get_shcode : function(co) {
			return co.replace(/<span class="mceMediaCredit[^"]*">\s*<span([^>]+)>\s*<span[^>]+>([\s\S]+?)<\/span>\s*<span[^>]+>(.+?)<\/span>\s*<\/span>\s*<\/span>\s*/gi, function(a,b,c,name){
				var id, cls, w;
				cls = b.match(/class=['"]([^'"]+)/i);
				w = c.match(/width=['"]([0-9]+)/);

				name = name.replace(/<\S[^<>]*>/gi, '').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
				
				if (typeof($mediaCredit) != "undefined")
					for (var theId in $mediaCredit.id)
						if (name.search($mediaCredit.id[theId]) != -1) {
							id = theId;
							break;
						}

				credit = id ? ('id='+id) : ('name="'+name+'"');
				cls = ( cls && cls[1] ) ? cls[1] : 'alignnone';
				w = ( w && w[1] ) ? w[1] : '';

				if ( ! w || ! name ) return c;
				cls = cls.match(/align[^ '"]+/) || 'alignnone';

				return '[media-credit '+credit+' align="'+cls+'" width="'+w+'"]'+c+'[/media-credit]';
			});
		},

		_checkNameToId : function(name) {
			for (var theId in $mediaCredit.id)
				if (name.search($mediaCredit.id[theId]) != -1)
					return theId;
			return -1;
		},

		getInfo : function() {
			return {
				longname : 'Media Credit',
				author : 'Scott Bressler',
				authorurl : 'http://www.scottbressler.com/blog/plugins/',
				infourl : 'http://www.scottbressler.com/blog/plugins/media-credit/',
				version : "1.5"
			};
		}
	});

	tinymce.PluginManager.add('mediacredit', tinymce.plugins.mediaCredit);
})();

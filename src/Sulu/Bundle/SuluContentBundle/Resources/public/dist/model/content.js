define(["mvc/relationalmodel"],function(a){"use strict";return new a({urlRoot:"/admin/api/nodes",stateSave:function(a,b,c,d,e){return e=_.defaults(e||{},{url:this.urlRoot+(void 0!==this.get("id")?"/"+this.get("id"):"")+"?webspace="+a+"&language="+b+(c?"&state="+c:"")}),this.save.call(this,d,e)},fullSave:function(a,b,c,d,e,f,g,h){return f=f?"1":"0",h=_.defaults(h||{},{url:this.urlRoot+(void 0!==this.get("id")?"/"+this.get("id"):"")+"?webspace="+b+"&language="+c+"&template="+a+(d?"&parent="+d:"")+(e?"&state="+e:"")+(f?"&navigation="+f:"")}),this.save.call(this,g,h)},fullFetch:function(a,b,c,d){return d=_.defaults(d||{},{url:this.urlRoot+(void 0!==this.get("id")?"/"+this.get("id"):"")+"?webspace="+a+"&language="+b+"&breadcrumb="+!!c}),this.fetch.call(this,d)},fullDestroy:function(a,b,c){return c=_.defaults(c||{},{url:this.urlRoot+(void 0!==this.get("id")?"/"+this.get("id"):"")+"?webspace="+a+"&language="+b}),this.destroy.call(this,c)},defaults:function(){return{}}})});
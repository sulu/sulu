define(function(){"use strict";return{type:"excerpt-tab",tabInitialize:function(){this.sandbox.emit("husky.toolbar.header.item.disable","template",!1)},parseData:function(a){return a.ext.excerpt},loadComponentData:function(){var a=$.Deferred();return this.sandbox.emit("sulu.snippets.snippet.get-data",function(b){a.resolve(this.parseData(b))}.bind(this)),a},getTemplate:function(){return"text!/admin/content/template/form/excerpt.html?language="+this.options.locale},save:function(a,b){this.sandbox.emit("sulu.snippets.snippet.get-data",function(c){c.ext.excerpt=a,this.sandbox.emit("sulu.snippets.snippet.save",c,b)}.bind(this))}}});
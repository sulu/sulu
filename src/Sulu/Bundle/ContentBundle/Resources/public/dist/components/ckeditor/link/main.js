define(["underscore","config","text!./form.html"],function(a,b,c){"use strict";var d="#internal-link-form",e=b.get("sulu_content.link_provider.configuration");return{defaults:{options:{provider:"page",link:{},saveCallback:function(a){},removeCallback:function(){}},templates:{form:c,providerDatasource:'<div id="provider-data-source"/>'},translations:{save:"public.save",back:"public.previous",remove:"content.ckeditor.internal-link.remove",altTitle:"content.ckeditor.internal-link.alt-title",href:"content.ckeditor.internal-link.href",target:"content.ckeditor.internal-link.target",targetBlank:"content.ckeditor.internal-link.target-blank",targetSelf:"content.ckeditor.internal-link.target-self",internalLink:"content.ckeditor.internal-link"}},initialize:function(){this.config=e[this.options.provider],this.initializeDialog()},bindDomEvents:function(){this.sandbox.dom.on(this.$el,"click",function(){return this.sandbox.emit("husky.overlay.internal-link.slide-to",1),!1}.bind(this),".internal-link-href, #internal-link-href-button"),this.sandbox.dom.on(this.$el,"click",function(){return this.href=null,$("#internal-link-href-button-clear").hide(),$("#internal-link-href-value").val(""),!1}.bind(this),"#internal-link-href-button-clear")},save:function(){return this.validate()?(this.options.saveCallback(this.getData()),void this.sandbox.stop()):!1},validate:function(){return this.href?this.sandbox.form.validate(d):($(".href-container").addClass("husky-validate-error"),!1)},getData:function(){var b=this.sandbox.form.getData(d,b);return a.defaults(b,{href:this.href,provider:this.options.provider,published:this.hrefPublished,title:this.options.link.title?this.options.link.title:this.hrefTitle})},setData:function(a){return this.sandbox.form.setData(d,a)},setHref:function(a,b,c){this.href=a,this.hrefTitle=b,this.hrefPublished=!!c;var d=$("#internal-link-href-value");d.val(b),$("#internal-link-href-button-clear").show()},initializeDialog:function(){var b=this.translations.internalLink+": "+this.sandbox.translate(this.config.title),c=this.sandbox.dom.createElement('<div class="overlay-container"/>'),e=$(this.templates.providerDatasource()),f=null,g=[{type:"cancel",align:"left"},{type:"ok",text:this.translations.save,align:"right"}];this.sandbox.dom.append(this.$el,c),this.options.link.href&&g.push({text:this.translations.remove,align:"center",classes:"just-text",callback:function(){this.options.removeCallback(),this.sandbox.emit("husky.overlay.internal-link.close")}.bind(this)}),this.config.slideOptions.tabs&&(f=a.map(this.config.slideOptions.tabs,function(b){return a.extend({data:e},b)}),e=null),this.sandbox.start([{name:"overlay@husky",options:{openOnStart:!0,removeOnClose:!0,el:c,container:this.$el,skin:"large",instanceName:"internal-link",slides:[{title:b,data:this.templates.form({translations:this.translations}),buttons:g,okCallback:this.save.bind(this)},{title:b,data:e,tabs:f,cssClass:"data-source-slide",contentSpacing:!1,buttons:[{type:"cancel",text:this.translations.back,align:"left"}],cancelCallback:function(){return this.sandbox.emit("husky.overlay.internal-link.slide-to",0),!1}.bind(this)}]}}]).then(function(){this.sandbox.form.create(d).initialized.then(function(){this.setData(this.options.link).then(this.initializeFormComponents.bind(this)),this.bindDomEvents()}.bind(this))}.bind(this))},initializeFormComponents:function(){this.sandbox.start([{name:"loader@husky",options:{el:this.$find(".loader")}},{name:this.config.component,options:a.extend({},this.config.componentOptions,{el:"#provider-data-source",link:this.options.link,webspace:this.options.webspace,locale:this.options.locale,setHref:function(a,b,c){a&&b&&this.setHref(a,b,c),this.showHrefInput()}.bind(this),selectCallback:function(a,b,c){var d=$("#internal-link-href-value");d.val(b),$("#internal-link-href-button-clear").show(),this.href=a,this.hrefTitle=b,this.hrefPublished=!!c,this.sandbox.emit("husky.overlay.internal-link.slide-to",0),$(".href-container").removeClass("husky-validate-error")}.bind(this)})}])},showHrefInput:function(){this.$find(".loader").hide(),this.$find(".href-container").show()}}});
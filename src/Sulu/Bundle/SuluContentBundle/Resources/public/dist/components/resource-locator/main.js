define([],function(){"use strict";var a={instanceName:null,url:null},b=function(a){return['<div class="resource-locator">','<span class="icon-cogwheel pointer" id="',a.ids.edit,'"></span>',a.url?'   <span id="'+a.ids.url+'" class="url">'+a.url+"</span>":"",'   <span id="'+a.ids.tree+'" class="tree"></span>','   <input type="text" readonly="readonly" id="'+a.ids.input+'" class="form-element"/>','   <span class="icon-chevron-right pointer" id="',a.ids.toggle,'"></span>','   <div id="',a.ids.history,'" class="hidden">',"   </div>","</div>"].join("")},c=function(a){return"#"+this.options.ids[a]},d=function(){this.options.ids={url:"resource-locator-"+this.options.instanceName+"-url",tree:"resource-locator-"+this.options.instanceName+"-tree",input:"resource-locator-"+this.options.instanceName+"-input",edit:"resource-locator-"+this.options.instanceName+"-edit",toggle:"resource-locator-"+this.options.instanceName+"-toggle",history:"resource-locator-"+this.options.instanceName+"-history"},this.sandbox.dom.html(this.$el,b(this.options)),f.call(this),e.call(this)},e=function(){this.sandbox.dom.on(this.$el,"data-changed",f.bind(this)),this.sandbox.dom.on(c.call(this,"edit"),"click",g.bind(this)),this.sandbox.dom.on(c.call(this,"toggle"),"click",h.bind(this)),this.sandbox.dom.on(c.call(this,"input"),"change",i.bind(this)),this.sandbox.dom.on(c.call(this,"input"),"change",function(){this.$el.trigger("change")}.bind(this)),this.sandbox.dom.on(c.call(this,"input"),"focusout",function(){this.$el.trigger("focusout")}.bind(this))},f=function(a){a||(a=this.sandbox.dom.data(this.$el,"value"));var b=a.split("/");this.sandbox.dom.val(c.call(this,"input"),b.pop()),this.sandbox.dom.html(c.call(this,"tree"),b.join("/")+"/")},g=function(){this.sandbox.dom.removeAttr(c.call(this,"input"),"readonly")},h=function(){var a=c.call(this,"toggle");this.historyClosed?(this.sandbox.dom.removeClass(a,"icon-chevron-right"),this.sandbox.dom.removeClass(a,"pointer"),this.sandbox.dom.addClass(a,"icon-chevron-down"),this.sandbox.dom.addClass(a,"pointer"),this.historyClosed=!1,j.call(this)):(this.sandbox.dom.removeClass(a,"icon-chevron-down"),this.sandbox.dom.removeClass(a,"pointer"),this.sandbox.dom.addClass(a,"icon-chevron-right"),this.sandbox.dom.addClass(a,"pointer"),this.sandbox.dom.addClass(c.call(this,"history"),"hidden"),this.historyClosed=!0)},i=function(){var a=this.sandbox.dom.val(c.call(this,"input")),b=this.sandbox.dom.html(c.call(this,"tree"));this.sandbox.dom.data(this.$el,"value",b+a)},j=function(){this.sandbox.util.load(this.options.historyApi).then(function(a){var b=a._embedded,d=["<ul>"];this.sandbox.util.foreach(b,function(a){d.push("<li>"+a.resourceLocator+" ("+this.sandbox.date.format(a.created)+")</li>")}.bind(this)),d.push("</ul>"),this.sandbox.dom.html(c.call(this,"history"),d.join("")),this.sandbox.dom.removeClass(c.call(this,"history"),"hidden")}.bind(this))};return{historyClosed:!0,initialize:function(){this.options=this.sandbox.util.extend({},a,this.options),d.call(this)}}});
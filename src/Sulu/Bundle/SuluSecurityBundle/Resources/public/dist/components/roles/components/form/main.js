define([],function(){"use strict";var a,b,c=["view","add","edit","delete","archive","live","security"],d=["security.permissions.view","security.permissions.add","security.permissions.edit","security.permissions.delete","security.permissions.archive","security.permissions.live","security.permissions.security"],e="#matrix-container",f="#matrix",g="#role-form";return{name:"Sulu Security Role Form",view:!0,templates:["/admin/security/template/role/form"],initialize:function(){this.saved=!0,this.selectedSystem="",a=this.options.data.permissions,this.sandbox.on("husky.select.system.initialize",function(){this.selectedSystem=this.sandbox.dom.attr("#system","data-selection-values"),this.initializeMatrix(),this.initializeValidation(),this.bindDOMEvents(),this.bindCustomEvents(),this.setHeaderBar(!0),this.listenForChange()}.bind(this)),this.render()},bindDOMEvents:function(){this.sandbox.dom.on(this.$el,"change",this.setGod.bind(this),"#god")},bindCustomEvents:function(){this.sandbox.on("husky.matrix.changed",function(a){this.changePermission(a)}.bind(this)),this.sandbox.on("sulu.header.toolbar.save",function(){this.save()}.bind(this)),this.sandbox.on("sulu.header.toolbar.delete",function(){this.sandbox.emit("sulu.role.delete",this.sandbox.dom.val("#id"))}.bind(this)),this.sandbox.on("sulu.role.saved",function(a){this.options.data.id=a,this.setHeaderBar(!0),this.setTitle(),this.setBreadcrumb()},this),this.sandbox.on("sulu.header.back",function(){this.sandbox.emit("sulu.roles.list")},this),this.sandbox.on("husky.select.system.selected.item",function(a){this.selectedSystem=a,this.initializeMatrix()}.bind(this))},initializeValidation:function(){this.sandbox.form.create(g)},initializeMatrix:function(){var g,h,i=this.sandbox.dom.createElement('<div id="matrix" class="loading"/>'),j=function(b){var d,e=!1;g.push(b.split(".").splice(2).join(".")),a.forEach(function(a){a.context===b&&(e=!0,d=h.push([])-1,c.forEach(function(b){h[d].push(a.permissions[b])}))}),e||h.push([])};this.sandbox.stop(f),this.sandbox.dom.append(e,i),this.sandbox.util.ajax({url:"/admin/contexts?system="+this.selectedSystem}).done(function(a){a=JSON.parse(a),b=a;for(var e in a)a.hasOwnProperty(e)&&(g=[],h=[],a[e].forEach(j),this.sandbox.start([{name:"matrix@husky",options:{el:f,captions:{general:e,type:this.sandbox.translate("security.roles.section"),horizontal:this.sandbox.translate("security.roles.permissions"),all:this.sandbox.translate("security.roles.all"),none:this.sandbox.translate("security.roles.none"),vertical:g},values:{vertical:a[e],horizontal:c,titles:this.sandbox.translateArray(d)},data:h}}]),this.sandbox.dom.removeClass(i,"loading"))}.bind(this))},setGod:function(){this.sandbox.emit(this.sandbox.dom.is("#god",":checked")?"husky.matrix.set-all":"husky.matrix.unset-all")},changePermission:function(a){"string"==typeof a.value?this.setPermission(a.section,a.value,a.activated):this.sandbox.dom.each(a.value,function(b,c){this.setPermission(a.section,c,a.activated)}.bind(this)),a.activated||this.sandbox.dom.attr("#god",{checked:!1})},setPermission:function(b,c,d){var e=this.getContextKey(b);a[e]?a[e].permissions[c]=d:(a[e]={},a[e].context=b,a[e].permissions={},a[e].permissions[c]=d)},getContextKey:function(b){var c=a.length;return a.forEach(function(a,d){a.context===b&&(c=d)}),c},save:function(){if(this.sandbox.form.validate(g)){var b={id:this.sandbox.dom.val("#id"),name:this.sandbox.dom.val("#name"),system:this.selectedSystem,permissions:a};this.options.data=this.sandbox.util.extend(!0,{},this.options.data,b),this.sandbox.emit("sulu.roles.save",b)}},render:function(){this.$el.html(this.renderTemplate("/admin/security/template/role/form",{data:this.options.data})),this.sandbox.start(this.$el),this.setTitle(),this.setBreadcrumb()},setTitle:function(){var a="security.roles.title";this.options.data&&this.options.data.name&&(a=this.options.data.name),this.sandbox.emit("sulu.header.set-title",a)},setBreadcrumb:function(){var a=[{title:"navigation.settings"},{title:"security.roles.title",event:"sulu.roles.list"}];a.push(this.options.data&&this.options.data.name?{title:this.options.data.name}:{title:"security.roles.title"}),this.sandbox.emit("sulu.header.set-breadcrumb",a)},setHeaderBar:function(a){if(a!==this.saved){var b=this.options.data&&this.options.data.id?"edit":"add";this.sandbox.emit("sulu.header.toolbar.state.change",b,a,!0)}this.saved=a},listenForChange:function(){this.sandbox.dom.on("#role-form","change",function(){this.setHeaderBar(!1)}.bind(this),"select, input"),this.sandbox.dom.on("#role-form","keyup",function(){this.setHeaderBar(!1)}.bind(this),"input"),this.sandbox.on("husky.matrix.changed",function(){this.setHeaderBar(!1)}.bind(this))}}});
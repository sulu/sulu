define(["suluresource/models/filter","app-config","filtersutil/filter"],function(a,b,c){"use strict";var d="sulu.resource.filters.",e={baseFilterRoute:"resource/filters"},f=d+"new",g=d+"delete",h=d+"edit",i=d+"save",j=d+"list";return{initialize:function(){this.filter=null,this.bindCustomEvents(),"list"===this.options.display?this.renderList():"form"===this.options.display&&this.renderForm()},bindCustomEvents:function(){this.sandbox.on(f,function(){this.newFilter()}.bind(this)),this.sandbox.on(i,function(a){this.save(a)}.bind(this)),this.sandbox.on(g,function(a,b){"array"===this.sandbox.util.typeOf(a)?this.deleteFilters(a,b):this.deleteFilter(a,b)}.bind(this)),this.sandbox.on(h,function(a){this.load(a,b.getUser().locale)}.bind(this)),this.sandbox.on(j,function(a){this.sandbox.emit("sulu.router.navigate",e.baseFilterRoute+"/"+a)}.bind(this)),this.sandbox.on("sulu.header.language-changed",function(a){this.load(this.options.id,a)},this)},save:function(b){this.sandbox.emit("sulu.header.toolbar.item.loading","save-button"),this.filter=a.findOrCreate(b),this.filter.saveLocale(this.options.locale,{success:function(a){var c=a.toJSON();b.id?this.sandbox.emit("sulu.resource.filters.saved",c):this.load(c.id,this.options.locale)}.bind(this),error:function(){this.sandbox.logger.log("error while saving filter")}.bind(this)})},newFilter:function(){this.sandbox.emit("sulu.router.navigate","resource/filters/"+this.options.context+"/"+b.getUser().locale+"/add")},deleteFilter:function(b,c){return b.id||0==b.id?void this.showDeleteConfirmation(id,function(d){if(d){var e=a.findOrCreate({id:b.id});this.adjustSettingsForFilter(b,c),e.destroy({success:function(){this.sandbox.emit("sulu.router.navigate","resource/filters/"+c)}.bind(this)})}}.bind(this)):void this.sandbox.emit("sulu.overlay.show-error","sulu.overlay.delete-no-items")},deleteFilters:function(a,b){return a.length<1?void this.sandbox.emit("sulu.overlay.show-error","sulu.overlay.delete-no-items"):void this.showDeleteConfirmation(a,function(c){if(c){var d="/admin/api/filters?ids="+a.join(","),e=a.slice();this.adjustSettingsForMultipleFilters(a,b),this.sandbox.util.ajax({url:d,type:"DELETE",success:function(){e.forEach(function(a){this.sandbox.emit("husky.datagrid.record.remove",a)}.bind(this))}.bind(this),error:function(a){this.sandbox.logger.error("error when deleting multiple filters!",a)}.bind(this)})}}.bind(this))},adjustSettingsForMultipleFilters:function(b,c){this.sandbox.util.foreach(b,function(b){var d=a.findOrCreate({id:b});d.fetchLocale(SULU.user.locale,{success:function(a){this.adjustSettingsForFilter(a.toJSON(),c)}.bind(this)})}.bind(this))},adjustSettingsForFilter:function(a,b){var d=c.getFilterSettingKey(b),e=c.getFilterSettingValue(a);this.sandbox.sulu.deleteSettingsByKeyAndValue(d,e)},showDeleteConfirmation:function(a,b){0!==a.length&&this.sandbox.emit("sulu.overlay.show-warning","sulu.overlay.be-careful","resource.filter.delete.warning",b.bind(this,!1),b)},load:function(a,b){this.sandbox.emit("sulu.router.navigate","resource/filters/"+this.options.context+"/"+b+"/edit:"+a+"/details")},renderForm:function(){var b=this.sandbox.dom.createElement('<div id="filters-form-container"/>'),c={name:"filters/components/form@suluresource",options:{el:b,locale:this.options.locale,context:this.options.context}};this.html(b),this.options.id?(this.filter=a.findOrCreate({id:this.options.id}),this.filter.fetchLocale(this.options.locale,{success:function(a){c.options.data=a.toJSON(),this.sandbox.start([c])}.bind(this)})):this.sandbox.start([c])},renderList:function(){var a=this.sandbox.dom.createElement('<div id="filters-list-container"/>');this.html(a),this.sandbox.start([{name:"filters/components/list@suluresource",options:{el:a,context:this.options.context}}])}}});
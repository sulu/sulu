define(["filtersutil/header"],function(a){"use strict";var b="#filter-form";return{name:"Sulu Filter Form",view:!0,templates:["/admin/resource/template/filter/form"],header:function(){return{toolbar:{template:[{id:"save-button",icon:"floppy-o",iconSize:"large","class":"highlight",position:1,group:"left",disabled:!0,callback:function(){this.sandbox.emit("sulu.header.toolbar.save")}.bind(this)},{icon:"trash-o",iconSize:"large",group:"left",id:"delete-button",position:30,callback:function(){this.sandbox.emit("sulu.header.toolbar.delete")}.bind(this)}],languageChanger:{preSelected:this.options.locale}}}},initialize:function(){this.saved=!0,this.initializeValidation(),this.bindCustomEvents(),this.setHeaderBar(!0),this.render(),this.listenForChange()},bindCustomEvents:function(){this.sandbox.on("sulu.header.toolbar.save",function(){this.save()}.bind(this)),this.sandbox.on("sulu.header.toolbar.delete",function(){this.sandbox.emit("sulu.resource.filters.delete",this.sandbox.dom.val("#id"),this.options.type)}.bind(this)),this.sandbox.on("sulu.resource.filters.saved",function(a){this.options.data=a,this.setHeaderBar(!0),this.setHeaderInformation()},this),this.sandbox.on("sulu.header.back",function(){this.sandbox.emit("sulu.resource.filters.list",this.options.type)},this)},initializeValidation:function(){this.sandbox.form.create(b)},save:function(){if(this.sandbox.form.validate(b)){var a=this.sandbox.form.getData(b);""===a.id&&delete a.id,a.conjunction=a.conjunction.id,a.context=this.options.type,this.sandbox.emit("sulu.resource.filters.save",a)}},render:function(){this.sandbox.dom.html(this.$el,this.renderTemplate("/admin/resource/template/filter/form")),this.setHeaderInformation(),this.initForm(this.options.data)},initForm:function(a){var c=this.sandbox.form.create(b);c.initialized.then(function(){this.setFormData(a)}.bind(this))},setFormData:function(a){this.sandbox.form.setData(b,a).then(function(){this.sandbox.start(b)}.bind(this)).fail(function(a){this.sandbox.logger.error("An error occured when setting data!",a)}.bind(this))},setHeaderInformation:function(){var b=this.options.data?this.options.data.name:null,c=this.options.data?this.options.data.id:null;a.setTitle(this.sandbox,b),a.setBreadCrumb(this.sandbox,this.options.type,c)},setHeaderBar:function(a){if(a!==this.saved){var b=this.options.data&&this.options.data.id?"edit":"add";this.sandbox.emit("sulu.header.toolbar.state.change",b,a,!0)}this.saved=a},listenForChange:function(){this.sandbox.dom.on("#filter-form","change",function(){this.setHeaderBar(!1)}.bind(this),"select"),this.sandbox.dom.on("#filter-form","keyup",function(){this.setHeaderBar(!1)}.bind(this),"input, textarea"),this.sandbox.on("husky.select.conjunction.selected.item",function(){this.setHeaderBar(!1)}.bind(this))}}});
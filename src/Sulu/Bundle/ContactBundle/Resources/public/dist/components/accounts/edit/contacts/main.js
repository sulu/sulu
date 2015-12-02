define(["mvc/relationalstore","text!sulucontact/components/accounts/edit/contacts/contact-relation.form.html","text!sulucontact/components/accounts/edit/contacts/contact.form.html","config","services/sulucontact/account-manager","services/sulucontact/contact-manager","services/sulucontact/contact-router"],function(a,b,c,d,e,f,g){"use strict";var h={relationFormSelector:"#contact-relation-form",contactSelector:"#contact-field",positionSelector:"#company-contact-position",newContactFormSelector:"#contact-form",contactListSelector:"#people-list"},i=function(a){g.toEdit(a)},j=function(){this.sandbox.on("sulu.contacts.account.contact.removed",function(a,b){this.sandbox.emit("husky.datagrid.record.remove",b)},this),this.sandbox.on("husky.datagrid.radio.selected",function(a){e.setMainContact(this.data.id,a)},this),this.sandbox.on("husky.select.company-position-select.selected.item",function(a){this.companyPosition=a},this),this.sandbox.once("sulu.contacts.set-types",function(a){this.formOfAddress=a.formOfAddress,this.emailTypes=a.emailTypes}.bind(this)),this.sandbox.on("husky.overlay.new-contact.opened",function(){var a=this.sandbox.dom.find(h.newContactFormSelector,this.$el);this.sandbox.start(a),this.sandbox.form.create(h.newContactFormSelector)}.bind(this))},k=function(a){var b,d,e;a=this.sandbox.util.extend(!0,{},{translate:this.sandbox.translate,formOfAddress:this.formOfAddress},a),b=this.sandbox.util.template(c,a),d=this.sandbox.dom.createElement("<div />"),e=this.sandbox.dom.find(h.contactListSelector),this.sandbox.dom.append(e,d),this.sandbox.start([{name:"overlay@husky",options:{el:d,title:this.sandbox.translate("contact.accounts.add-new-contact-to-account"),openOnStart:!0,removeOnClose:!0,instanceName:"new-contact",data:b,skin:"wide",okCallback:m.bind(this)}}])},l=function(a){return a.position=a.position.position,a},m=function(){if(this.sandbox.form.validate(h.newContactFormSelector)){var a=this.sandbox.form.getData(h.newContactFormSelector);return a.account=this.data,a.emails=[{email:a.email,emailType:this.emailTypes[0]}],f.save(a).then(function(a){this.sandbox.emit("husky.datagrid.record.add",l(a))}.bind(this)),!0}return!1},n=function(a){var c,e,f,g;g=d.get("sulucontact.components.autocomplete.default.contact"),g.el=h.contactSelector,a=this.sandbox.util.extend(!0,{},{translate:this.sandbox.translate},a),c=this.sandbox.util.template(b,a),e=this.sandbox.dom.createElement("<div />"),f=this.sandbox.dom.find("#people-list"),this.sandbox.dom.append(f,e),this.sandbox.start([{name:"overlay@husky",options:{el:e,title:this.sandbox.translate("contact.accounts.add-contact"),openOnStart:!0,removeOnClose:!0,instanceName:"contact-relation",data:c,okCallback:q.bind(this)}},{name:"auto-complete@husky",options:g}]),this.sandbox.util.load("/admin/api/contact/positions").then(function(a){this.sandbox.start([{name:"select@husky",options:{el:h.positionSelector,instanceName:"company-position-select",valueName:"position",defaultLabel:this.sandbox.translate("public.please-choose"),returnValue:"id",data:a._embedded.positions,noNewValues:!0,deselectField:"select.no-choice",isNative:!0}}])}.bind(this)).fail(function(a,b){this.sandbox.logger.error(a,b)}.bind(this))},o=function(){this.sandbox.emit("husky.datagrid.items.get-selected",function(a){this.sandbox.sulu.showDeleteDialog(function(b){b&&e.removeAccountContacts(this.data.id,a)}.bind(this))}.bind(this))},p=function(){return this.sandbox.sulu.buttons.get({add:{options:{dropdownItems:{addExisting:{options:{id:"add-account-contact",title:this.sandbox.translate("contact.account.add-account-contact"),callback:n.bind(this)}},addNew:{options:{id:"add-new-contact-to-account",title:this.sandbox.translate("contact.accounts.add-new-contact-to-account"),callback:k.bind(this)}}}}},deleteSelected:{options:{callback:o.bind(this)}}})},q=function(){var a=this.sandbox.dom.find(h.contactSelector+" input",h.relationFormSelector),b=this.sandbox.dom.data(a,"id");b&&(e.addAccountContact(this.data.id,b,this.companyPosition),f.loadOrNew(b).then(function(a){this.sandbox.emit("husky.datagrid.record.add",l(a))}.bind(this)))};return{layout:function(){return{content:{width:"fixed"}}},templates:["/admin/contact/template/contact/list"],initialize:function(){this.data=this.options.data(),this.formOfAddress=null,this.companyPosition=null,j.call(this),this.render()},render:function(){this.sandbox.dom.html(this.$el,this.renderTemplate("/admin/contact/template/contact/list")),this.sandbox.sulu.initListToolbarAndList.call(this,"accountsContactsFields","/admin/api/contacts/fields?accountContacts=true",{el:this.$find("#list-toolbar-container"),instanceName:"contacts",template:p.call(this),hasSearch:!0},{el:this.sandbox.dom.find("#people-list",this.$el),url:"/admin/api/accounts/"+this.data.id+"/contacts?flat=true",searchInstanceName:"contacts",resultKey:"contacts",actionCallback:i.bind(this),searchFields:["fullName"],contentFilters:{isMainContact:"radio"},viewOptions:{table:{selectItem:{type:"checkbox"},removeRow:!1}}})}}});
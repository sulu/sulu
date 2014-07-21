define(["sulucontact/model/account","sulucontact/model/contact","sulucontact/model/accountContact","accountsutil/header","sulucontact/model/activity"],function(a,b,c,d,e){"use strict";var f={dialogEntityFoundTemplate:["<p><%= foundMessage %>:</p>",'<% if (typeof list !== "undefined") { %>',"<ul><%= list %></ul>","<% } %>",'<% if (typeof numChildren !== "undefined" && numChildren > 3 && typeof andMore !== "undefined") { %>',"<p><%= andMore %></p>","<% } %>","<p><%= description %></p>",'<% if (typeof checkboxText !== "undefined") { %>',"<p>",'   <label for="overlay-checkbox">','       <div class="custom-checkbox">','           <input type="checkbox" id="overlay-checkbox" class="form-element" />','           <span class="icon"></span>',"       </div>","       <%= checkboxText %>","</label>","</p>","<% } %>"].join("")};return{initialize:function(){if(this.bindCustomEvents(),this.account=null,this.accountType=null,this.accountTypes=null,"list"===this.options.display)this.renderList();else if("form"===this.options.display)this.renderForm().then(function(){d.setHeader.call(this,this.account,this.options.accountType)}.bind(this));else if("contacts"===this.options.display)this.renderContacts().then(function(){d.setHeader.call(this,this.account,this.options.accountType)}.bind(this));else if("financials"===this.options.display)this.renderFinancials().then(function(){d.setHeader.call(this,this.account,this.options.accountType)}.bind(this));else{if("activities"!==this.options.display)throw"display type wrong";this.renderActivities().then(function(){d.setHeader.call(this,this.account,this.options.accountType)}.bind(this))}},bindCustomEvents:function(){this.sandbox.once("sulu.contacts.activities.set.defaults",this.parseActivityDefaults.bind(this)),this.sandbox.on("sulu.contacts.activities.get.defaults",function(){this.sandbox.emit("sulu.contacts.activities.set.defaults",this.activityDefaults)},this),this.sandbox.on("sulu.contacts.account.delete",this.del.bind(this)),this.sandbox.on("sulu.contacts.accounts.save",this.save.bind(this)),this.sandbox.on("sulu.contacts.accounts.load",this.load.bind(this)),this.sandbox.on("sulu.contacts.contact.load",this.loadContact.bind(this)),this.sandbox.on("sulu.contacts.accounts.new",this.add.bind(this)),this.sandbox.on("sulu.contacts.accounts.delete",this.delAccounts.bind(this)),this.sandbox.on("sulu.contacts.accounts.contact.save",this.addAccountContact.bind(this)),this.sandbox.on("sulu.contacts.accounts.contacts.remove",this.removeAccountContacts.bind(this)),this.sandbox.on("sulu.contacts.accounts.contacts.set-main",this.setMainContact.bind(this)),this.sandbox.on("sulu.contacts.accounts.financials.save",this.saveFinancials.bind(this)),this.sandbox.on("sulu.contacts.accounts.list",function(a,b){var c="";a&&(c="/type:"+a),this.sandbox.emit("sulu.router.navigate","contacts/accounts"+c,b?!1:!0,!0,!0)},this),this.sandbox.on("sulu.contacts.account.types",function(a){this.accountType=a.accountType,this.accountTypes=a.accountTypes}.bind(this)),this.sandbox.on("sulu.contacts.account.get.types",function(a){"function"==typeof a&&a(this.accountType,this.accountTypes)}.bind(this)),this.sandbox.on("sulu.contacts.account.convert",function(a){this.convertAccount(a)}.bind(this)),this.sandbox.on("sulu.contacts.account.activities.delete",this.removeActivities.bind(this)),this.sandbox.on("sulu.contacts.account.activity.save",this.saveActivity.bind(this)),this.sandbox.on("sulu.contacts.account.activity.load",this.loadActivity.bind(this))},parseActivityDefaults:function(a){var b,c;for(b in a)if(a.hasOwnProperty(b))for(c in a[b])a[b].hasOwnProperty(c)&&(a[b][c].translation=this.sandbox.translate(a[b][c].name));this.activityDefaults=a},removeActivities:function(a){this.sandbox.emit("sulu.overlay.show-warning","sulu.overlay.be-careful","sulu.overlay.delete-desc",null,function(){var b;this.sandbox.util.foreach(a,function(a){b=e.findOrCreate({id:a}),b.destroy({success:function(){this.sandbox.emit("sulu.contacts.account.activity.removed",a)}.bind(this),error:function(){this.sandbox.logger.log("error while deleting activity")}.bind(this)})}.bind(this))}.bind(this))},saveActivity:function(a){var b=!0;a.id&&(b=!1),this.activity=e.findOrCreate({id:a.id}),this.activity.set(a),this.activity.save(null,{success:function(a){this.activity=this.flattenActivityObjects(a.toJSON()),this.activity.assignedContact=this.activity.contact.fullName,b?this.sandbox.emit("sulu.contacts.account.activity.added",this.activity):this.sandbox.emit("sulu.contacts.account.activity.updated",this.activity)}.bind(this),error:function(){this.sandbox.logger.log("error while saving activity")}.bind(this)})},flattenActivityObjects:function(a){return a.activityStatus&&(a.activityStatus=this.sandbox.translate(a.activityStatus.name)),a.activityType&&(a.activityType=this.sandbox.translate(a.activityType.name)),a.activityPriority&&(a.activityPriority=this.sandbox.translate(a.activityPriority.name)),a},loadActivity:function(a){a?(this.activity=e.findOrCreate({id:a}),this.activity.fetch({success:function(a){this.activity=a,this.sandbox.emit("sulu.contacts.account.activity.loaded",a.toJSON())}.bind(this),error:function(a,b){this.sandbox.logger.log("error while fetching activity",a,b)}.bind(this)})):this.sandbox.logger.warn("no id given to load activity")},renderActivities:function(){var a,c=this.sandbox.data.deferred();return this.contact=new b,a=this.sandbox.dom.createElement('<div id="activities-list-container"/>'),this.html(a),this.dfdAccount=this.sandbox.data.deferred(),this.dfdSystemContacts=this.sandbox.data.deferred(),this.options.id?(this.getAccount(this.options.id),this.getSystemMembers(),this.sandbox.data.when(this.dfdAccount,this.dfdSystemContacts).then(function(){c.resolve(),this.sandbox.start([{name:"activities@sulucontact",options:{el:a,account:this.account.toJSON(),responsiblePersons:this.responsiblePersons,instanceName:"account"}}])}.bind(this))):(this.sandbox.logger.error("activities are not available for unsaved contacts!"),c.reject()),c.promise()},getAccount:function(b){this.account=new a({id:b}),this.account.fetch({success:function(a){this.account=a,this.dfdAccount.resolve()}.bind(this),error:function(){this.sandbox.logger.log("error while fetching contact")}.bind(this)})},getSystemMembers:function(){this.sandbox.util.load("api/contacts?bySystem=true").then(function(a){this.responsiblePersons=a._embedded.contacts,this.sandbox.util.foreach(this.responsiblePersons,function(a){var c=b.findOrCreate(a);a=c.toJSON()}.bind(this)),this.dfdSystemContacts.resolve()}.bind(this)).fail(function(a,b){this.sandbox.logger.error(a,b)}.bind(this))},setMainContact:function(a){this.account.set({mainContact:b.findOrCreate({id:a})}),this.account.save(null,{patch:!0,success:function(){}.bind(this)})},addAccountContact:function(a,d){var e=c.findOrCreate({id:a,contact:b.findOrCreate({id:a}),account:this.account});e.set({position:d}),e.save(null,{success:function(a){var b=a.toJSON();this.sandbox.emit("sulu.contacts.accounts.contact.saved",b)}.bind(this),error:function(){this.sandbox.logger.log("error while saving contact")}.bind(this)})},removeAccountContacts:function(a){this.sandbox.emit("sulu.overlay.show-warning","sulu.overlay.be-careful","sulu.overlay.delete-desc",null,function(){var d;this.sandbox.util.foreach(a,function(a){d=c.findOrCreate({id:a,contact:b.findOrCreate({id:a}),account:this.account}),d.destroy({success:function(){this.sandbox.emit("sulu.contacts.accounts.contacts.removed",a)}.bind(this),error:function(){this.sandbox.logger.log("error while deleting AccountContact")}.bind(this)})}.bind(this))}.bind(this))},convertAccount:function(a){this.confirmConversionDialog(function(b){b&&(this.account.set({type:a.id}),this.sandbox.emit("sulu.header.toolbar.item.loading","options-button"),this.sandbox.util.ajax("/admin/api/accounts/"+this.account.id+"?action=convertAccountType&type="+a.name,{type:"POST",success:function(a){var b=a;this.sandbox.emit("sulu.header.toolbar.item.enable","options-button"),this.sandbox.emit("sulu.contacts.accounts.saved",b),d.setHeader.call(this,this.account,this.options.accountType),this.sandbox.emit("sulu.account.type.converted")}.bind(this),error:function(){this.sandbox.logger.log("error while saving profile")}.bind(this)}))}.bind(this))},confirmConversionDialog:function(a){if(a&&"function"!=typeof a)throw"callback is not a function";this.sandbox.emit("sulu.overlay.show-warning","sulu.overlay.be-careful","contact.accounts.type.conversion.message",a.bind(this,!1),a.bind(this,!0))},del:function(){this.confirmSingleDeleteDialog(this.options.id,function(a,b){a&&(this.sandbox.emit("sulu.header.toolbar.item.loading","options-button"),this.account.destroy({data:{removeContacts:!!b},processData:!0,success:function(){this.sandbox.emit("sulu.router.navigate","contacts/accounts")}.bind(this)}))}.bind(this))},save:function(a){this.sandbox.emit("sulu.header.toolbar.item.loading","save-button"),this.account.set(a),this.account.save(null,{success:function(b){var c=b.toJSON();a.id?this.sandbox.emit("sulu.contacts.accounts.saved",c):this.sandbox.emit("sulu.router.navigate","contacts/accounts/edit:"+c.id+"/details")}.bind(this),error:function(){this.sandbox.logger.log("error while saving profile")}.bind(this)})},saveFinancials:function(a){this.sandbox.emit("sulu.header.toolbar.item.loading","save-button"),this.account.set(a),this.account.save(null,{patch:!0,success:function(a){var b=a.toJSON();this.sandbox.emit("sulu.contacts.accounts.financials.saved",b)}.bind(this),error:function(){this.sandbox.logger.log("error while saving profile")}.bind(this)})},load:function(a){this.sandbox.emit("sulu.router.navigate","contacts/accounts/edit:"+a+"/details")},loadContact:function(a){this.sandbox.emit("sulu.router.navigate","contacts/contacts/edit:"+a+"/details")},add:function(a){this.sandbox.emit("sulu.router.navigate","contacts/accounts/add/type:"+a)},delAccounts:function(b){return b.length<1?void this.sandbox.emit("sulu.overlay.show-error","sulu.overlay.delete-no-items"):void this.showDeleteConfirmation(b,function(c,d){c&&b.forEach(function(b){var c=new a({id:b});c.destroy({data:{removeContacts:!!d},processData:!0,success:function(){this.sandbox.emit("husky.datagrid.record.remove",b)}.bind(this)})}.bind(this))}.bind(this))},renderList:function(){var a=this.sandbox.dom.createElement('<div id="accounts-list-container"/>');this.html(a),this.sandbox.start([{name:"accounts/components/list@sulucontact",options:{el:a,accountType:this.options.accountType?this.options.accountType:null}}])},renderFinancials:function(){var b=this.sandbox.dom.createElement('<div id="accounts-form-container"/>'),c=this.sandbox.data.deferred();return this.html(b),this.options.id&&(this.account=new a({id:this.options.id}),this.account.fetch({success:function(a){this.sandbox.start([{name:"accounts/components/financials@sulucontact",options:{el:b,data:a.toJSON()}}]),c.resolve()}.bind(this),error:function(){this.sandbox.logger.log("error while fetching contact"),c.reject()}.bind(this)})),c.promise()},renderForm:function(){this.account=new a;var b,c=this.sandbox.dom.createElement('<div id="accounts-form-container"/>'),e=this.sandbox.data.deferred();return this.html(c),this.options.id?(this.account=new a({id:this.options.id}),this.account.fetch({success:function(a){this.sandbox.start([{name:"accounts/components/form@sulucontact",options:{el:c,data:a.toJSON()}}]),e.resolve()}.bind(this),error:function(){this.sandbox.logger.log("error while fetching contact"),e.reject()}.bind(this)})):(b=d.getAccountTypeIdByTypeName.call(this,this.options.accountType),this.account.set({type:b}),this.sandbox.start([{name:"accounts/components/form@sulucontact",options:{el:c,data:this.account.toJSON()}}]),e.resolve()),e.promise()},renderContacts:function(){var b=this.sandbox.dom.createElement('<div id="accounts-contacts-container"/>'),c=this.sandbox.data.deferred();return this.html(b),this.options.id&&(this.account=new a({id:this.options.id}),this.account.fetch({success:function(a){this.sandbox.start([{name:"accounts/components/contacts@sulucontact",options:{el:b,data:a.toJSON()}}]),c.resolve()}.bind(this),error:function(){this.sandbox.logger.log("error while fetching contact"),c.reject()}.bind(this)})),c.promise()},showDeleteConfirmation:function(a,b){0!==a.length&&(1===a.length?this.confirmSingleDeleteDialog(a[0],b):this.confirmMultipleDeleteDialog(a,b))},confirmSingleDeleteDialog:function(a,b){var c="/admin/api/accounts/"+a+"/deleteinfo";this.sandbox.util.ajax({headers:{"Content-Type":"application/json"},context:this,type:"GET",url:c,success:function(c){this.showConfirmSingleDeleteDialog(c,a,b)}.bind(this),error:function(a,b,c){this.sandbox.logger.error("error during get request: "+b,c)}.bind(this)})},showConfirmSingleDeleteDialog:function(a,b,c){if(c&&"function"!=typeof c)throw"callback is not a function";var d="contact.accounts.delete.desc",e="show-warning",g="sulu.overlay.be-careful",h=function(){var a=this.sandbox.dom.find("#overlay-checkbox").length&&this.sandbox.dom.prop("#overlay-checkbox","checked");c.call(this,!0,a)}.bind(this);parseInt(a.numChildren,10)>0?(e="show-error",g="sulu.overlay.error",h=void 0,d=this.sandbox.util.template(f.dialogEntityFoundTemplate,{foundMessage:this.sandbox.translate("contact.accounts.delete.sub-found"),list:this.template.dependencyListAccounts.call(this,a.children),numChildren:parseInt(a.numChildren,10),andMore:this.sandbox.util.template(this.sandbox.translate("public.and-number-more"),{number:"<strong><%= values.numChildren - values.children.length) %></strong>"}),description:this.sandbox.translate("contact.accounts.delete.sub-found-desc")})):parseInt(a.numContacts,10)>0&&(d=this.sandbox.util.template(f.dialogEntityFoundTemplate,{foundMessage:this.sandbox.translate("contact.accounts.delete.contacts-found"),list:this.template.dependencyListContacts.call(this,a.contacts),numChildren:parseInt(a.numContacts,10),andMore:this.sandbox.util.template(this.sandbox.translate("public.and-number-more"),{number:"<strong><%= values.numContacts - values.contacts.length) %></strong>"}),description:this.sandbox.translate("contact.accounts.delete.contacts-question"),checkboxText:this.sandbox.util.template(this.sandbox.translate("contact.accounts.delete.contacts-checkbox"),{number:parseInt(a.numContacts,10)})})),this.sandbox.emit("sulu.overlay."+e,g,d,c.bind(this,!1),h)},confirmMultipleDeleteDialog:function(a,b){var c="/admin/api/accounts/multipledeleteinfo";this.sandbox.util.ajax({headers:{"Content-Type":"application/json"},context:this,type:"GET",url:c,data:{ids:a},success:function(c){this.showConfirmMultipleDeleteDialog(c,a,b)}.bind(this),error:function(a,b,c){this.sandbox.logger.error("error during get request: "+b,c)}.bind(this)})},showConfirmMultipleDeleteDialog:function(a,b,c){if(c&&"function"!=typeof c)throw"callback is not a function";var d="contact.accounts.delete.desc",e="sulu.overlay.be-careful",g="show-warning",h=function(){var a=this.sandbox.dom.find("#delete-contacts").length&&this.sandbox.dom.prop("#delete-contacts","checked");c(!0,a)}.bind(this);parseInt(a.numChildren,10)>0?(g="show-error",e="sulu.overlay.error",h=void 0,d=this.sandbox.util.template(f.dialogEntityFoundTemplate,{foundMessage:this.sandbox.translate("contact.accounts.delete.sub-found"),description:this.sandbox.translate("contact.accounts.delete.sub-found-desc")})):parseInt(a.numContacts,10)>0&&(d=this.sandbox.util.template(f.dialogEntityFoundTemplate,{foundMessage:this.sandbox.translate("contact.accounts.delete.contacts-found"),numChildren:parseInt(a.numContacts,10),description:this.sandbox.translate("contact.accounts.delete.contacts-question"),checkboxText:this.sandbox.util.template(this.sandbox.translate("contact.accounts.delete.contacts-checkbox"),{number:parseInt(a.numContacts,10)})})),this.sandbox.emit("sulu.overlay."+g,e,d,c.bind(this,!1),h)},template:{dependencyListContacts:function(a){var b="<% _.each(contacts, function(contact) { %> <li><%= contact.firstName %> <%= contact.lastName %></li> <% }); %>";return this.sandbox.template.parse(b,{contacts:a})},dependencyListAccounts:function(a){var b="<% _.each(accounts, function(account) { %> <li><%= account.name %></li> <% }); %>";return this.sandbox.template.parse(b,{accounts:a})}}}});
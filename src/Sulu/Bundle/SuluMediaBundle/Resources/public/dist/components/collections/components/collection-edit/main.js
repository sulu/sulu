define(function(){"use strict";var a={activeTab:null,data:{},instanceName:"collection"},b={FILES:"files",SETTINGS:"settings"},c={table:{name:"table"},thumbnailSmall:{name:"thumbnail",thViewOptions:{large:!1}},thumbnailLarge:{name:"thumbnail",thViewOptions:{large:!0}}},d={dropzoneSelector:".dropzone-container",toolbarSelector:".list-toolbar-container",datagridSelector:".datagrid-container",settingsFormId:"collection-settings",listViewStorageKey:"collectionEditListView"};return{view:!0,layout:{content:{width:"max"}},templates:["/admin/media/template/collection/files","/admin/media/template/collection/settings"],initialize:function(){this.options=this.sandbox.util.extend(!0,{},a,this.options),this.saved=!0,this.listView=this.sandbox.sulu.getUserSetting(d.listViewStorageKey)||"thumbnailSmall",this.bindCustomEvents(),this.render()},bindCustomEvents:function(){this.sandbox.on("sulu.list-toolbar.change.table",function(){this.sandbox.emit("husky.datagrid.view.change","table"),this.sandbox.sulu.saveUserSetting(d.listViewStorageKey,"table")}.bind(this)),this.sandbox.on("sulu.list-toolbar.change.thumbnail-small",function(){this.sandbox.emit("husky.datagrid.view.change","thumbnail",{large:!1}),this.sandbox.sulu.saveUserSetting(d.listViewStorageKey,"thumbnailSmall")}.bind(this)),this.sandbox.on("sulu.list-toolbar.change.thumbnail-large",function(){this.sandbox.emit("husky.datagrid.view.change","thumbnail",{large:!0}),this.sandbox.sulu.saveUserSetting(d.listViewStorageKey,"thumbnailLarge")}.bind(this)),this.sandbox.on("sulu.header.back",function(){this.sandbox.emit("sulu.media.collections.list")}.bind(this)),this.sandbox.on("husky.dropzone."+this.options.instanceName+".files-added",function(a){this.sandbox.emit("sulu.labels.success.show","labels.success.media-upload-desc","labels.success"),this.addFilesToDatagrid(a)}.bind(this)),this.sandbox.on("sulu.list-toolbar.add",function(){this.sandbox.emit("husky.dropzone."+this.options.instanceName+".open-data-source")}.bind(this)),this.sandbox.on("husky.datagrid.item.click",this.editMedia.bind(this)),this.sandbox.on("sulu.media-edit.closed",function(){this.sandbox.emit("husky.dropzone."+this.options.instanceName+".unlock-popup")}.bind(this)),this.sandbox.on("sulu.media.collections.save-media",function(a){this.sandbox.emit("husky.datagrid.records.change",a)}.bind(this)),this.sandbox.on("sulu.list-toolbar.delete",this.deleteMedia.bind(this)),this.sandbox.on("sulu.header.toolbar.save",this.saveSettings.bind(this)),this.sandbox.on("sulu.header.toolbar.delete",this.deleteCollection.bind(this)),this.sandbox.on("husky.datagrid.number.selections",this.toggleEditButton.bind(this)),this.sandbox.on("sulu.list-toolbar.edit",this.editMedia.bind(this))},deleteMedia:function(){this.sandbox.emit("husky.datagrid.items.get-selected",function(a){this.sandbox.emit("sulu.media.collections.delete-media",a,function(a){this.sandbox.emit("husky.datagrid.record.remove",a)}.bind(this))}.bind(this))},deleteCollection:function(){this.sandbox.emit("sulu.media.collections.delete-collection",this.options.data.id,function(){this.sandbox.sulu.unlockDeleteSuccessLabel(),this.sandbox.emit("sulu.media.collections.collection-list")}.bind(this))},render:function(){this.setHeaderInfos(),this.options.activeTab===b.FILES?this.renderFiles():this.options.activeTab===b.SETTINGS&&this.renderSettings()},renderFiles:function(){this.sandbox.dom.html(this.$el,this.renderTemplate("/admin/media/template/collection/files")),this.startDropzone(),this.startDatagrid()},editMedia:function(a){this.sandbox.emit("sulu.header.toolbar.item.loading","edit"),this.sandbox.once("sulu.media-edit.edit",function(){this.sandbox.emit("sulu.header.toolbar.item.enable","edit",!1)}.bind(this)),this.sandbox.emit("husky.datagrid.items.get-selected",function(b){this.sandbox.emit("husky.dropzone."+this.options.instanceName+".lock-popup"),a&&-1===b.indexOf(a)&&b.push(a),this.sandbox.emit("sulu.media.collections.edit-media",b)}.bind(this))},renderSettings:function(){this.sandbox.dom.html(this.$el,this.renderTemplate("/admin/media/template/collection/settings")),this.sandbox.start("#"+d.settingsFormId),this.sandbox.form.create("#"+d.settingsFormId),this.sandbox.form.setData("#"+d.settingsFormId,this.options.data).then(function(){this.startSettingsToolbar(),this.bindSettingsDomEvents()}.bind(this))},bindSettingsDomEvents:function(){this.sandbox.dom.on("#"+d.settingsFormId,"change keyup",function(){this.saved===!0&&(this.sandbox.emit("sulu.header.toolbar.state.change","edit",!1),this.saved=!1)}.bind(this))},startSettingsToolbar:function(){this.sandbox.emit("sulu.header.set-toolbar",{template:"default"})},setHeaderInfos:function(){this.sandbox.emit("sulu.header.set-title",this.options.data.title),this.sandbox.emit("sulu.header.set-breadcrumb",[{title:"navigation.media"},{title:"media.collections.title",event:"sulu.media.collections.list"},{title:this.options.data.title}])},startDropzone:function(){this.sandbox.start([{name:"dropzone@husky",options:{el:this.$find(d.dropzoneSelector),url:"/admin/api/media?collection%5Bid%5D="+this.options.data.id,method:"POST",paramName:"fileVersion",instanceName:this.options.instanceName}}])},saveSettings:function(){if(this.sandbox.form.validate("#"+d.settingsFormId)){var a=this.sandbox.form.getData("#"+d.settingsFormId);this.options.data=this.sandbox.util.extend(!0,{},this.options.data,a),this.sandbox.emit("sulu.header.toolbar.item.loading","save-button"),this.sandbox.once("sulu.media.collections.collection-changed",this.savedCallback.bind(this)),this.sandbox.emit("sulu.media.collections.save-collection",this.options.data)}},savedCallback:function(){this.setHeaderInfos(),this.sandbox.emit("sulu.header.toolbar.state.change","edit",!0,!0),this.saved=!0,this.sandbox.emit("sulu.labels.success.show","labels.success.collection-save-desc","labels.success")},startDatagrid:function(){this.sandbox.sulu.initListToolbarAndList.call(this,"mediaFields","/admin/api/media/fields",{el:this.$find(d.toolbarSelector),instanceName:this.options.instanceName,parentTemplate:"defaultEditable",template:"changeable",inHeader:!0},{el:this.$find(d.datagridSelector),url:"/admin/api/media?collection="+this.options.data.id,view:c[this.listView].name,resultKey:"media",pagination:!1,viewOptions:{table:{fullWidth:!1},thumbnail:c[this.listView].thViewOptions||{}}})},toggleEditButton:function(a){var b=a>0;this.sandbox.emit("sulu.list-toolbar."+this.options.instanceName+".edit.state-change",b)},addFilesToDatagrid:function(a){for(var b=-1,c=a.length;++b<c;)a[b].selected=!0;this.sandbox.emit("husky.datagrid.records.add",a,this.scrollToBottom.bind(this))},scrollToBottom:function(){this.sandbox.dom.scrollAnimate(this.sandbox.dom.height(this.sandbox.dom.$document),"body")}}});
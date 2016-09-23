define(["underscore","config","services/sulumedia/collection-manager","services/sulumedia/user-settings-manager","text!./skeleton.html"],function(a,b,c,d,e){"use strict";var f={listContainerSelector:".list-container",overlayBackButtonSelector:".overlay-container .back",dropzoneWrapperContainer:".dropzone-wrapper-container",newFormSelector:"#collection-new"},g={title:"",description:""},h=function(){return{title:this.sandbox.translate("sulu.media.all-collections"),hasSub:!0}};return{templates:["/admin/media/template/collection/new"],defaults:{options:{preselected:[],url:"/admin/api/media",singleSelect:!1,removeable:!0,instanceName:null,types:null,removeOnClose:!1,openOnStart:!1,saveCallback:function(){},removeCallback:function(){}},templates:{skeleton:e,url:["<%= url %>?locale=<%= locale %>","<% if (!!types) {%>&types=<%= types %><% } %>","<% _.each(params, function(value, key) {%>&<%= key %>=<%= value %><% }) %>"].join("")},translations:{save:"sulu-media.selection.overlay.save",remove:"public.remove",uploadInfo:"media-selection.list-toolbar.upload-info",allMedias:"media-selection.overlay.all-medias"}},events:{names:{setItems:{postFix:"set-items",type:"on"},open:{postFix:"open",type:"on"}},namespace:"sulu.media-selection-overlay."},loadedItems:{},initialize:function(){this.data={},this.initializeDialog(),this.bindCollectionViewEvents(),this.bindDomEvents(),this.bindCustomEvents()},bindCollectionViewEvents:function(){this.sandbox.on("sulu.collection-view."+this.options.instanceName+".asset.clicked",function(a,b){this.options.singleSelect&&(this.setItems([b]),this.save(),this.sandbox.emit("husky.overlay."+this.options.instanceName+".close"))},this),this.sandbox.on("sulu.collection-view."+this.options.instanceName+".asset.added",function(a,b){this.addItem(b)}.bind(this)),this.sandbox.on("sulu.collection-view."+this.options.instanceName+".asset.removed",function(a){this.removeItem(a)}.bind(this)),this.sandbox.on("sulu.collection-view."+this.options.instanceName+".folder.clicked",this.renderCollectionView,this),this.sandbox.on("sulu.collection-view."+this.options.instanceName+".folder.breadcrumb-clicked",this.handleBreadcrumbClick,this),this.sandbox.on("sulu.collection-view."+this.options.instanceName+".folder.add-clicked",this.slideToAddForm,this)},bindDomEvents:function(){this.$el.on("click",".back",function(){this.data._embedded&&this.data._embedded.parent?this.renderCollectionView(this.data._embedded.parent.id):this.renderCollectionView()}.bind(this))},bindCustomEvents:function(){this.options.removeOnClose&&this.sandbox.on("husky.overlay."+this.options.instanceName+".closed",function(){this.sandbox.stop()}.bind(this)),this.events.setItems(this.setItems.bind(this)),this.events.open(function(){this.sandbox.emit("husky.overlay."+this.options.instanceName+".open")}.bind(this)),this.sandbox.on("husky.datagrid."+this.options.instanceName+".loaded",function(b){a.each(b._embedded.media,function(a){this.loadedItems[a.id]=a}.bind(this))}.bind(this))},save:function(){this.options.saveCallback(this.getData())},getData:function(){return a.map(this.items,function(a){return this.loadedItems&&this.loadedItems[a.id]?this.loadedItems[a.id]:a}.bind(this))},setItems:function(b){this.items=b;var c=a.map(this.items,function(a){return parseInt(a.id)});this.sandbox.emit("husky.datagrid."+this.options.instanceName+".selected.update",c)},addItem:function(a){return this.has(a.id)?!1:(this.items.push(a),!0)},removeItem:function(b){this.items=a.filter(this.items,function(a){return a.id!==b})},has:function(b){return!!a.filter(this.items,function(a){return a.id===b}).length},getUrl:function(a){return a||(a={}),this.templates.url({url:this.options.url,locale:this.options.locale,types:this.options.types,params:a})},startOverlayComponents:function(){this.startToolbar(),this.renderCollectionView()},initializeDialog:function(){var a=this.sandbox.dom.createElement('<div class="overlay-container"/>');this.sandbox.dom.append(this.$el,a);var b=[{type:"cancel",align:"left"}];this.options.removeable&&b.push({text:this.translations.remove,align:"center",classes:"just-text",callback:function(){this.options.removeCallback(),this.sandbox.emit("husky.overlay."+this.options.instanceName+".close")}.bind(this)}),this.options.singleSelect||b.push({type:"ok",text:this.translations.save,align:"right"}),this.sandbox.once("husky.overlay."+this.options.instanceName+".opened",function(){this.sandbox.form.create(f.newFormSelector)}.bind(this)),this.sandbox.start([{name:"overlay@husky",options:{openOnStart:this.options.openOnStart,removeOnClose:this.options.removeOnClose,el:a,container:this.$el,cssClass:"media-selection-overlay",instanceName:this.options.instanceName,slides:[{displayHeader:!1,data:this.templates.skeleton({title:this.translations.allMedias}),buttons:b,contentSpacing:!1,okCallback:function(){this.save()}.bind(this)},{title:this.sandbox.translate("sulu.media.add-collection"),data:this.renderTemplate("/admin/media/template/collection/new"),okCallback:function(){return this.addCollection(),!1}.bind(this),cancelCallback:function(){return this.slideToCollectionView(),!1}.bind(this)}]}}]).then(function(){return this.setItems(this.options.preselected),this.options.openOnStart?void this.startOverlayComponents():void this.sandbox.once("husky.overlay."+this.options.instanceName+".opened",function(){this.startOverlayComponents()}.bind(this))}.bind(this))},startToolbar:function(){this.sandbox.start([{name:"toolbar@husky",options:{el:this.$el.find(".toolbar"),instanceName:this.options.instanceName,skin:"big",buttons:[{id:"add-folder",icon:"plus-circle",title:"sulu.media.add-collection",callback:this.slideToAddForm.bind(this)}]}}])},startCollectionView:function(b){var c=$('<div class="collection-view"/>');this.$el.find(f.listContainerSelector).append(c),this.sandbox.start([{name:"collection-view@sulumedia",options:{el:c,data:b,locale:this.options.locale,instanceName:this.options.instanceName,assetActions:["fa-check-circle-o"],assetSelectOnClick:!0,assetSingleSelect:!!this.options.singleSelect,assetPreselected:a.map(this.items,function(a){return parseInt(a.id)}),assetShowActionIcon:!!this.options.singleSelect,assetHasEdit:!1,assetHasDelete:!1,assetHasMove:!1,assetHasSelectedCounter:!1,dropzoneOverlayContainer:this.$el.find(f.dropzoneWrapperContainer),parentContainer:f.listContainerSelector}}])},handleBackButtonDisplay:function(){this.data.id?this.sandbox.dom.show(f.overlayBackButtonSelector):this.sandbox.dom.hide(f.overlayBackButtonSelector)},handleBreadcrumbClick:function(a){this.renderCollectionView(a.data.id)},renderCollectionView:function(a){var b;this.sandbox.stop(".collection-view"),a?(b=$('<div class="loader"/>'),this.sandbox.sulu.showLoader.call(this,b),this.$el.find(f.listContainerSelector).append(b),c.load(a,this.options.locale).then(function(a){this.sandbox.stop(".loader"),this.data=a,this.handleBackButtonDisplay(),this.startCollectionView(this.data)}.bind(this))):(this.data=h.call(this),this.startCollectionView(this.data),this.handleBackButtonDisplay())},slideToAddForm:function(){this.sandbox.emit("husky.overlay."+this.options.instanceName+".slide-to",1)},slideToCollectionView:function(){this.sandbox.emit("husky.overlay."+this.options.instanceName+".slide-to",0),this.sandbox.form.setData(f.newFormSelector,g)},addCollection:function(){if(this.sandbox.form.validate(f.newFormSelector)){var a=this.sandbox.form.getData(f.newFormSelector);this.slideToCollectionView(),a.parent=this.data.id,a.locale=d.getMediaLocale(),c.save(a).then(function(a){this.renderCollectionView(a.id)}.bind(this))}}}});
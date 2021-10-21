define(["config","services/sulucontent/smart-content-manager"],function(a,b){"use strict";var c={dataSource:"",subFoldersDisabled:!1,categories:[],tags:[],tagsDisabled:!1,tagsAutoCompleteUrl:"",tagsGetParameter:"search",preSelectedTagOperator:"or",preSelectedCategoryOperator:"or",sortBy:[],preSelectedSortBy:null,preSelectedSortMethod:"asc",presentAs:[],preSelectedPresentAs:null,instanceName:"undefined",url:"",dataSourceParameter:"dataSource",audienceTargetingParameter:"audienceTargeting",includeSubFolders:!1,includeSubFoldersParameter:"includeSubFolders",categoriesParameter:"categories",categoryOperatorParameter:"categoryOperator",paramsParameter:"params",tagsParameter:"tags",tagOperatorParameter:"tagOperator",sortByParameter:"sortBy",sortMethodParameter:"sortMethod",presentAsParameter:"presentAs",limitResultParameter:"limitResult",excludedParameter:"excluded",limitResultDisabled:!1,publishedStateKey:"publishedState",publishedKey:"published",idKey:"id",resultKey:"items",datasourceKey:"datasource",tagsResultKey:"tags",titleKey:"title",descriptionKey:"url",imageKey:"image",pathKey:"path",localeKey:"locale",webspaceKey:"webspaceKey",translations:{},elementDataName:"smart-content",externalConfigs:!1,has:{},title:"Smart-Content",datasource:null,categoryRoot:null,displayOptions:{},navigateEvent:"sulu.router.navigate",deepLink:""},d={tags:!0,categories:!0,sorting:!0,limit:!0,presentAs:!0},e={asc:"Ascending",desc:"Descanding"},f={or:"or",and:"and"},g={containerSelector:".smart-content-container",headerSelector:".header",contentSelector:".content",sourceSelector:".source",buttonIcon:"fa-filter",includeSubSelector:".includeSubCheck",audienceTargetingSelector:".audienceTargeting",tagListClass:"tag-list",tagOperatorClass:"tag-list-operator-dropdown",sortByDropdownClass:"sort-by-dropdown",sortMethodDropdownClass:"sort-method-dropdown",presentAsDropdownClass:"present-as-dropdown",limitToSelector:".limit-to",dataSourceSelector:".data-source",contentListClass:"items-list",loaderClass:"loader",noContentClass:"no-content",isLoadingClass:"is-loading"},h={skeleton:['<div class="white-box smart-content-container form-element">','<div class="header">','    <span class="selected-counter">','        <span class="num">0</span>',"        <span><%= selectedCounterStr %></span>","    </span>",'    <span class="no-content-message"><%= noContentStr %></span>',"</div>",'<div class="content"></div>',"</div>"].join(""),source:['<span class="text">','    <span class="source">','        <span class="desc"><%= desc %></span>','        <span class="val"><%= val %></span>',"    </span>","</span>"].join(""),contentItem:['<li data-id="<%= dataId %>">','    <span class="num"><%= num %></span>','    <span class="icons"><%= icons %></span>',"<% if (!!image) { %>",'    <span class="image"><img src="<%= image %>"/></span>',"<% } %>",'    <span class="value"><%= value %></span>',"</li>"].join(""),contentItemLink:['<li data-id="<%= dataId %>">','    <a href="#" data-id="<%= dataId %>" data-webspace="<%= webspace %>" data-locale="<%= locale %>" class="link">','        <span class="num"><%= num %></span>','        <span class="icons"><%= icons %></span>',"<% if (!!image) { %>",'        <span class="image"><img src="<%= image %>"/></span>',"<% } %>",'        <span class="value" title="<%= value %>"><%= (typeof cropper === "function") ? cropper(value, 42) : value %></span>',"<% if (!!description) { %>",'        <span class="description" title="<%= description %>"><%= (typeof cropper === "function") ? cropper(description, 55) : description %></span>',"<% } %>","    </a>","</li>"].join(""),categoryItem:["<span><%=item.name%></span>"].join(""),overlayContent:{main:['<div class="smart-overlay-content grid">',"</div>"].join(""),dataSource:['<div class="grid-row">','    <div class="grid-col-6">','        <span class="desc"><%= dataSourceLabelStr %></span>','        <div class="btn action fit" id="select-data-source-action"><%= dataSourceButtonStr %></div>','        <div><span class="sublabel"><%= dataSourceLabelStr %>:</span> <span class="sublabel data-source"><%= dataSourceValStr %></span></div>',"    </div>",'    <div class="grid-col-6">','        <div class="check<%= disabled %>">',"            <label>",'                <div class="custom-checkbox">','                    <input type="checkbox" class="includeSubCheck form-element"<%= includeSubCheckedStr %>/>','                    <span class="icon"></span>',"                </div>",'                <span class="description"><%= includeSubStr %></span>',"            </label>","        </div>","    </div>","</div>"].join(""),audienceTargeting:['<div class="grid-col-6 check">',"    <label>",'        <div class="custom-checkbox">','            <input type="checkbox" class="audienceTargeting form-element"<%= value %>/>','            <span class="icon"></span>',"        </div>",'        <span class="description"><%= label %></span>',"    </label>","</div>"].join(""),categories:['<div class="grid-row">','    <div class="grid-col-12">','        <div class="categories-loader"></div>','        <div class="categories" style="display: none;">','            <span class="desc"><%= categoriesLabelStr %></span>','            <div class="btn action fit select-categories-btn" id="select-categories-action"><%= categoriesButtonStr %></div>','            <div class="sublabel"><span><%= categoriesStr %> (<span class="amount-selected-categories"></span>):</span> <span class="selected-categories"></span></div>',"        </div>","    </div>","</div>"].join(""),tagList:['<div class="grid-row">','    <div class="grid-col-6 tags<%= disabled %>">','        <span class="desc"><%= filterByTagsStr %></span>','        <div class="'+g.tagListClass+'"></div>',"    </div>",'    <div class="grid-col-6 <%= disabled %>">','        <span class="desc">&nbsp;</span>','        <div class="'+g.tagOperatorClass+'"></div>',"    </div>","</div>"].join(""),sortBy:['<div class="grid-row">','    <div class="grid-col-6">','        <span class="desc"><%= sortByStr %></span>','        <div class="'+g.sortByDropdownClass+'"></div>',"    </div>",'    <div class="grid-col-6">','        <span class="desc">&nbsp;</span>','        <div class="'+g.sortMethodDropdownClass+'"></div>',"    </div>","</div>"].join(""),presentAs:['<div class="grid-col-6">','    <span class="desc"><%= presentAsStr %></span>','    <div class="'+g.presentAsDropdownClass+'"></div>',"</div>"].join(""),limitResult:['<div class="grid-col-6">','    <span class="desc"><%= limitResultToStr %></span>','    <input type="text" value="<%= limitResult %>" class="limit-to form-element"<%= disabled %>/>',"</div>"].join("")},icons:{draft:'<span class="draft-icon" title="<%= title %>"/>',published:['<span class="published-icon" title="<%= title %>"/>'].join("")}},i="husky.smart-content.",j=function(){return q.call(this,"initialize")},k=function(){return q.call(this,"input-retrieved")},l=function(){return q.call(this,"data-request")},m=function(){return q.call(this,"data-retrieved")},n=function(){return q.call(this,"data-changed")},o=function(){return q.call(this,"external-configs")},p=function(){return q.call(this,"set-configs")},q=function(a){return i+(this.options.instanceName?this.options.instanceName+".":"")+a};return{initialize:function(){this.sandbox.logger.log("initialize",this),this.options=this.sandbox.util.extend(!0,{},c,this.options),this.options.displayOptions=this.sandbox.util.extend(!0,{},d,this.options.displayOptions),this.options.externalConfigs===!0?this.sandbox.on(o.call(this),function(a){this.options=this.sandbox.util.extend(!0,{},this.options,a),this.createComponent()}.bind(this)):this.createComponent()},createComponent:function(){this.setVariables(),this.render(),this.renderStartContent(),this.startLoader(),this.startOverlay(),this.bindEvents(),this.bindDomEvents(),this.setURI(),this.loadContent(),this.setElementData(this.overlayData),this.sandbox.emit(j.call(this))},setVariables:function(){this.$container=null,this.$header=null,this.$content=null,this.$loader=null,this.$button=null,this.items=[],this.URI={data:{},str:this.options.url,hasChanged:!1},this.initOverlayData(),this.translations={elementsSelected:"public.elements-selected",noContentFound:"smart-content.nocontent-found",noContentSelected:"smart-content.nocontent-selected",visible:"smart-content.visible",of:"smart-content.of",configureSmartContent:"smart-content.configure-smart-content",dataSourceLabel:"smart-content.data-source.label",dataSourceButton:"smart-content.data-source.button",categoryLabel:"smart-content.categories.label",categoryButton:"smart-content.categories.button",categories:"smart-content.categories",includeSubFolders:"smart-content.include-sub-folders",audienceTargeting:"smart-content.audience-targeting",filterByTags:"smart-content.filter-by-tags",useAnyTag:"smart-content.use-any-tag",useAllTags:"smart-content.use-all-tags",sortBy:"smart-content.sort-by",noSorting:"smart-content.no-sorting",ascending:"smart-content.ascending",descending:"smart-content.descending",presentAs:"smart-content.present-as",limitResultTo:"smart-content.limit-result-to",noCategory:"smart-content.no-category",choosePresentAs:"smart-content.choose-present-as",from:"smart-content.from",subFoldersInclusive:"smart-content.sub-folders-inclusive",viewAll:"smart-content.view-all",viewLess:"smart-content.view-less",chooseDataSource:"smart-content.choose-data-source",chooseDataSourceOk:"smart-content.choose-data-source.ok",chooseDataSourceReset:"smart-content.choose-data-source.reset",chooseDataSourceCancel:"smart-content.choose-data-source.cancel",chooseCategories:"smart-content.choose-categories",chooseCategoriesOk:"smart-content.choose-categories.ok",chooseCategoriesCancel:"smart-content.choose-categories.cancel",clearButton:"smart-content.clear",applyButton:"smart-content.apply",unpublished:"public.unpublished",publishedWithDraft:"public.published-with-draft"},this.translations=this.sandbox.util.extend(!0,{},this.translations,this.options.translations)},initOverlayData:function(){this.$overlayContent=null,this.overlayData={dataSource:this.options.dataSource,audienceTargeting:this.options.audienceTargeting,includeSubFolders:this.options.includeSubFolders,categories:this.options.categories||[],categoryOperator:this.options.preSelectedCategoryOperator||[],tags:this.options.tags||[],tagOperator:this.options.preSelectedTagOperator,sortBy:this.options.preSelectedSortBy,sortMethod:this.options.preSelectedSortMethod,presentAs:this.options.preSelectedPresentAs,limitResult:this.options.limitResult},this.overlayDisabled={categories:0===this.options.categories.length,sortBy:0===this.options.sortBy.length,presentAs:0===this.options.presentAs.length,subFolders:this.options.subFoldersDisabled,tags:this.options.tagsDisabled,limitResult:this.options.limitResultDisabled}},render:function(){this.renderContainer(),this.renderHeader()},renderContainer:function(){this.sandbox.dom.html(this.$el,this.sandbox.util.template(h.skeleton,{noContentStr:this.sandbox.translate(this.translations.noContentSelected),selectedCounterStr:this.sandbox.translate(this.translations.elementsSelected)})),this.$container=this.sandbox.dom.find(g.containerSelector,this.$el)},renderHeader:function(){this.$header=this.sandbox.dom.find(g.headerSelector,this.$el),this.$header.length?this.renderButton():this.sandbox.logger.log("Error: no Header-container found!")},insertSource:function(){var a,b=this.sandbox.dom.find(g.dataSourceSelector,this.$overlayContent),c=this.sandbox.translate(this.overlayData.fullQualifiedTitle);this.sandbox.dom.text(b,this.sandbox.util.cropMiddle(c,30,"...")),this.options.has.datasource&&"undefined"!=typeof this.overlayData.dataSource&&""!==this.overlayData.dataSource&&""!==this.overlayData.title&&null!==this.overlayData.title&&(a=this.sandbox.translate(this.translations.from),a+=this.overlayData.includeSubFolders!==!1?" ("+this.sandbox.translate(this.translations.subFoldersInclusive)+"):":": ",this.sandbox.dom.append(this.$header,this.sandbox.util.template(h.source)({desc:a,val:this.sandbox.translate(this.overlayData.title)})))},removeSource:function(){this.sandbox.dom.remove(this.sandbox.dom.find(g.sourceSelector,this.$header))},renderButton:function(){this.$button=this.sandbox.dom.createElement('<span class="icon left action"/>'),this.sandbox.dom.prependClass(this.$button,g.buttonIcon),this.sandbox.dom.prepend(this.$header,this.$button)},initContentContainer:function(){null===this.$content&&(this.$content=this.sandbox.dom.find(g.contentSelector,this.$el))},renderContent:function(){if(this.initContentContainer(),0!==this.items.length){this.$container.removeClass(g.noContentClass);var a=this.sandbox.dom.createElement('<ul class="'+g.contentListClass+'"/>');this.sandbox.util.foreach(this.items,function(b,c){var d=h.contentItem;""!==this.options.deepLink&&(d=h.contentItemLink),this.sandbox.dom.append(a,_.template(d,{dataId:b[this.options.idKey],value:b[this.options.titleKey],description:b[this.options.descriptionKey]||null,image:b[this.options.imageKey]||null,webspace:this.options.webspace,locale:this.options.locale,num:c+1,icons:this.getItemIcons(b),cropper:this.sandbox.util.cropMiddle}))}.bind(this)),this.sandbox.dom.append(this.$content,a)}else this.$content.empty(),this.$header.find(".no-content-message").html(this.sandbox.translate(this.translations.noContentFound)),this.$container.addClass(g.noContentClass)},getItemIcons:function(a){if(void 0===a[this.options.publishedStateKey])return"";var b="",c=this.sandbox.translate(this.translations.unpublished);return!a[this.options.publishedStateKey]&&a[this.options.publishedKey]&&(c=this.sandbox.translate(this.translations.publishedWithDraft),b+=_.template(h.icons.published,{title:c})),a[this.options.publishedStateKey]||(b+=_.template(h.icons.draft,{title:c})),b},renderStartContent:function(){this.initContentContainer(),this.$container.addClass(g.noContentClass)},bindEvents:function(){this.sandbox.on(m.call(this),function(){this.renderContent(),this.removeSource(),this.insertSource()}.bind(this)),this.sandbox.on(k.call(this),function(){this.setURI(),this.loadContent()}.bind(this)),this.sandbox.on("husky.overlay.smart-content."+this.options.instanceName+".initialized",function(){this.startOverlayComponents()}.bind(this)),this.sandbox.on(p.call(this),function(a){this.options=this.sandbox.util.extend(!1,{},this.options,a),this.sandbox.emit("husky.overlay.smart-content."+this.options.instanceName+".remove"),this.initOverlayData(),this.startOverlay(),this.setURI(),this.loadContent()}.bind(this))},bindDomEvents:function(){this.sandbox.dom.on(this.$el,"click",function(a){var b=this.sandbox.dom.data(a.currentTarget,"id"),c=this.sandbox.dom.data(a.currentTarget,"webspace"),d=this.sandbox.dom.data(a.currentTarget,"locale"),e=this.options.deepLink;return e=e.replace("{webspace}",c).replace("{locale}",d).replace("{id}",b),this.sandbox.emit(this.options.navigateEvent,e),!1}.bind(this),"a.link")},startLoader:function(){this.$loader=this.sandbox.dom.createElement('<div class="'+g.loaderClass+'"/>'),this.sandbox.dom.append(this.$header,this.$loader),this.sandbox.start([{name:"loader@husky",options:{el:this.$loader,size:"20px",color:"#999999"}}])},startOverlay:function(){var a=!!this.options.has.datasource,b=!!this.options.has.categories&&!!this.options.displayOptions.categories;this.initOverlayContent(),this.mainSlide=0,this.datasourceSlide=a?1:null,this.categoriesSlide=a?2:b?1:null;var c=this.sandbox.dom.createElement("<div/>"),d=[{title:this.sandbox.translate(this.translations.configureSmartContent).replace("{title}",this.options.title),data:this.$overlayContent,buttons:[{type:"cancel",text:"public.cancel",classes:"gray black-text",inactive:!1,align:"left"},{text:this.sandbox.translate(this.translations.clearButton),inactive:!1,align:"center",classes:"just-text",callback:function(){this.clear()}.bind(this)},{type:"ok",text:this.sandbox.translate(this.translations.applyButton),inactive:!1,align:"right"}],okCallback:function(){this.getOverlayData()}.bind(this)}];a&&d.push({title:this.sandbox.translate(this.translations.chooseDataSource),data:'<div id="data-source-'+this.options.instanceName+'" class="data-source-content"/>',cssClass:"data-source-slide",okInactive:!0,contentSpacing:!1,buttons:[{type:"cancel",inactive:!1,text:this.translations.chooseDataSourceCancel,align:"left"},{inactive:!1,classes:"just-text",text:this.translations.chooseDataSourceReset,align:"center",callback:function(){var a=this.sandbox.dom.find(g.dataSourceSelector,this.$overlayContent);return this.overlayData.dataSource=null,a.text(""),a.data("id",null),this.sandbox.emit("smart-content.datasource."+this.options.instanceName+".set-selected",this.overlayData.dataSource),this.sandbox.emit("husky.overlay.smart-content."+this.options.instanceName+".slide-to",this.mainSlide),!1}.bind(this)}],cancelCallback:function(){return this.sandbox.emit("smart-content.datasource."+this.options.instanceName+".set-selected",this.overlayData.dataSource),this.sandbox.emit("husky.overlay.smart-content."+this.options.instanceName+".slide-to",this.mainSlide),!1}.bind(this)}),b&&d.push({title:this.sandbox.translate(this.translations.chooseCategories),data:'<div id="categories-'+this.options.instanceName+'" class="categories-content"/>',cssClass:"categories-slide",buttons:[{type:"cancel",inactive:!1,text:this.translations.chooseCategoriesCancel,align:"left"},{type:"ok",inactive:!1,text:this.translations.chooseCategoriesOk,align:"right"}],cancelCallback:function(){return this.sandbox.emit("husky.overlay.smart-content."+this.options.instanceName+".slide-to",this.mainSlide),!1}.bind(this),okCallback:function(){return this.sandbox.emit("smart-content.categories."+this.options.instanceName+".get-data",this.selectCategories.bind(this)),this.sandbox.emit("husky.overlay.smart-content."+this.options.instanceName+".slide-to",this.mainSlide),!1}.bind(this)}),this.sandbox.dom.append(this.$el,c),this.sandbox.start([{name:"overlay@husky",options:{triggerEl:this.$button,el:c,removeOnClose:!1,container:this.$el,instanceName:"smart-content."+this.options.instanceName,skin:"medium",slides:d}}]),this.bindDatasourceEvents()},bindDatasourceEvents:function(){this.options.has.datasource&&this.sandbox.on("husky.overlay.smart-content."+this.options.instanceName+".initialized",this.initDatasource.bind(this)),this.options.has.categories&&this.options.displayOptions.categories&&this.sandbox.on("husky.overlay.smart-content."+this.options.instanceName+".initialized",this.initCategories.bind(this)),this.sandbox.once("husky.overlay.smart-content."+this.options.instanceName+".opened",function(){var a=this.sandbox.dom.outerHeight(".smart-content-overlay .slide-0 .overlay-content")+24;this.sandbox.dom.css(".smart-content-overlay .slide-1 .overlay-content","height",a+"px")}.bind(this)),this.sandbox.dom.on(this.$el,"click",function(){this.sandbox.emit("husky.overlay.smart-content."+this.options.instanceName+".slide-to",this.datasourceSlide)}.bind(this),"#select-data-source-action"),this.sandbox.dom.on(this.$el,"click",function(){this.sandbox.emit("husky.overlay.smart-content."+this.options.instanceName+".slide-to",this.categoriesSlide)}.bind(this),"#select-categories-action")},initDatasource:function(){var a={el:"#data-source-"+this.options.instanceName,selected:this.overlayData.dataSource,webspace:this.options.webspace,locale:this.options.locale,instanceName:this.options.instanceName,selectCallback:function(a,b){b=this.sandbox.translate(b);var c=this.sandbox.dom.find(g.dataSourceSelector,this.$overlayContent);this.overlayData.dataSource=a,this.sandbox.dom.text(c,this.sandbox.util.cropMiddle(b,30,"...")),this.sandbox.dom.data(c,"id",a),this.sandbox.emit("smart-content.datasource."+this.options.instanceName+".set-selected",this.overlayData.dataSource),this.sandbox.emit("husky.overlay.smart-content."+this.options.instanceName+".slide-to",this.mainSlide)}.bind(this)},b=this.sandbox.util.extend(!0,{},a,this.options.datasource.options);this.sandbox.start([{name:this.options.datasource.name,options:b}])},initCategories:function(){this.sandbox.once("smart-content.categories."+this.options.instanceName+".initialized",this.handleCategoriesInitialized.bind(this)),this.sandbox.start([{name:"loader@husky",options:{el:this.sandbox.dom.find(".categories-loader",this.$overlayContent)}},{name:"smart-content/categories@sulucontent",options:{el:"#categories-"+this.options.instanceName,instanceName:this.options.instanceName,preselectedOperator:this.overlayData.categoryOperator,preselectedCategories:this.overlayData.categories,root:this.options.categoryRoot,locale:this.options.locale}}])},handleCategoriesInitialized:function(a){this.selectCategories(a),this.sandbox.stop(this.sandbox.dom.find(".categories-loader",this.$overlayContent)),this.sandbox.dom.find(".categories",this.$overlayContent).show()},selectCategories:function(a){this.overlayData.categories=a.ids,this.overlayData.categoryOperator=a.operator,this.renderCategories(a.items)},renderCategories:function(a){var b,c=[],d=a.length>3?3:a.length;for(b=0;d>b;b++)c.push(this.sandbox.util.template(h.categoryItem,{item:a[b]})),d-1>b&&c.push(", ");d<a.length&&c.push(" ..."),this.sandbox.dom.html(this.sandbox.dom.find(".selected-categories",this.$overlayContent),c.join("")),this.sandbox.dom.html(this.sandbox.dom.find(".amount-selected-categories",this.$overlayContent),a.length)},initOverlayContent:function(){this.$overlayContent=this.sandbox.dom.createElement(_.template(h.overlayContent.main)()),this.appendOverlayContent(this.$overlayContent,this.options)},appendOverlayContent:function(b,c){var d;this.options.has.datasource&&(b.append(_.template(h.overlayContent.dataSource)({dataSourceLabelStr:this.sandbox.translate(this.translations.dataSourceLabel),dataSourceButtonStr:this.sandbox.translate(this.translations.dataSourceButton),dataSourceValStr:"",includeSubStr:this.sandbox.translate(this.translations.includeSubFolders),includeSubCheckedStr:c.includeSubFolders?" checked":"",disabled:this.overlayDisabled.subFolders?" disabled":""})),b.append('<div class="clear"></div>')),this.options.has.categories&&this.options.displayOptions.categories&&b.append(_.template(h.overlayContent.categories)({categoriesLabelStr:this.sandbox.translate(this.translations.categoryLabel),categoriesStr:this.sandbox.translate(this.translations.categories),categoriesButtonStr:this.sandbox.translate(this.translations.categoryButton)})),this.options.has.tags&&this.options.displayOptions.tags&&(b.append(_.template(h.overlayContent.tagList)({filterByTagsStr:this.sandbox.translate(this.translations.filterByTags),disabled:this.overlayDisabled.tags?" disabled":""})),b.append('<div class="clear"></div>')),this.options.has.sorting&&this.options.displayOptions.sorting&&(b.append(_.template(h.overlayContent.sortBy)({sortByStr:this.sandbox.translate(this.translations.sortBy)})),b.append('<div class="clear"></div>')),d=$('<div class="grid-row"/>'),this.options.has.presentAs&&this.options.displayOptions.presentAs&&this.options.presentAs&&this.options.presentAs.length>0&&d.append(_.template(h.overlayContent.presentAs)({presentAsStr:this.sandbox.translate(this.translations.presentAs)})),this.options.has.limit&&this.options.displayOptions.limit&&d.append(_.template(h.overlayContent.limitResult)({limitResultToStr:this.sandbox.translate(this.translations.limitResultTo),limitResult:c.limitResult>0?c.limitResult:"",disabled:this.overlayDisabled.limitResult?" disabled":""})),d.find("> *").length>=2&&(b.append(d),d=$('<div class="grid-row"/>')),a.has("sulu_audience_targeting")&&this.options.has.audienceTargeting&&d.append(_.template(h.overlayContent.audienceTargeting)({label:this.sandbox.translate(this.translations.audienceTargeting),value:c.audienceTargeting?" checked":""})),d.find("> *").length>=0&&b.append(d),b.append('<div class="clear"></div>')},startOverlayComponents:function(){this.sandbox.start([{name:"auto-complete-list@husky",options:{el:this.sandbox.dom.find("."+g.tagListClass,this.$overlayContent),instanceName:this.options.instanceName+g.tagListClass,items:this.overlayData.tags,remoteUrl:this.options.tagsAutoCompleteUrl,autocomplete:""!==this.options.tagsAutoCompleteUrl,getParameter:this.options.tagsGetParameter,noNewTags:!0,itemsKey:this.options.tagsResultKey,disabled:this.overlayDisabled.tags}},{name:"select@husky",options:{el:this.sandbox.dom.find("."+g.tagOperatorClass,this.$overlayContent),instanceName:this.options.instanceName+g.tagOperatorClass,value:"name",data:[{id:f.or,name:this.sandbox.translate(this.translations.useAnyTag)},{id:f.and,name:this.sandbox.translate(this.translations.useAllTags)}],preSelectedElements:this.overlayData.tagOperator?[f[this.overlayData.tagOperator]]:[],disabled:this.overlayDisabled.tags}},{name:"select@husky",options:{el:this.sandbox.dom.find("."+g.sortByDropdownClass,this.$overlayContent),instanceName:this.options.instanceName+g.sortByDropdownClass,value:"name",data:this.options.sortBy,preSelectedElements:this.overlayData.sortBy?[this.overlayData.sortBy]:[],disabled:this.overlayDisabled.sortBy,defaultLabel:this.sandbox.translate("smart-content.no-sorting"),deselectField:this.sandbox.translate("smart-content.no-sorting")}},{name:"select@husky",options:{el:this.sandbox.dom.find("."+g.sortMethodDropdownClass,this.$overlayContent),instanceName:this.options.instanceName+g.sortMethodDropdownClass,value:"name",data:[{id:e.asc,name:this.sandbox.translate(this.translations.ascending)},{id:e.desc,name:this.sandbox.translate(this.translations.descending)}],preSelectedElements:this.overlayData.sortMethod?[e[this.overlayData.sortMethod]]:null,disabled:this.overlayDisabled.sortBy}},{name:"select@husky",options:{el:this.sandbox.dom.find("."+g.presentAsDropdownClass,this.$overlayContent),instanceName:this.options.instanceName+g.presentAsDropdownClass,defaultLabel:this.sandbox.translate(this.translations.choosePresentAs),value:"name",data:this.options.presentAs,preSelectedElements:this.overlayData.presentAs?[this.overlayData.presentAs]:[],disabled:this.overlayDisabled.presentAs}}])},setURI:function(){var a={};if(a[this.options.dataSourceParameter]=this.overlayData.dataSource,a[this.options.audienceTargetingParameter]=this.overlayData.audienceTargeting,a[this.options.includeSubFoldersParameter]=this.overlayData.includeSubFolders,a[this.options.tagsParameter]=this.overlayData.tags,a[this.options.tagOperatorParameter]=this.overlayData.tagOperator,a[this.options.sortByParameter]=this.overlayData.sortBy,a[this.options.sortMethodParameter]=this.overlayData.sortMethod,a[this.options.presentAsParameter]=this.overlayData.presentAs,a[this.options.limitResultParameter]=""!==this.overlayData.limitResult?this.overlayData.limitResult:null,a[this.options.categoriesParameter]=this.overlayData.categories||[],a[this.options.categoryOperatorParameter]=this.overlayData.categoryOperator||this.options.preSelectedCategoryOperator,a[this.options.paramsParameter]=JSON.stringify(this.options.property.params),a[this.options.excludedParameter]=[this.options.excluded,this.overlayData.dataSource],JSON.stringify(a)!==JSON.stringify(this.URI.data)){var b=this.sandbox.dom.data(this.$el,this.options.elementDataName);this.sandbox.emit(n.call(this),b,this.$el),this.URI.data=this.sandbox.util.extend(!0,{},a),this.URI.hasChanged=!0}else this.URI.hasChanged=!1},loadContent:function(){if(this.URI.hasChanged===!0){var a=this.sandbox.util.deepCopy(this.URI.data);delete a[this.options.audienceTargetingParameter],this.sandbox.emit(l.call(this)),this.$find("."+g.contentListClass).empty(),this.$container.addClass(g.isLoadingClass),b.load(this.URI.str,this.URI.data,this.options.excludeDuplicates?this.options.alias:null).done(function(a){this.$container.removeClass(g.isLoadingClass),this.options.has.datasource&&a[this.options.datasourceKey]?(this.overlayData.title=a[this.options.datasourceKey][this.options.titleKey],this.overlayData.fullQualifiedTitle=a[this.options.datasourceKey][this.options.pathKey]):(this.overlayData.title=null,this.overlayData.fullQualifiedTitle=""),this.items=a._embedded[this.options.resultKey],this.updateSelectedCounter(this.items.length),this.sandbox.emit(m.call(this))}.bind(this)).fail(function(a){this.sandbox.logger.log(a)}.bind(this))}},updateSelectedCounter:function(a){this.$header.find(".selected-counter .num").html(a)},getOverlayData:function(){var a,b,c,d,h,i;a=b=c=d=h=this.sandbox.data.deferred(),this.overlayData.audienceTargeting=this.sandbox.dom.prop(this.sandbox.dom.find(g.audienceTargetingSelector,this.$overlayContent),"checked"),this.overlayData.includeSubFolders=this.sandbox.dom.prop(this.sandbox.dom.find(g.includeSubSelector,this.$overlayContent),"checked"),this.overlayData.limitResult=this.sandbox.dom.val(this.sandbox.dom.find(g.limitToSelector,this.$overlayContent)),i=this.sandbox.dom.data(this.sandbox.dom.find(g.dataSourceSelector,this.$overlayContent),"id"),void 0!==i&&(this.overlayData.dataSource=i),this.sandbox.emit("husky.auto-complete-list."+this.options.instanceName+g.tagListClass+".get-tags",function(b){this.overlayData.tags=b,a.resolve()}.bind(this)),this.sandbox.emit("husky.select."+this.options.instanceName+g.tagOperatorClass+".get-checked",function(a){this.overlayData.tagOperator=a[0]===f.and?f.and:f.or,b.resolve()}.bind(this)),this.sandbox.emit("husky.select."+this.options.instanceName+g.sortByDropdownClass+".get-checked",function(a){this.overlayData.sortBy=a,c.resolve()}.bind(this)),this.sandbox.emit("husky.select."+this.options.instanceName+g.sortMethodDropdownClass+".get-checked",function(a){this.overlayData.sortMethod=a[0]===e.asc?"asc":"desc",d.resolve()}.bind(this)),this.sandbox.emit("husky.select."+this.options.instanceName+g.presentAsDropdownClass+".get-checked",function(a){1===a.length?this.overlayData.presentAs=a[0]:this.overlayData.presentAs=null,h.resolve()}.bind(this)),this.sandbox.dom.when(a.promise(),b.promise(),c.promise(),d.promise(),h.promise()).then(function(){this.setElementData(this.overlayData),this.sandbox.emit(k.call(this))}.bind(this))},setElementData:function(a){var b=this.sandbox.util.extend(!0,{},a);this.sandbox.dom.data(this.$el,this.options.elementDataName,b)},clear:function(){this.overlayData={dataSource:"",includeSubFolders:!1,audienceTargeting:!1,limitResult:null,presentAs:null,sortBy:[],sortMethod:"asc",categoryOperator:"or",categories:[],tags:[],tagOperator:"or"},this.$overlayContent.html(""),this.appendOverlayContent(this.$overlayContent,this.overlayData),this.startOverlayComponents(),this.handleCategoriesInitialized({ids:[],operator:"or",items:[]}),this.sandbox.emit("smart-content.datasource."+this.options.instanceName+".set-selected",this.overlayData.dataSource)}}});
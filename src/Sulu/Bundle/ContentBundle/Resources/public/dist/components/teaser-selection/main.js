define(["underscore","config","text!./item.html"],function(a,b,c,d){"use strict";var e={options:{eventNamespace:"sulu.teaser-selection",resultKey:"teasers",dataAttribute:"teaser-selection",dataDefault:{},hidePositionElement:!0,hideConfigButton:!0,webspaceKey:null,locale:null,navigateEvent:"sulu.router.navigate",idKey:"teaserId",types:{},presentAs:[],translations:{noContentSelected:"sulu-content.teaser.no-teaser",add:"sulu-content.teaser.add-teaser"}},templates:{url:'/admin/api/teasers?ids=<%= ids.join(",") %>',item:c,itemForm:d,presentAsButton:'<span class="fa-eye present-as icon right border"><span class="selected-text"></span><span class="dropdown-toggle"></span></span>'},translations:{edit:"sulu-content.teaser.edit",edited:"sulu-content.teaser.edited",reset:"sulu-content.teaser.reset",apply:"sulu-content.teaser.apply",cancel:"sulu-content.teaser.cancel"}},f=function(){var b=$("<div/>");this.$addButton.parent().append(b),this.$addButton.append('<span class="dropdown-toggle"/>'),this.sandbox.start([{name:"dropdown@husky",options:{el:b,data:a.map(this.options.types,function(b,c){return a.extend({id:c,name:c},b)}),valueName:"title",trigger:this.$addButton,triggerOutside:!0,clickCallback:h.bind(this)}}])},g=function(){var b=$(this.templates.presentAsButton()),c=b.find(".selected-text"),d=$("<div/>"),e=this.getData().presentAs||"";b.insertAfter(this.$addButton),this.$addButton.parent().append(d),a.each(this.options.presentAs,function(a){return a.id===e?(c.text(a.name),!1):void 0}),this.sandbox.start([{name:"dropdown@husky",options:{el:d,instanceName:this.options.instanceName,data:this.options.presentAs,alignment:"right",trigger:b,triggerOutside:!0,clickCallback:function(b){c.text(b.name),this.setData(a.extend(this.getData(),{presentAs:b.id}))}.bind(this)}}])},h=function(b){var c=$('<div class="teaser-selection"/>'),d=$("<div/>"),e=this.getData().items||[];this.$el.append(c),this.sandbox.start([{name:"overlay@husky",options:{el:c,instanceName:this.options.instanceName,openOnStart:!0,removeOnClose:!0,cssClass:"type-"+b.name,skin:"large",slides:[{title:this.sandbox.translate(b.title),data:d,okCallback:function(){var a=this.getData();a.items=e,this.setData(a),this.sandbox.stop(d)}.bind(this)}]}}]),this.sandbox.once("husky.overlay."+this.options.instanceName+".initialized",function(){this.sandbox.start([{name:b.component,options:a.extend({el:d,locale:this.options.locale,webspaceKey:this.options.webspaceKey,instanceName:this.options.instanceName,type:b.name,data:a.filter(e,function(a){return a.type===b.name}),selectCallback:function(a){e.push(a)},deselectCallback:function(b){e=a.without(e,a.findWhere(e,b))}},b.componentOptions)}])}.bind(this))},i=function(){this.$el.on("click",".edit-teaser",function(a){return j.call(this,$(a.currentTarget).parents("li")),!1}.bind(this)),this.$el.on("click",".cancel-teaser-edit",function(a){return k.call(this,$(a.currentTarget).parents("li")),!1}.bind(this)),this.$el.on("click",".reset-teaser-edit",function(a){return m.call(this,$(a.currentTarget).parents("li")),!1}.bind(this)),this.$el.on("click",".apply-teaser-edit",function(a){return l.call(this,$(a.currentTarget).parents("li")),!1}.bind(this)),this.$el.on("click",".edit .image",function(a){n.call(this,$(a.currentTarget).parents("li"))}.bind(this))},j=function(a){var b=a.find(".view"),c=a.find(".edit"),d=this.getItem(a.data("id")),e=this.getApiItem(a.data("id")),f=c.find(".description-container"),g=$('<textarea class="form-element component description"></textarea>'),h=d.mediaId||e.mediaId;a.find(".move").hide(),f.children().remove(),f.append(g),b.addClass("hidden"),c.removeClass("hidden"),c.find(".title").val(d.title||""),c.find(".description").val(d.description||""),c.find(".moreText").val(d.moreText||""),c.find(".image-content").remove(),h?c.find(".image").prepend('<div class="image-content"><img class="mediaId" data-id="'+h+'" src="/admin/media/redirect/media/'+h+"?locale="+this.options.locale+'&format=50x50"/></div>'):c.find(".image").prepend('<div class="fa-picture-o image-content"/>'),this.sandbox.start([{name:"ckeditor@husky",options:{el:g,placeholder:this.cleanupText(e.description||""),autoStart:!1}}])},k=function(a){a.find(".view").removeClass("hidden"),a.find(".edit").addClass("hidden"),a.find(".move").show(),o.call(this,a)},l=function(b){var c=b.find(".view"),d=b.find(".edit"),e={title:d.find(".title").val()||null,description:d.find(".description").val()||null,moreText:d.find(".moreText").val()||null,mediaId:d.find(".mediaId").data("id")||null},f=this.isEdited(e);k.call(this,b),e=this.mergeItem(b.data("id"),e),e=a.defaults(e,this.getApiItem(b.data("id"))),c.find(".title").text(e.title),c.find(".description").text(this.cropAndCleanupText(e.description||"")),c.find(".image").remove(),e.mediaId&&c.find(".value").prepend('<span class="image"><img src="'+d.find(".mediaId").attr("src")+'"/></span>'),c.find(".edited").removeClass("hidden"),f||c.find(".edited").addClass("hidden")},m=function(b){var c=b.find(".view"),d=b.data("id"),e=this.getApiItem(d),f=this.getItem(d);k.call(this,b),f=a.omit(f,["title","description","moreText","mediaId"]),this.setItem(d,f),c.find(".title").text(e.title),c.find(".description").text(this.cropAndCleanupText(e.description||"")),c.find(".image").remove(),e.mediaId&&c.find(".value").prepend('<span class="image"><img src="/admin/media/redirect/media/'+e.mediaId+"?locale="+this.options.locale+'&format=50x50"/></span>'),c.find(".edited").addClass("hidden")},n=function(a){var b=$("<div/>"),c=a.data("id"),d=this.getApiItem(c);this.$el.append(b),this.sandbox.start([{name:"media-selection/overlay@sulumedia",options:{el:b,preselected:[d.mediaId],instanceName:"teaser-"+d.type+"-"+d.id,removeOnClose:!0,openOnStart:!0,singleSelect:!0,locale:this.options.locale,saveCallback:function(b){var c=b[0],d=a.find(".image-content");d.removeClass("fa-picture-o"),d.html('<img class="mediaId" data-id="'+c.id+'" src="'+c.thumbnails["sulu-50x50"]+'"/>')},removeCallback:function(){var b=a.find(".image-content");b.addClass("fa-picture-o"),b.html("")}}}])},o=function(a){this.sandbox.stop(a.find(".component"))};return{type:"itembox",defaults:e,apiItems:{},initialize:function(){this.$el.addClass("teaser-selection"),this.render(),f.call(this),0<this.options.presentAs.length&&g.call(this),i.call(this)},getUrl:function(b){var c=a.map(b.items||[],function(a){return a.type+";"+a.id});return this.templates.url({ids:c})},cleanupText:function(a){return $("<div>").html("<div>"+a+"</div>").text()},cropAndCleanupText:function(a,b){return b=b?b:50,this.sandbox.util.cropTail(this.cleanupText(a),b)},isEdited:function(b){return!a.isEqual(a.keys(b).sort(),["id","type"])},getItemContent:function(b){var c=this.getItem(b.teaserId),d=this.isEdited(c);return this.apiItems[b.teaserId]=b,b=a.defaults(c,b),this.templates.item(a.defaults(b,{apiItem:this.apiItems[b.teaserId],translations:this.translations,descriptionText:this.cropAndCleanupText(b.description||""),types:this.options.types,translate:this.sandbox.translate,locale:this.options.locale,mediaId:null,edited:d}))},sortHandler:function(b){var c=this.getData();c.items=a.map(b,function(a){var b=a.split(";");return{type:b[0],id:b[1]}}),this.setData(c,!1)},removeHandler:function(a){for(var b=this.getData(),c=b.items||[],d=a.split(";"),e=-1,f=c.length;++e<f;)if(d[0]==c[e].type&&d[1]==c[e].id){c.splice(e,1);break}b.items=c,this.setData(b,!1)},getItem:function(b){var c=this.getData().items||[],d=b.split(";");return a.find(c,function(a){return a.type==d[0]&&a.id==d[1]})},getApiItem:function(a){return this.apiItems[a]||null},mergeItem:function(b,c){var d=this.getData(),e=d.items||[],f=b.split(";");return d.items=a.map(e,function(b){return b.type!=f[0]||b.id!=f[1]?b:(c=a.defaults(c,b),a.omit(c,a.filter(a.keys(c),function(a){return null==c[a]})))}),this.setData(d,!1),this.getItem(b)},setItem:function(b,c){var d=this.getData(),e=d.items||[],f=b.split(";");return d.items=a.map(e,function(a){return a.type!=f[0]||a.id!=f[1]?a:c}),this.setData(d,!1),this.getItem(b)}}});
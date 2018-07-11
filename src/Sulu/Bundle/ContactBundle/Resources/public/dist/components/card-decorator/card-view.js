define(function(){"use strict";var a={unselectOnBackgroundClick:!0,imageFormat:"sulu-100x100",emptyListTranslation:"public.empty-list",fields:{firstInfoRow:["city","countryCode"],secondInfoRow:["mainEmail"]},separators:{title:" ",infoRow:", "},icons:{firstInfoRow:"fa-map-marker",secondInfoRow:"fa-envelope"}},b={cardGridClass:"card-grid",emptyIndicatorClass:"empty-list",selectedClass:"selected",actionNavigatorClass:"action-navigator",itemHeadClass:"item-head",itemInfoClass:"item-info"},c={item:['<div class="card-item">','   <div class="'+b.itemHeadClass+'">','       <div class="head-container">','           <div class="head-image '+b.actionNavigatorClass+'">','               <span class="<%= pictureIcon %> image-default"></span>','               <div class="image-content" style="background-image: url(\'<%= picture %>\')"></div>',"           </div>",'           <div class="head-name '+b.actionNavigatorClass+'"><%= name %></div>',"       </div>",'       <div class="head-checkbox custom-checkbox"><input type="checkbox"><span class="icon"></span></div>',"   </div>","</div>"].join(""),infoContainer:['<div class="'+[b.itemInfoClass,b.actionNavigatorClass].join(" ")+'"></div>'].join(""),infoRow:['<div class="info-row">','   <span class="<%= icon %> info-icon"></span>','   <span class="info-text"><%= text %></span>',"</div>"].join(""),emptyIndicator:['<div class="'+b.emptyIndicatorClass+'">','   <div class="fa-coffee icon"></div>',"   <span><%= text %></span>","</div>"].join("")},d=function(a,b,c){if(a&&b){var d=[];return b.forEach(function(b){a[b]&&d.push(a[b])}),d.join(c)}},e=function(a){var b=this.sandbox.util.extend(!1,{},a);return this.datagrid.matchings.forEach(function(a){var c=a.type===this.datagrid.types.THUMBNAILS?this.options.imageFormat:"";b[a.attribute]=this.datagrid.processContentFilter.call(this.datagrid,a.attribute,b[a.attribute],a.type,c)}.bind(this)),b};return function(){return{initialize:function(b,c){this.datagrid=b,this.sandbox=this.datagrid.sandbox,this.options=this.sandbox.util.extend(!0,{},a,c),this.setVariables()},setVariables:function(){this.rendered=!1,this.$el=null,this.$items={}},render:function(a,b){this.renderCardContainer(b),this.bindGeneralDomEvents(),this.renderRecords(a.embedded),this.rendered=!0},renderCardContainer:function(a){this.$el=this.sandbox.dom.createElement('<div class="card-grid-container"/>');var d=this.sandbox.util.template(c.emptyIndicator,{text:this.sandbox.translate(this.options.emptyListTranslation)});this.sandbox.dom.append(this.$el,d);var e=this.sandbox.dom.createElement('<div class="'+b.cardGridClass+'"/>');this.sandbox.dom.append(this.$el,e),this.sandbox.dom.append(a,this.$el)},updateEmptyIndicatorVisibility:function(){this.datagrid.data.embedded&&this.datagrid.data.embedded.length>0?this.sandbox.dom.hide("."+b.emptyIndicatorClass):this.sandbox.dom.show("."+b.emptyIndicatorClass)},bindGeneralDomEvents:function(){this.options.unselectOnBackgroundClick&&this.sandbox.dom.on("body","click.cards",function(){this.deselectAllRecords()}.bind(this))},renderRecords:function(a,b){"undefined"==typeof b&&(b=!0),this.updateEmptyIndicatorVisibility(),this.sandbox.util.foreach(a,function(a){var c,f,g,h,i,j=e.call(this,a);c=j.id,f=j[this.options.fields.picture].url||"",g=d(j,this.options.fields.title,this.options.separators.title),h=d(j,this.options.fields.firstInfoRow,this.options.separators.infoRow),i=d(j,this.options.fields.secondInfoRow,this.options.separators.infoRow),this.renderItem(c,f,g,h,i,b)}.bind(this))},renderItem:function(a,d,e,f,g,h){this.$items[a]=this.sandbox.dom.createElement(this.sandbox.util.template(c.item,{name:this.sandbox.util.cropTail(this.sandbox.util.escapeHtml(String(e)),25),picture:d,pictureIcon:this.options.icons.picture})),d&&this.sandbox.dom.addClass(this.sandbox.dom.find(".head-image",this.$items[a]),"no-default"),f&&this.addInfoRowToItem(this.$items[a],this.options.icons.firstInfoRow,f),g&&this.addInfoRowToItem(this.$items[a],this.options.icons.secondInfoRow,g),this.datagrid.itemIsSelected.call(this.datagrid,a)&&this.selectRecord(a),h?$("."+b.cardGridClass).append(this.$items[a]):$("."+b.cardGridClass).prepend(this.$items[a]),this.bindItemEvents(a)},addInfoRowToItem:function(a,d,e){var f=this.sandbox.dom.find("."+b.itemInfoClass,a);f.length||(f=this.sandbox.dom.createElement(this.sandbox.util.template(c.infoContainer)()),this.sandbox.dom.append(a,f)),this.sandbox.dom.append(f,this.sandbox.dom.createElement(this.sandbox.util.template(c.infoRow,{icon:d,text:this.sandbox.util.cropMiddle(this.sandbox.util.escapeHtml(String(e)),22)})))},extendOptions:function(a){this.options=this.sandbox.util.extend(!0,{},this.options,a)},destroy:function(){this.sandbox.dom.off("body","click.cards"),this.sandbox.dom.remove(this.$el)},bindItemEvents:function(a){this.sandbox.dom.on(this.$items[a],"click",function(b){this.sandbox.dom.stopPropagation(b),this.datagrid.itemAction.call(this.datagrid,a)}.bind(this),"."+b.actionNavigatorClass),this.sandbox.dom.on(this.$items[a],"click",function(b){this.sandbox.dom.stopPropagation(b),this.toggleItemSelected(a)}.bind(this))},toggleItemSelected:function(a){this.datagrid.itemIsSelected.call(this.datagrid,a)===!0?this.deselectRecord(a):this.selectRecord(a)},selectRecord:function(a){this.sandbox.dom.addClass(this.$items[a],b.selectedClass),this.sandbox.dom.is(this.sandbox.dom.find('input[type="checkbox"]',this.$items[a]),":checked")||this.sandbox.dom.prop(this.sandbox.dom.find('input[type="checkbox"]',this.$items[a]),"checked",!0),this.datagrid.setItemSelected.call(this.datagrid,a)},deselectRecord:function(a){this.sandbox.dom.removeClass(this.$items[a],b.selectedClass),this.sandbox.dom.is(this.sandbox.dom.find('input[type="checkbox"]',this.$items[a]),":checked")&&this.sandbox.dom.prop(this.sandbox.dom.find('input[type="checkbox"]',this.$items[a]),"checked",!1),this.datagrid.setItemUnselected.call(this.datagrid,a)},addRecord:function(a,b){this.renderRecords([a],b)},removeRecord:function(a){return this.$items[a]?(this.sandbox.dom.remove(this.$items[a]),this.datagrid.removeRecord.call(this.datagrid,a),this.updateEmptyIndicatorVisibility(),!0):!1},deselectAllRecords:function(){this.sandbox.util.each(this.$items,function(a){this.deselectRecord(Number(a))}.bind(this))}}}});
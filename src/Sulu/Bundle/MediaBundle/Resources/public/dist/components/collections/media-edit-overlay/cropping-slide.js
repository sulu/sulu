define(["services/sulumedia/user-settings-manager","services/sulumedia/format-manager","text!./toolbar-slide.html"],function(a,b,c){"use strict";var d=function(a){this.selectedFormat=this.formats[a],this.saved=!0,this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.disable","save",!1),this.selectedFormat.options?this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.enable","remove",!1):this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.disable","remove",!1),b.cropPossibleInInFormat(this.selectedFormat.scale.x,this.selectedFormat.scale.y,this.imageWidth,this.imageHeight)?this.sandbox.emit("husky.label.too-small-image-"+this.media.id+".vanish"):k.call(this),this.sandbox.emit("sulu.area-selection.cropping-"+this.media.id+".set-area-guide-dimensions",this.selectedFormat.scale.x,this.selectedFormat.scale.y,n.call(this,this.formatCrops[this.selectedFormat.key]))},e=function(a){a.id!==this.selectedFormat.key&&(this.saved?d.call(this,a.id):this.sandbox.sulu.showConfirmationDialog({title:"sulu-media.unsaved-crop-title",description:"sulu-media.unsaved-crop-description",callback:function(b){return b?void d.call(this,a.id):(this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.change","formats",this.selectedFormat.key),void this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.unmark",this.selectedFormat.key))}.bind(this)}))},f=function(){this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.loading","save");var a={key:this.selectedFormat.key,options:{cropX:this.$el.find(".area-selection").data("area").x,cropY:this.$el.find(".area-selection").data("area").y,cropWidth:this.$el.find(".area-selection").data("area").width,cropHeight:this.$el.find(".area-selection").data("area").height}};b.saveFormat(this.media.id,this.sandbox.sulu.user.locale,a).then(h.bind(this)).fail(function(){this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.disable","save",!1)}.bind(this))},g=function(){this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.loading","remove");var a={key:this.selectedFormat.key,options:{}};b.saveFormat(this.media.id,this.sandbox.sulu.user.locale,a).then(h.bind(this)).fail(function(){this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.disable","remove",!1)}.bind(this))},h=function(a){var b=!!a.options,c=this.media.thumbnails[a.key]+"&t="+(new Date).getTime();this.formatCrops[a.key]=a,this.selectedFormat=a,this.saved=!0,$('img[src="'+this.media.thumbnails[a.key]+'"]').each(function(a,b){b.src=c}),this.media.thumbnails[a.key]=c,this.sandbox.emit("sulu.medias.media.saved",this.media.id,this.media),this.sandbox.emit("husky.label.invalid-crop-"+this.media.id+".vanish"),this.sandbox.emit("husky.label.invalid-crops-"+this.media.id+".vanish"),b?(this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.disable","save",!0),this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.enable","remove",!1),this.sandbox.emit("sulu.labels.success.show","sulu-media.crop-save-success")):(this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.disable","remove",!0),this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.disable","save",!1),this.sandbox.emit("sulu.labels.success.show","sulu-media.crop-remove-success")),this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".items.set","formats",o.call(this)),this.sandbox.emit("sulu.area-selection.cropping-"+this.media.id+".set-area-guide-dimensions",this.selectedFormat.scale.x,this.selectedFormat.scale.y,n.call(this,a)),this.sandbox.emit("sulu.media-edit.preview.loading",this.sandbox.translate("sulu-media.saved-crops-not-visible"))},i=function(){var a=$.Deferred();return this.sandbox.start([{name:"area-selection@sulumedia",options:{el:this.$el.find(".area-selection"),instanceName:"cropping-"+this.media.id,image:this.media.url}}]),this.sandbox.once("sulu.area-selection.cropping-"+this.media.id+".initialized",j.bind(this,a)),a},j=function(a,b,c){this.imageWidth=b,this.imageHeight=c,this.selectedFormat=q.call(this),this.selectedFormat?this.sandbox.emit("sulu.area-selection.cropping-"+this.media.id+".set-area-guide-dimensions",this.selectedFormat.scale.x,this.selectedFormat.scale.y,n.call(this,this.selectedFormat)):k.call(this),a.resolve()},k=function(){var a=$('<div class="too-small"/>');this.sandbox.stop(this.$el.find(".label-container .too-small")),this.$el.find(".label-container").append(a),this.sandbox.start([{name:"label@husky",options:{el:a,type:"WARNING",instanceName:"too-small-image-"+this.media.id,title:"sulu-media.crop-not-possible",autoVanish:!1,description:"sulu-media.image-too-small",additionalLabelClasses:"small",hasClose:!1}}])},l=function(){var a=$('<div class="invalid-crop"/>');this.sandbox.stop(this.$el.find(".label-container .invalid-crop")),this.$el.find(".label-container").append(a),this.sandbox.start([{name:"label@husky",options:{el:a,type:"WARNING",instanceName:"invalid-crop-"+this.media.id,title:"sulu-media.crop-out-of-date",autoVanish:!1,description:"sulu-media.crop-out-of-date-text",additionalLabelClasses:"small"}}])},m=function(a){if(0!==a.length){var b=a.map(function(a){return a.title}).join(", "),c=$('<div class="invalid-crops"/>');this.$overlay.find(".info-label-container").append(c),this.sandbox.start([{name:"label@husky",options:{el:c,type:"WARNING",instanceName:"invalid-crops-"+this.media.id,title:"sulu-media.crops-out-of-date",autoVanish:!1,description:this.sandbox.translate("sulu-media.following-crops-out-of-date")+": "+b,additionalLabelClasses:"small"}}])}},n=function(a){return a?a.options&&!b.cropOptionsAreValid(a.options,a.scale.x,a.scale.y,this.imageWidth,this.imageHeight)?(l.call(this),null):(this.sandbox.emit("husky.label.invalid-crop-"+this.media.id+".vanish"),a.options?{width:a.options.cropWidth,height:a.options.cropHeight,x:a.options.cropX,y:a.options.cropY}:void 0):null},o=function(){var a,c=[];return $.each(this.formats,function(d){a=null,this.formatCrops[d]&&this.formatCrops[d].options&&!b.cropOptionsAreValid(this.formatCrops[d].options,this.formatCrops[d].scale.x,this.formatCrops[d].scale.y,this.imageWidth,this.imageHeight)?a="warning":this.formatCrops[d].options&&(a="checked"),c.push({id:d,title:this.formatCrops[d].title||d,styleClass:a})}.bind(this)),c},p=function(){this.sandbox.start([{name:"toolbar@husky",options:{el:this.$el.find(".toolbar"),instanceName:"cropping-"+this.media.id,skin:"big",buttons:[{id:"save",icon:"floppy-o",title:"public.save-and-apply",disabled:!0,callback:f.bind(this)},{id:"remove",icon:"trash-o",title:"sulu-media.remove-crop",disabled:!this.selectedFormat||!this.selectedFormat.options,callback:g.bind(this)},{id:"formats",icon:"picture-o",title:this.selectedFormat?this.selectedFormat.title:"sulu-media.image-formats",dropdownItems:o.call(this),dropdownOptions:{maxHeight:385,changeButton:!0,callback:e.bind(this)}}]}}])},q=function(){var a=null,c=null;return $.each(this.formats,function(d){c||(c=this.formatCrops[d]),!a&&b.cropPossibleInInFormat(this.formatCrops[d].scale.x,this.formatCrops[d].scale.y,this.imageWidth,this.imageHeight)&&(a=this.formatCrops[d])}.bind(this)),a||c},r=function(){var a=[];return $.each(this.formats,function(c){this.formatCrops[c]&&this.formatCrops[c].options&&!b.cropOptionsAreValid(this.formatCrops[c].options,this.formatCrops[c].scale.x,this.formatCrops[c].scale.y,this.imageWidth,this.imageHeight)&&a.push(this.formatCrops[c])}.bind(this)),a},s=function(){this.$el.find(".back").on("click",this.onBack)},t=function(){this.sandbox.on("sulu.area-selection.cropping-"+this.media.id+".area-changed",function(){this.saved=!1,this.sandbox.emit("husky.toolbar.cropping-"+this.media.id+".item.enable","save",!1)}.bind(this))};return{sandbox:null,media:null,formats:null,selectedFormat:null,$overlay:null,$el:null,imageWidth:null,imageHeight:null,onBack:null,initialize:function(a,b,d,e,f){this.$overlay=a,this.sandbox=b,this.media=d,this.formats=e,this.onBack=f,this.saved=!0,this.$el=$(_.template(c,{hint:this.sandbox.translate("sulu-media.crop-double-click-hint")}))},getSlideDefinition:function(){return{displayHeader:!1,data:this.$el,buttons:[],cancelCallback:function(){this.sandbox.stop()}.bind(this)}},start:function(){var a=$.Deferred();return t.call(this),s.call(this),b.loadFormats(this.media.id,this.sandbox.sulu.user.locale).then(function(b){this.formatCrops=b,i.call(this).then(function(){m.call(this,r.call(this)),p.call(this)}.bind(this)),a.resolve()}.bind(this)),a},destroy:function(){this.$el&&this.sandbox.stop(this.$el.find("*"))}}});
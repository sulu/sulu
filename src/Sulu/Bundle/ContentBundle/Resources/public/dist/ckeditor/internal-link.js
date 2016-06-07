define(["underscore","services/husky/translator","text!sulucontentcss/ckeditor-plugin.css"],function(a,b,c){"use strict";var d=function(a){var b=a.getStartElement(),c=b.getAscendant("sulu:link",!0);return c&&c.is("sulu:link")?{title:c.getText(),altTitle:c.getAttribute("title"),target:c.getAttribute("target"),href:c.getAttribute("href")}:{title:a.getSelectedText()}},e=function(a,b,c){var d=b.getStartElement();d&&d.is("sulu:link")||(d=a.document.createElement("sulu:link"),a.insertElement(d)),d.setAttribute("title",c.altTitle),d.setAttribute("href",c.href),d.setAttribute("target",c.target),d.setText(c.title),d.removeAttribute("sulu:validation-state"),c.published||d.setAttribute("sulu:validation-state","unpublished"),a.fire("change")},f=function(a,b){var c=d(b),e=b.getStartElement(),f=e.getAscendant("sulu:link",!0);f.remove(),a.insertText(c.title)};return function(g){return{tagName:"sulu:link",init:function(a){CKEDITOR.dtd[this.tagName]=1,CKEDITOR.dtd.body[this.tagName]=1,CKEDITOR.dtd.div[this.tagName]=1,CKEDITOR.dtd.li[this.tagName]=1,CKEDITOR.dtd.p[this.tagName]=1,CKEDITOR.dtd.$block[this.tagName]=1,CKEDITOR.dtd.$removeEmpty[this.tagName]=1,a.addCommand("internalLinkDialog",{dialogName:"internalLinkDialog",allowedContent:"sulu:link[title,target,sulu:validation-state,!href]",requiredContent:"sulu:link[href]",exec:function(){var b=$("<div/>");$("body").append(b),g.start([{name:"ckeditor-internal-link@sulucontent",options:{el:b,webspace:a.config.webspace,locale:a.config.locale,link:d(a.getSelection()),saveCallback:function(c){g.stop(b),e(a,a.getSelection(),c)},removeCallback:function(){f(a,a.getSelection())}}}])}}),a.addCommand("removeInternalLink",{exec:function(){f(a,a.getSelection())},refresh:function(){var b=a.getSelection(),c=b.getStartElement();return c.getAscendant("sulu:link",!0)?void this.setState(CKEDITOR.TRISTATE_OFF):void this.setState(CKEDITOR.TRISTATE_DISABLED)},contextSensitive:1,startDisabled:1}),a.ui.addButton("InternalLink",{label:g.translate("content.ckeditor.internal-link"),command:"internalLinkDialog",icon:"/bundles/sulucontent/img/icon_link_internal.png"}),a.contextMenu&&(a.addMenuGroup("suluGroup"),a.addMenuItem("internalLinkItem",{label:g.translate("content.ckeditor.internal-link.edit"),icon:"/bundles/sulucontent/img/icon_link_internal.png",command:"internalLinkDialog",group:"suluGroup"}),a.addMenuItem("removeInternalLinkItem",{label:g.translate("content.ckeditor.internal-link.remove"),icon:"/bundles/sulucontent/img/icon_remove_link_internal.png",command:"removeInternalLink",group:"suluGroup"}),a.contextMenu.addListener(function(a){return a.getAscendant("sulu:link",!0)?{internalLinkItem:CKEDITOR.TRISTATE_OFF,removeInternalLinkItem:CKEDITOR.TRISTATE_OFF}:void 0}))},onLoad:function(){CKEDITOR.addCss(a.template(c,{tag:"link",translations:{unpublished:b.translate("content.text_editor.error.unpublished"),removed:b.translate("content.text_editor.error.removed")}}))}}}});
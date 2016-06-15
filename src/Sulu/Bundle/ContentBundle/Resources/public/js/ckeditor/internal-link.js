define([
    'underscore',
    'services/husky/translator',
    'text!sulucontentcss/ckeditor-internal-link.css'
], function(_, Translator, editorCSS) {

    'use strict';

    var getLinkBySelection = function(selection) {
            var element = selection.getStartElement(),
                linkElement = element.getAscendant('sulu:link', true);

            if (!!linkElement && linkElement.is('sulu:link')) {
                return {
                    title: linkElement.getText(),
                    altTitle: linkElement.getAttribute('title'),
                    target: linkElement.getAttribute('target'),
                    href: linkElement.getAttribute('href')
                };
            }

            return {
                title: selection.getSelectedText()
            };
        },
        render = function(editor, selection, link) {
            var tag = selection.getStartElement();

            if (!tag || !tag.is('sulu:link')) {
                tag = editor.document.createElement('sulu:link');
                editor.insertElement(tag);
            }

            tag.setAttribute('title', link.altTitle);
            tag.setAttribute('href', link.href);
            tag.setAttribute('target', link.target);
            tag.setText(link.title);

            // only valid pages can be selected.
            tag.removeAttribute('sulu:validation-state');

            if (!link.published) {
                tag.setAttribute('sulu:validation-state', 'unpublished');
            }

            editor.fire('change');
        };

    return function(sandbox) {
        return {
            tagName: 'sulu:link',

            init: function(editor) {
                // extend dtd
                CKEDITOR.dtd[this.tagName] = 1;
                CKEDITOR.dtd.body['sulu:link'] = 1;
                CKEDITOR.dtd.div['sulu:link'] = 1;
                CKEDITOR.dtd.li['sulu:link'] = 1;
                CKEDITOR.dtd.p['sulu:link'] = 1;
                CKEDITOR.dtd.$block['sulu:link'] = 1;
                CKEDITOR.dtd.$removeEmpty['sulu:link'] = 1;

                editor.addCommand('internalLinkDialog', {
                    dialogName: 'internalLinkDialog',
                    allowedContent: 'sulu:link[title,target,sulu:validation-state,!href]',
                    requiredContent: 'sulu:link[href]',
                    exec: function() {
                        var $element = $('<div/>');
                        $('body').append($element);

                        sandbox.start([
                            {
                                name: 'ckeditor-internal-link@sulucontent',
                                options: {
                                    el: $element,
                                    webspace: editor.config.webspace,
                                    locale: editor.config.locale,
                                    link: getLinkBySelection(editor.getSelection()),
                                    saveCallback: function(link) {
                                        sandbox.stop($element);
                                        render(editor, editor.getSelection(), link);
                                    }
                                }
                            }
                        ]);
                    }
                });
                editor.addCommand('removeInternalLink', {
                    exec: function() {
                        var selection = editor.getSelection(),
                            link = getLinkBySelection(selection),
                            element = selection.getStartElement(),
                            linkElement = element.getAscendant('sulu:link', true);

                        linkElement.remove();
                        editor.insertText(link.title);
                    },
                    refresh: function() {
                        var selection = editor.getSelection(),
                            element = selection.getStartElement();

                        if (!element.getAscendant('sulu:link', true)) {
                            this.setState(CKEDITOR.TRISTATE_DISABLED);

                            return;
                        }

                        this.setState(CKEDITOR.TRISTATE_OFF);
                    },
                    contextSensitive: 1,
                    startDisabled: 1
                });

                editor.ui.addButton(
                    'InternalLink',
                    {
                        label: sandbox.translate('content.ckeditor.internal-link'),
                        command: 'internalLinkDialog',
                        icon: '/bundles/sulucontent/img/icon_link_internal.png'
                    }
                );
                editor.ui.addButton(
                    'RemoveInternalLink',
                    {
                        label: sandbox.translate('content.ckeditor.internal-link.remove'),
                        command: 'removeInternalLink',
                        icon: '/bundles/sulucontent/img/icon_remove_link_internal.png'
                    }
                );

                if (editor.contextMenu) {
                    editor.addMenuGroup('suluGroup');
                    editor.addMenuItem('internalLinkItem', {
                        label: sandbox.translate('content.ckeditor.internal-link.edit'),
                        icon: '/bundles/sulucontent/img/icon_link_internal.png',
                        command: 'internalLinkDialog',
                        group: 'suluGroup'
                    });
                    editor.addMenuItem('removeInternalLinkItem', {
                        label: sandbox.translate('content.ckeditor.internal-link.remove'),
                        icon: '/bundles/sulucontent/img/icon_remove_link_internal.png',
                        command: 'removeInternalLink',
                        group: 'suluGroup'
                    });

                    editor.contextMenu.addListener(function(element) {
                        if (element.getAscendant('sulu:link', true)) {
                            return {
                                internalLinkItem: CKEDITOR.TRISTATE_OFF,
                                removeInternalLinkItem: CKEDITOR.TRISTATE_OFF
                            };
                        }
                    });
                }
            },

            onLoad: function() {
                CKEDITOR.addCss(_.template(editorCSS, {
                    translations: {
                        unpublished: Translator.translate('content.text_editor.error.unpublished'),
                        removed: Translator.translate('content.text_editor.error.removed')
                    }
                }));
            }
        };
    };
});

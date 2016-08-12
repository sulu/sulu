define([
    'services/husky/translator',
    'text!sulumediacss/ckeditor/media-link-plugin.css'
], function(Translator, editorCSS) {

    'use strict';

    var getLinkBySelection = function(selection) {
            var element = selection.getStartElement(),
                linkElement = element.getAscendant('sulu:media', true);

            if (!!linkElement && linkElement.is('sulu:media')) {
                return {
                    title: linkElement.getText(),
                    id: linkElement.getAttribute('id')
                };
            }

            return {
                title: selection.getSelectedText()
            };
        },

        render = function(editor, selection, link) {
            var tag = selection.getStartElement();

            if (!tag || !tag.is('sulu:media')) {
                tag = editor.document.createElement('sulu:media');
                editor.insertElement(tag);
            }

            tag.setAttribute('id', link.id);
            tag.setText(link.title);

            // only valid medias can be selected.
            tag.removeAttribute('removed');

            editor.fire('change');
        },

        remove = function(editor, selection) {
            var link = getLinkBySelection(selection),
                element = selection.getStartElement(),
                linkElement = element.getAscendant('sulu:media', true);

            linkElement.remove();
            editor.insertText(link.title);
        };

    return function(sandbox) {
        return {
            tagName: 'sulu:media',

            init: function(editor) {
                this.extendCkEditorDtd();

                editor.addCommand('mediaLinkDialog', this.getMediaLinkDialogCommand(editor));
                editor.addCommand('removeMediaLink', this.getRemoveMediaLinkCommand(editor));

                editor.ui.addButton(
                    'MediaLink',
                    {
                        label: sandbox.translate('sulu-media.ckeditor.media-link'),
                        command: 'mediaLinkDialog',
                        icon: '/bundles/sulumedia/img/icon_link_media.png'
                    }
                );

                if (editor.contextMenu) {
                    this.addSuluMenuGroup(editor);

                    editor.contextMenu.addListener(function(element) {
                        if (element.getAscendant('sulu:media', true)) {
                            return {
                                mediaLinkItem: CKEDITOR.TRISTATE_OFF,
                                removeMediaLinkItem: CKEDITOR.TRISTATE_OFF
                            };
                        }
                    });
                }
            },

            extendCkEditorDtd: function() {
                CKEDITOR.dtd[this.tagName] = 1;
                CKEDITOR.dtd.body[this.tagName] = 1;
                CKEDITOR.dtd.div[this.tagName] = 1;
                CKEDITOR.dtd.li[this.tagName] = 1;
                CKEDITOR.dtd.p[this.tagName] = 1;
                CKEDITOR.dtd.$inline[this.tagName] = 1;
                CKEDITOR.dtd.$removeEmpty[this.tagName] = 1;
            },

            getMediaLinkDialogCommand: function(editor) {
                return {
                    dialogName: 'mediaLinkDialog',
                    allowedContent: 'sulu:media[title,removed,!id]',
                    requiredContent: 'sulu:media[id]',
                    exec: function() {
                        var $element = $('<div/>'),
                            link = getLinkBySelection(editor.getSelection());

                        $('#content').append($element);

                        sandbox.start([
                            {
                                name: 'media-selection/overlay@sulumedia',
                                options: {
                                    el: $element,
                                    webspace: editor.config.webspace,
                                    locale: editor.config.locale,
                                    preselected: !!link.id ? [link] : [],
                                    removeable: !!link.id,
                                    instanceName: 'media-link',
                                    translations: {
                                        title: 'sulu-media.ckeditor.media-link',
                                        save: 'sulu-media.ckeditor.media-link.dialog.save',
                                        remove: 'sulu-media.ckeditor.media-link.dialog.remove',
                                        selectedTitle: 'sulu-media.ckeditor.media-link.dialog.selected-title'
                                    },
                                    removeOnClose: true,
                                    openOnStart: true,
                                    singleSelect: true,
                                    saveCallback: function(items) {
                                        sandbox.stop($element);

                                        link.id = items[0].id;
                                        link.title = !!link.title ? link.title : items[0].title;

                                        render(editor, editor.getSelection(), link);
                                    },
                                    removeCallback: function() {
                                        remove(editor, editor.getSelection());
                                    }
                                }
                            }
                        ]);
                    }
                };
            },

            getRemoveMediaLinkCommand: function(editor) {
                return {
                    exec: function() {
                        remove(editor, editor.getSelection());
                    },
                    refresh: function() {
                        var selection = editor.getSelection(),
                            element = selection.getStartElement();

                        if (!element.getAscendant('sulu:media', true)) {
                            this.setState(CKEDITOR.TRISTATE_DISABLED);

                            return;
                        }

                        this.setState(CKEDITOR.TRISTATE_OFF);
                    },
                    contextSensitive: 1,
                    startDisabled: 1
                };
            },

            addSuluMenuGroup: function(editor) {
                editor.addMenuGroup('suluGroup');
                editor.addMenuItem('mediaLinkItem', {
                    label: sandbox.translate('sulu-media.ckeditor.media-link.edit'),
                    icon: '/bundles/sulumedia/img/icon_link_media.png',
                    command: 'mediaLinkDialog',
                    group: 'suluGroup'
                });
                editor.addMenuItem('removeMediaLinkItem', {
                    label: sandbox.translate('sulu-media.ckeditor.media-link.edit.remove'),
                    icon: '/bundles/sulumedia/img/icon_remove_link_media.png',
                    command: 'removeMediaLink',
                    group: 'suluGroup'
                });
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

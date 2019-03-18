// @flow
import React from 'react';
import {Observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import {downcastAttributeToElement} from '@ckeditor/ckeditor5-engine/src/conversion/downcast-converters';
import {upcastAttributeToAttribute} from '@ckeditor/ckeditor5-engine/src/conversion/upcast-converters';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import ContextualBalloon from '@ckeditor/ckeditor5-ui/src/panel/balloon/contextualballoon';
import ClickObserver from '@ckeditor/ckeditor5-engine/src/view/observer/clickobserver';
import {render, unmountComponentAtNode} from 'react-dom';
import ExternalLinkCommand from './ExternalLinkCommand';
import ExternalLinkOverlay from './ExternalLinkOverlay';
import ExternalLinkBalloonView from './ExternalLinkBalloonView';
import ExternalUnlinkCommand from './ExternalUnlinkCommand';
import type {ExternalLinkEventInfo} from './types';

// eslint-disable-next-line max-len
const LINK_ICON = '<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M11.077 15l.991-1.416a.75.75 0 1 1 1.229.86l-1.148 1.64a.748.748 0 0 1-.217.206 5.251 5.251 0 0 1-8.503-5.955c.02-.095.06-.189.12-.274l1.147-1.639a.75.75 0 1 1 1.228.86L4.933 10.7l.006.003a3.75 3.75 0 0 0 6.132 4.294l.006.004zm5.494-5.335a.748.748 0 0 1-.12.274l-1.147 1.639a.75.75 0 1 1-1.228-.86l.86-1.23a3.75 3.75 0 0 0-6.144-4.301l-.86 1.229a.75.75 0 0 1-1.229-.86l1.148-1.64a.748.748 0 0 1 .217-.206 5.251 5.251 0 0 1 8.503 5.955zm-4.563-2.532a.75.75 0 0 1 .184 1.045l-3.155 4.505a.75.75 0 1 1-1.229-.86l3.155-4.506a.75.75 0 0 1 1.045-.184z" fill="#000" fill-rule="evenodd"/></svg>';

export default class ExternalLinkPlugin extends Plugin {
    @observable open: boolean = false;
    balloon: ContextualBalloon;

    init() {
        this.externalLinkOverlayElement = document.createElement('div');
        this.editor.sourceElement.appendChild(this.externalLinkOverlayElement);
        this.balloon = this.editor.plugins.get(ContextualBalloon);
        this.balloonView = new ExternalLinkBalloonView(this.editor.locale);

        this.listenTo(this.balloonView, 'externalUnlink', () => {
            this.editor.execute('externalUnlink');
            this.hideBalloon();
        });

        render(
            (
                <Observer>
                    {() => (
                        <ExternalLinkOverlay
                            onCancel={this.handleOverlayClose}
                            onConfirm={this.handleOverlayConfirm}
                            open={this.open}
                        />
                    )}
                </Observer>
            ),
            this.externalLinkOverlayElement
        );

        this.editor.commands.add('externalLink', new ExternalLinkCommand(this.editor));
        this.editor.commands.add('externalUnlink', new ExternalUnlinkCommand(this.editor));

        this.editor.ui.componentFactory.add('externalLink', (locale) => {
            const button = new ButtonView(locale);

            button.bind('isEnabled').to(this.editor.commands.get('externalLink'));

            button.set({
                icon: LINK_ICON,
            });

            button.on('execute', action(() => {
                this.selection = this.editor.model.document.selection;
                this.open = true;
            }));

            return button;
        });

        this.editor.model.schema.extend('$text', {allowAttributes: 'linkTarget'});
        this.editor.model.schema.extend('$text', {allowAttributes: 'linkHref'});

        this.editor.conversion.for('upcast').add(upcastAttributeToAttribute({
            view: {
                name: 'a',
                key: 'target',
            },
            model: 'linkTarget',
        }));

        this.editor.conversion.for('downcast').add(downcastAttributeToElement({
            model: 'linkTarget',
            view: (attributeValue, writer) => {
                return writer.createAttributeElement('a', {target: attributeValue});
            },
        }));

        this.editor.conversion.for('upcast').add(upcastAttributeToAttribute({
            view: {
                name: 'a',
                key: 'href',
            },
            model: 'linkHref',
        }));

        this.editor.conversion.for('downcast').add(downcastAttributeToElement({
            model: 'linkHref',
            view: (attributeValue, writer) => {
                return writer.createAttributeElement('a', {href: attributeValue});
            },
        }));

        const view = this.editor.editing.view;
        view.addObserver(ClickObserver);

        this.listenTo(view.document, 'click', () => {
            const selection = this.editor.editing.view.document.selection;
            const firstPosition = selection.getFirstPosition();

            const externalLink = firstPosition.getAncestors().find(
                (ancestor) => ancestor.is('attributeElement') && ancestor.name === 'a'
            );

            this.hideBalloon();

            if (externalLink) {
                this.balloon.add({
                    position: {target: view.domConverter.mapViewToDom(externalLink)},
                    view: this.balloonView,
                });
            }
        });
    }

    hideBalloon() {
        if (this.balloon.hasView(this.balloonView)) {
            this.balloon.remove(this.balloonView);
        }
    }

    @action handleOverlayConfirm = (eventInfo: ExternalLinkEventInfo) => {
        this.editor.execute('externalLink', {...eventInfo, selection: this.selection});
        this.open = false;
    };

    @action handleOverlayClose = () => {
        this.open = false;
    };

    destroy() {
        unmountComponentAtNode(this.externalLinkOverlayElement);
        this.externalLinkOverlayElement.remove();
        this.externalLinkOverlayElement = undefined;
    }
}

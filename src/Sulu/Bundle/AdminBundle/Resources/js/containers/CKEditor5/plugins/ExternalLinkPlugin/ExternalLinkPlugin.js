// @flow
import React from 'react';
import {Observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import ContextualBalloon from '@ckeditor/ckeditor5-ui/src/panel/balloon/contextualballoon';
import ClickObserver from '@ckeditor/ckeditor5-engine/src/view/observer/clickobserver';
import {render, unmountComponentAtNode} from 'react-dom';
import ExternalLinkCommand from './ExternalLinkCommand';
import ExternalLinkOverlay from './ExternalLinkOverlay';
import ExternalLinkBalloonView from './ExternalLinkBalloonView';
import ExternalUnlinkCommand from './ExternalUnlinkCommand';
import {LINK_HREF_ATTRIBUTE, LINK_TARGET_ATTRIBUTE} from './constants';
// $FlowFixMe
import linkIcon from '!!raw-loader!./link.svg'; // eslint-disable-line import/no-webpack-loader-syntax

const DEFAULT_TARGET = '_self';

export default class ExternalLinkPlugin extends Plugin {
    @observable open: boolean = false;
    @observable target: ?string = DEFAULT_TARGET;
    @observable url: ?string;
    balloon: ContextualBalloon;

    init() {
        this.externalLinkOverlayElement = document.createElement('div');
        this.editor.sourceElement.appendChild(this.externalLinkOverlayElement);
        this.balloon = this.editor.plugins.get(ContextualBalloon);
        this.balloonView = new ExternalLinkBalloonView(this.editor.locale);
        this.balloonView.bind('href').to(this, 'href');

        this.listenTo(this.balloonView, 'externalUnlink', () => {
            this.editor.execute('externalUnlink');
            this.hideBalloon();
        });

        this.listenTo(this.balloonView, 'externalLink', action(() => {
            this.selection = this.editor.model.document.selection;
            const firstPosition = this.selection.getFirstPosition();
            const node = firstPosition.textNode || firstPosition.nodeBefore;

            this.target = node.getAttribute(LINK_TARGET_ATTRIBUTE);
            this.url = node.getAttribute(LINK_HREF_ATTRIBUTE);

            this.open = true;
            this.hideBalloon();
        }));

        render(
            (
                <Observer>
                    {() => (
                        <ExternalLinkOverlay
                            onCancel={this.handleOverlayClose}
                            onConfirm={this.handleOverlayConfirm}
                            onTargetChange={this.handleTargetChange}
                            onUrlChange={this.handleUrlChange}
                            open={this.open}
                            target={this.target}
                            url={this.url}
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

            button.bind('isEnabled').to(this.editor.commands.get('externalLink'), 'buttonEnabled');

            button.set({
                icon: linkIcon,
            });

            button.on('execute', action(() => {
                this.selection = this.editor.model.document.selection;
                this.open = true;
                this.target = DEFAULT_TARGET;
                this.url = undefined;
            }));

            return button;
        });

        this.editor.model.schema.extend('$text', {allowAttributes: 'linkTarget'});
        this.editor.model.schema.extend('$text', {allowAttributes: 'linkHref'});

        this.editor.conversion.for('upcast').attributeToAttribute({
            view: {
                name: 'a',
                key: 'target',
            },
            model: 'linkTarget',
        });

        this.editor.conversion.for('downcast').attributeToElement({
            model: 'linkTarget',
            view: (attributeValue, writer) => {
                return writer.createAttributeElement('a', {target: attributeValue});
            },
        });

        this.editor.conversion.for('upcast').attributeToAttribute({
            view: {
                name: 'a',
                key: 'href',
            },
            model: 'linkHref',
        });

        this.editor.conversion.for('downcast').attributeToElement({
            model: 'linkHref',
            view: (attributeValue, writer) => {
                return writer.createAttributeElement('a', {href: attributeValue});
            },
        });

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
                this.set('href', externalLink.getAttribute('href'));
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

    @action handleOverlayConfirm = () => {
        this.editor.execute('externalLink', {selection: this.selection, target: this.target, url: this.url});
        this.open = false;
    };

    @action handleOverlayClose = () => {
        this.open = false;
    };

    @action handleTargetChange = (target: ?string) => {
        this.target = target;
    };

    @action handleUrlChange = (url: ?string) => {
        this.url = url;
    };

    destroy() {
        unmountComponentAtNode(this.externalLinkOverlayElement);
        this.externalLinkOverlayElement.remove();
        this.externalLinkOverlayElement = undefined;
    }
}

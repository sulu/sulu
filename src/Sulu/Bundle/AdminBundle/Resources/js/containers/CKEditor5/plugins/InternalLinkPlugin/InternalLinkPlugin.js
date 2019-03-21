// @flow
import React, {Fragment} from 'react';
import {render, unmountComponentAtNode} from 'react-dom';
import {action, observable} from 'mobx';
import {Observer} from 'mobx-react';
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import ListView from '@ckeditor/ckeditor5-ui/src/list/listview';
import ListItemView from '@ckeditor/ckeditor5-ui/src/list/listitemview';
import {createDropdown} from '@ckeditor/ckeditor5-ui/src/dropdown/utils';
import internalLinkTypeRegistry from './registries/InternalLinkTypeRegistry';
// $FlowFixMe
import linkIcon from '!!raw-loader!./link.svg'; // eslint-disable-line import/no-webpack-loader-syntax

const DEFAULT_TARGET = '_self';

export default class InternalLinkPlugin extends Plugin {
    @observable openOverlay: ?string = undefined;
    @observable target: ?string = DEFAULT_TARGET;
    @observable id: ?string | number = undefined;

    init() {
        this.internalLinkElement = document.createElement('div');
        this.editor.sourceElement.appendChild(this.internalLinkElement);

        const locale = this.editor.config.get('internalLinks.locale');

        render(
            (
                <Observer>
                    {() => (
                        <Fragment>
                            {internalLinkTypeRegistry.getKeys().map((key) => {
                                const InternalLinkOverlay = internalLinkTypeRegistry.getOverlay(key);

                                return (
                                    <InternalLinkOverlay
                                        id={this.openOverlay === key ? this.id : undefined}
                                        key={key}
                                        locale={locale}
                                        onCancel={this.handleOverlayClose}
                                        onConfirm={this.handleOverlayConfirm}
                                        onIdChange={this.handleIdChange}
                                        onTargetChange={this.handleTargetChange}
                                        open={this.openOverlay === key}
                                        options={internalLinkTypeRegistry.getOptions(key)}
                                        target={this.target}
                                    />
                                );
                            })}
                        </Fragment>
                    )}
                </Observer>
            ),
            this.internalLinkElement
        );

        this.editor.ui.componentFactory.add('internalLink', (locale) => {
            const dropdownButton = createDropdown(locale);
            const list = new ListView(locale);

            dropdownButton.buttonView.set({
                icon: linkIcon,
            });

            internalLinkTypeRegistry.getKeys().forEach((key) => {
                const button = new ButtonView(locale);
                button.set({
                    label: internalLinkTypeRegistry.getTitle(key),
                    withText: true,
                });
                const listItem = new ListItemView(locale);
                listItem.children.add(button);
                button.delegate('execute').to(listItem);

                button.on('execute', action(() => {
                    this.openOverlay = key;
                    this.target = DEFAULT_TARGET;
                    this.id = undefined;
                }));

                list.items.add(listItem);
            });

            list.items.delegate('execute').to(dropdownButton);

            dropdownButton.panelView.children.add(list);

            return dropdownButton;
        });
    }

    @action handleOverlayConfirm = () => {
        this.openOverlay = undefined;
    };

    @action handleOverlayClose = () => {
        this.openOverlay = undefined;
    };

    @action handleTargetChange = (target: ?string) => {
        this.target = target;
    };

    @action handleIdChange = (id: ?string | number) => {
        this.id = id;
    };

    destroy() {
        unmountComponentAtNode(this.internalLinkElement);
        this.internalLinkElement.remove();
        this.internalLinkElement = undefined;
    }
}

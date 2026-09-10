// @flow
import React from 'react';
import log from 'loglevel';
import {ClassicEditor} from '@ckeditor/ckeditor5-editor-classic';
import {Essentials} from '@ckeditor/ckeditor5-essentials';
import {Paragraph} from '@ckeditor/ckeditor5-paragraph';
import {addPTags, removePTags} from './utils';
import configRegistry from './registries/configRegistry';
import pluginRegistry from './registries/pluginRegistry';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {ElementRef} from 'react';
import '@ckeditor/ckeditor5-ui/dist/index.css';
import '@ckeditor/ckeditor5-editor-classic/dist/index.css';
import '@ckeditor/ckeditor5-alignment/dist/index.css';
import '@ckeditor/ckeditor5-basic-styles/dist/index.css';
import '@ckeditor/ckeditor5-essentials/dist/index.css';
import '@ckeditor/ckeditor5-heading/dist/index.css';
import '@ckeditor/ckeditor5-language/dist/index.css';
import '@ckeditor/ckeditor5-list/dist/index.css';
import '@ckeditor/ckeditor5-paragraph/dist/index.css';
import '@ckeditor/ckeditor5-table/dist/index.css';
import '@ckeditor/ckeditor5-widget/dist/index.css';
import './ckeditor5.scss';
import type {TextEditorConfig} from '../TextEditor/types';

type Props = {|
    config: TextEditorConfig,
    disabled: boolean,
    locale?: ?IObservableValue<string>,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
    onFocus?: (event: { target: EventTarget }) => void,
    value: ?string,
|};

/**
 * React component that renders a classic ck-editor.
 *
 * Implementation is based upon the official ck-editor component:
 * https://github.com/ckeditor/ckeditor5-react/blob/089e28eafa64baf273c5e3690b08c1f8ee5ebbe5/src/ckeditor.jsx
 */
export default class CKEditor5 extends React.Component<Props> {
    containerRef: ?ElementRef<'div'>;
    editorInstance: any;

    static defaultProps = {
        disabled: false,
        value: '',
    };

    constructor(props: Props) {
        super(props);

        this.editorInstance = null;
    }

    setContainerRef = (containerRef: ?ElementRef<'div'>) => {
        this.containerRef = containerRef;
    };

    get enabledKeys(): Array<string> {
        const {config: {features, tags}} = this.props;

        return [...tags, ...features];
    }

    componentDidUpdate() {
        if (this.editorInstance) {
            const {config: {enterMode}, value, disabled} = this.props;

            if (disabled) {
                this.editorInstance.ui.element.classList.add('disabled');
                this.editorInstance.enableReadOnlyMode('disabled');
            } else {
                this.editorInstance.ui.element.classList.remove('disabled');
                this.editorInstance.disableReadOnlyMode('disabled');
            }

            const editorData = this.getEditorData();
            if (editorData !== value && !(value === '' && editorData === undefined)) {
                let finalValue = value;
                if (finalValue && enterMode === 'br') {
                    finalValue = addPTags(finalValue);
                }
                this.editorInstance.setData(finalValue);
            }
        }
    }

    componentDidMount() {
        const {config, locale} = this.props;
        const enabledKeys = this.enabledKeys;

        this.warnAboutUnclaimedKeys(enabledKeys);

        const defaultConfig = {
            licenseKey: 'GPL',
            toolbar: [],
            sulu: {
                locale: locale && locale.get(),
            },
            ui: {
                poweredBy: {
                    position: 'inside',
                    side: 'right',
                    label: '',
                    verticalOffset: 2,
                    horizontalOffset: 3,
                },
            },
        };

        ClassicEditor
            .create({
                attachTo: this.containerRef,
                plugins: [
                    Essentials,
                    Paragraph,
                    ...pluginRegistry.getPlugins(enabledKeys),
                ],
                ...configRegistry.getConfigs(enabledKeys).reduce((previousConfig, editorConfig) => {
                    return {...previousConfig, ...editorConfig(previousConfig, config)};
                }, defaultConfig),
            })
            .then((editor) => {
                this.editorInstance = editor;
                let value = this.props.value;
                if (value && config.enterMode === 'br') {
                    value = addPTags(value);
                }
                this.editorInstance.setData(value);

                const {disabled, onBlur, onChange, onFocus} = this.props;
                const {
                    model: {
                        document: modelDocument,
                    },
                    editing: {
                        view: {
                            document: viewDocument,
                        },
                    },
                } = this.editorInstance;

                if (disabled) {
                    this.editorInstance.enableReadOnlyMode('disabled');
                    this.editorInstance.ui.element.classList.add('disabled');
                }

                if (onBlur) {
                    viewDocument.on('blur', () => {
                        onBlur();
                    });
                }

                if (onFocus) {
                    viewDocument.on('focus', () => {
                        onFocus({
                            target: this.editorInstance.ui.element.querySelector('div[contenteditable="true"]'),
                        });
                    });
                }

                if (onChange) {
                    modelDocument.on('change', () => {
                        if (modelDocument.differ.getChanges().length > 0) {
                            onChange(this.getEditorData());
                        }
                    });
                }
            })
            .catch((error) => {
                log.error(error);
            });
    }

    componentWillUnmount() {
        if (this.editorInstance) {
            this.editorInstance.destroy().then(() => this.editorInstance = null);
        }
    }

    warnAboutUnclaimedKeys(enabledKeys: Array<string>) {
        const claimedKeys = [...pluginRegistry.keys, ...configRegistry.keys];
        const unclaimedKeys = enabledKeys.filter((key) => !claimedKeys.includes(key));

        if (unclaimedKeys.length > 0) {
            log.warn(
                'The text editor config enables the following tags or features, but no plugin or config is ' +
                'registered for them: ' + unclaimedKeys.sort().join(', ')
            );
        }
    }

    getEditorData() {
        const {config: {enterMode}} = this.props;

        const editorData = this.editorInstance.getData();
        return editorData === '' ? undefined : (enterMode === 'br' ? removePTags(editorData) : editorData);
    }

    render() {
        return <div ref={this.setContainerRef}></div>;
    }
}

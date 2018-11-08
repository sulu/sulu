// @flow
import React from 'react';
import type {ElementRef} from 'react';
import log from 'loglevel';
import AlignmentPlugin from '@ckeditor/ckeditor5-alignment/src/alignment';
import BoldPlugin from '@ckeditor/ckeditor5-basic-styles/src/bold';
import ClassicEditor from '@ckeditor/ckeditor5-editor-classic/src/classiceditor';
import EssentialsPlugin from '@ckeditor/ckeditor5-essentials/src/essentials';
import ItalicPlugin from '@ckeditor/ckeditor5-basic-styles/src/italic';
import ListPlugin from '@ckeditor/ckeditor5-list/src/list';
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph';
import StrikethroughPlugin from '@ckeditor/ckeditor5-basic-styles/src/strikethrough';
import UnderlinePlugin from '@ckeditor/ckeditor5-basic-styles/src/underline';
import TablePlugin from '@ckeditor/ckeditor5-table/src/table';
import TableToolbarPlugin from '@ckeditor/ckeditor5-table/src/tabletoolbar';
import './ckeditor5.scss';
import type {TextEditorProps} from '../../containers/TextEditor/types';

type Props = TextEditorProps;

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

    componentDidUpdate() {
        if (this.editorInstance) {
            const {value, disabled} = this.props;

            this.editorInstance.isReadOnly = disabled;
            if (disabled) {
                this.editorInstance.element.classList.add('disabled');
            } else {
                this.editorInstance.element.classList.remove('disabled');
            }

            if (this.editorInstance.getData() !== value) {
                this.editorInstance.setData(value);
            }
        }
    }

    componentDidMount() {
        ClassicEditor
            .create(this.containerRef, {
                plugins: [
                    AlignmentPlugin,
                    BoldPlugin,
                    EssentialsPlugin,
                    ItalicPlugin,
                    ListPlugin,
                    ParagraphPlugin,
                    StrikethroughPlugin,
                    UnderlinePlugin,
                    TablePlugin,
                    TableToolbarPlugin,
                ],
                toolbar: [
                    'bold',
                    'italic',
                    'underline',
                    'strikethrough',
                    '|',
                    'alignment:left',
                    'alignment:center',
                    'alignment:right',
                    'alignment:justify',
                    '|',
                    'bulletedlist',
                    'numberedlist',
                    '|',
                    'insertTable',
                ],
                table: {
                    contentToolbar: [
                        'tableColumn',
                        'tableRow',
                        'mergeTableCells',
                    ],
                },
            })
            .then((editor) => {
                this.editorInstance = editor;

                this.editorInstance.setData(this.props.value);

                const {disabled, onBlur, onChange} = this.props;
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

                this.editorInstance.isReadOnly = disabled;
                if (disabled) {
                    this.editorInstance.element.classList.add('disabled');
                }

                if (onBlur) {
                    viewDocument.on('blur', () => {
                        onBlur();
                    });
                }

                if (onChange) {
                    modelDocument.on('change', () => {
                        if (modelDocument.differ.getChanges().length > 0) {
                            const editorData = editor.getData();
                            onChange(editorData === '<p>&nbsp;</p>' ? undefined : editorData);
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

    render() {
        return <div ref={this.setContainerRef}></div>;
    }
}

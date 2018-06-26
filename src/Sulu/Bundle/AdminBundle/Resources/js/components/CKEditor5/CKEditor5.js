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
import './ckeditor5.scss';

type Props = {|
    onChange: (value: ?string) => void,
    value: ?string,
|};

export default class CKEditor5 extends React.Component<Props> {
    domContainer: ?ElementRef<'div'>;
    editorInstance: any;

    static defaultProps = {
        value: '',
    };

    constructor(props: Props) {
        super(props);

        this.editorInstance = null;
    }

    setDomContainerRef = (domContainer: ?ElementRef<'div'>) => {
        this.domContainer = domContainer;
    };

    shouldComponentUpdate() {
        return false;
    }

    componentDidUpdate() {
        const {value} = this.props;
        if (this.editorInstance && value) {
            this.editorInstance.setData(value);
        }
    }

    componentDidMount() {
        ClassicEditor
            .create(this.domContainer, {
                plugins: [
                    AlignmentPlugin,
                    BoldPlugin,
                    EssentialsPlugin,
                    ItalicPlugin,
                    ListPlugin,
                    ParagraphPlugin,
                    StrikethroughPlugin,
                    UnderlinePlugin,
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
                ],
            })
            .then((editor) => {
                this.editorInstance = editor;

                // TODO: Pass data via constructor.
                this.editorInstance.setData(this.props.value);

                if (this.props.onChange) {
                    const document = this.editorInstance.model.document;
                    document.on('change', () => {
                        if (document.differ.getChanges().length > 0) {
                            const editorData = editor.getData();
                            this.props.onChange(editorData === '<p>&nbsp;</p>' ? undefined : editorData);
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
            this.editorInstance.destroy();
        }
    }

    render() {
        return <div ref={this.setDomContainerRef}></div>;
    }
}

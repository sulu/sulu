// @flow
import React from 'react';
import type {ElementRef} from 'react';
import log from 'loglevel';
import BoldPlugin from '@ckeditor/ckeditor5-basic-styles/src/bold';
import ClassicEditor from '@ckeditor/ckeditor5-editor-classic/src/classiceditor';
import EssentialsPlugin from '@ckeditor/ckeditor5-essentials/src/essentials';
import ItalicPlugin from '@ckeditor/ckeditor5-basic-styles/src/italic';
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph';
import StrikethroughPlugin from '@ckeditor/ckeditor5-basic-styles/src/strikethrough';
import UnderlinePlugin from '@ckeditor/ckeditor5-basic-styles/src/underline';
import './textEditor.scss';

type Props = {
    data: string,
    onChange: (data: string) => void,
};

export default class TextEditor extends React.Component<Props> {
    domContainer: ?ElementRef<'div'>;
    editorInstance: any;

    static defaultProps = {
        data: '',
        config: {},
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
        const {data} = this.props;
        if (this.editorInstance && data) {
            this.editorInstance.setData(data);
        }
    }

    componentDidMount() {
        ClassicEditor
            .create(this.domContainer, {
                plugins: [
                    BoldPlugin,
                    EssentialsPlugin,
                    ItalicPlugin,
                    ParagraphPlugin,
                    StrikethroughPlugin,
                    UnderlinePlugin,
                ],
                toolbar: [
                    'bold',
                    'italic',
                    'underline',
                    'strikethrough',
                ],
            })
            .then((editor) => {
                this.editorInstance = editor;

                // TODO: Pass data via constructor.
                this.editorInstance.setData(this.props.data);

                if (this.props.onChange) {
                    const document = this.editorInstance.model.document;
                    document.on('change', () => {
                        if (document.differ.getChanges().length > 0) {
                            this.props.onChange(editor.getData());
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

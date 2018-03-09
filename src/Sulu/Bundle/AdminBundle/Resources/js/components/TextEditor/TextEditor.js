// @flow
import React, {Fragment} from 'react';
import {Editor} from 'slate-react';
import type {FieldTypeProps} from '../../types';
import htmlSerializer from './HtmlSerializer';
import Toolbar from './Toolbar';
import textEditorStyles from './textEditor.scss';

type Props = FieldTypeProps<string> & {
    placeholder?: string,
};

type State = {
    value: Object,
};

export default class TextEditor extends React.Component<Props, State> {
    constructor(props: Props) {
        super(props);

        if (props.value) {
            this.state = {
                value: htmlSerializer.deserialize(props.value),
            };
        } else {
            this.state = {
                value: htmlSerializer.deserialize('<br/>'),
            };
        }
    }

    handleChange = (change: Object) => {
        this.setState({value: change.value});
        this.props.onChange(htmlSerializer.serialize(change.value));
    };

    render() {
        const {
            placeholder,
            onFinish,
        } = this.props;

        return (
            <Fragment>
                <Toolbar onChange={this.handleChange} value={this.state.value} />
                <Editor
                    className={textEditorStyles.textEditor}
                    onBlur={onFinish}
                    onChange={this.handleChange}
                    value={this.state.value}
                    placeholder={placeholder}
                    renderMark={this.renderMark}
                    renderNode={this.renderNode}
                    spellCheck={false}
                />
            </Fragment>
        );
    }

    renderMark = (props: Object) => {
        const {children, mark} = props;
        switch (mark.type) {
            case 'bold':
                return <strong>{children}</strong>;
            case 'italic':
                return <em>{children}</em>;
            case 'underline':
                return <u>{children}</u>;
            case 'strikethrough':
                return <s>{children}</s>;
        }
    };

    renderNode = (props: Object) => {
        const {attributes, children, node} = props;
        switch (node.type) {
            case 'paragraph':
                return <p>{children}</p>;
            case 'ordered-list':
                return <ol {...attributes}>{children}</ol>;
            case 'unordered-list':
                return <ul {...attributes}>{children}</ul>;
            case 'list-item':
                return <li {...attributes}>{children}</li>;
        }
    };
}

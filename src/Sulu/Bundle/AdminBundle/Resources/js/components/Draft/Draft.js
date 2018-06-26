// @flow
import React, {Fragment} from 'react';
import {Editor, EditorState, RichUtils, ContentBlock, CompositeDecorator} from 'draft-js';
import 'draft-js/dist/Draft.css';
import isSoftNewlineEvent from 'draft-js/lib/isSoftNewlineEvent';
import type {DraftHandleValue} from 'draft-js/lib/DraftHandleValue';
import {stateToHTML} from 'draft-js-export-html';
import {stateFromHTML} from 'draft-js-import-html';
import classNames from 'classnames';
import Toolbar from './Toolbar';
import type {TextEditorProps} from './../../containers/TextEditor/types';
import draftStyles from './draft.scss';

type State = {
    editorState: EditorState,
};

const customStylesMap = {
    SUPERSCRIPT: {
        fontSize: 10,
        position: 'relative',
        top: -6,
        display: 'inline-flex',
    },
    SUBSCRIPT: {
        fontSize: 10,
        position: 'relative',
        bottom: -6,
        display: 'inline-flex',
    },
};

const stateToHTMLOptions = {
    inlineStyles: {
        SUPERSCRIPT: {element: 'sup'},
        SUBSCRIPT: {element: 'sub'},
    },
    blockStyleFn: (block) => {
        if (block.getData().get('text-align')) {
            return {
                style: {
                    'text-align': block.getData().get('text-align'),
                },
            };
        }
    },
};

const stateFromHTMLOptions = {
    elementStyles: {
        'sup': 'SUPERSCRIPT',
        'sub': 'SUBSCRIPT',
    },
    customBlockFn: (element) => {
        const elementStyle = element.style;
        if (elementStyle.hasOwnProperty('text-align')) {
            return {
                data: {
                    'text-align': elementStyle['text-align'],
                },
            };
        }
    },
};

const BASIC_BLOCKS = {
    'unstyled': 'unstyled',
    'header-one': 'header-one',
    'header-two': 'header-two',
    'header-three': 'header-three',
    'header-four': 'header-four',
    'header-five': 'header-five',
    'header-six': 'header-six',
};

function blockStyleFn(block: ContentBlock): string {
    const type = block.getType();
    const textAlign = block.getData().get('text-align');
    const classes = [];

    switch (type) {
        case 'unstyled':
            classes.push(draftStyles.paragraph);
    }

    if (textAlign && Object.keys(BASIC_BLOCKS).some((key) => BASIC_BLOCKS[key] === type)) {
        classes.push(draftStyles['text-align-' + textAlign]);
    }

    return classNames(classes);
}

const MAX_LIST_DEPTH = 4;

function findLinkEntities(contentBlock, callback, contentState) {
    contentBlock.findEntityRanges((character) => {
        const entityKey = character.getEntity();
        return (
            entityKey !== null &&
            contentState.getEntity(entityKey).getType() === 'LINK'
        );
    }, callback);
}

const Link = (props) => {
    const {url} = props.contentState.getEntity(props.entityKey).getData();
    return <a href={url} className={draftStyles.link}>{props.children}</a>;
};

export default class Draft extends React.Component<TextEditorProps, State> {
    constructor(props: TextEditorProps) {
        super(props);

        const decorator = new CompositeDecorator([
            {
                strategy: findLinkEntities,
                component: Link,
            },
        ]);

        if (props.value) {
            this.state = {
                editorState: EditorState.createWithContent(stateFromHTML(props.value, stateFromHTMLOptions), decorator),
            };
        } else {
            this.state = {
                editorState: EditorState.createEmpty(decorator),
            };
        }
    }

    handleChange = (editorState: EditorState) => {
        this.setState({editorState});
        this.props.onChange(stateToHTML(editorState.getCurrentContent(), stateToHTMLOptions));
    };

    handleBlur = () => {
        const {onBlur} = this.props;

        if (onBlur) {
            onBlur();
        }
    };

    handleTab = (event: SyntheticKeyboardEvent<>) => {
        const {
            editorState,
        } = this.state;
        const newEditorState = RichUtils.onTab(event, editorState, MAX_LIST_DEPTH);
        if (newEditorState !== editorState) {
            this.handleChange(newEditorState);
        }
    };

    handleReturn = (event: SyntheticKeyboardEvent<>, editorState: EditorState): DraftHandleValue => {
        if (isSoftNewlineEvent(event)) {
            const selection = editorState.getSelection();
            if (selection.isCollapsed()) {
                this.handleChange(RichUtils.insertSoftNewline(editorState));
                return 'handled';
            }
        }

        return 'not-handled';
    };

    render() {
        const {
            editorState,
        } = this.state;

        const {
            placeholder,
            valid,
        } = this.props;

        const textEditorClass = classNames(
            draftStyles.texteditor,
            {
                [draftStyles.error]: !valid,
            }
        );

        return (
            <Fragment>
                <Toolbar editorState={editorState} onChange={this.handleChange} />
                <div className={textEditorClass}>
                    <Editor
                        className={draftStyles.texteditor}
                        editorState={editorState}
                        onChange={this.handleChange}
                        onBlur={this.handleBlur}
                        placeholder={placeholder}
                        onTab={this.handleTab}
                        handleReturn={this.handleReturn} // eslint-disable-line react/jsx-handler-names
                        blockStyleFn={blockStyleFn}
                        customStyleMap={customStylesMap}
                    />
                </div>
                <div>{stateToHTML(editorState.getCurrentContent(), stateToHTMLOptions)}</div>
            </Fragment>
        );
    }
}

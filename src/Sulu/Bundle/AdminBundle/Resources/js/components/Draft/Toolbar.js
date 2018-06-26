// @flow
import React from 'react';
import {EditorState, RichUtils, Modifier} from 'draft-js';
import Button from './Button';
import Spacer from './Spacer';
import toolbarStyles from './toolbar.scss';
import type {ToolbarItem, ToolbarConfig} from './types';

type Props = {
    editorState: EditorState,
    onChange: (state: EditorState) => any,
};

// TODO: make configurable
const toolbarConfig: ToolbarConfig = [
    {type: 'button', handle: 'inline-style', style: 'BOLD', icon: 'su-bold'},
    {type: 'button', handle: 'inline-style', style: 'ITALIC', icon: 'su-italic'},
    {type: 'button', handle: 'inline-style', style: 'UNDERLINE', icon: 'su-underline'},
    {type: 'button', handle: 'inline-style', style: 'STRIKETHROUGH', icon: 'su-strikethrough'},
    {type: 'button', handle: 'inline-style', style: 'SUPERSCRIPT', icon: 'su-superscript'},
    {type: 'button', handle: 'inline-style', style: 'SUBSCRIPT', icon: 'su-subscript'},
    {type: 'spacer'},
    {type: 'button', handle: 'block-type', style: 'ordered-list-item', icon: 'su-list-ol'},
    {type: 'button', handle: 'block-type', style: 'unordered-list-item', icon: 'su-list-ul'},
    {type: 'spacer'},
    {type: 'button', handle: 'alignment', style: 'left', icon: 'su-align-left'},
    {type: 'button', handle: 'alignment', style: 'center', icon: 'su-align-center'},
    {type: 'button', handle: 'alignment', style: 'right', icon: 'su-align-right'},
    {type: 'button', handle: 'alignment', style: 'justify', icon: 'su-align-justify'},
    {type: 'spacer'},
    {type: 'button', handle: 'anchor', style: 'anchor', icon: 'su-link2'},
];

const ToolbarItemTypes = {
    Button: 'button',
    Spacer: 'spacer',
};

export default class Toolbar extends React.Component<Props> {
    handleToggle = (handle: string, style: string) => {
        switch (handle) {
            case 'inline-style':
                this.handleToggleInlineStyle(style);
                break;
            case 'block-type':
                this.handleToggleBlockType(style);
                break;
            case 'alignment':
                this.handleToggleAlignment(style);
                break;
            case 'anchor':
                this.handleAnchor();
                break;
        }
    };

    handleToggleInlineStyle = (inlineStyle: string) => {
        let newEditorState = RichUtils.toggleInlineStyle(
            this.props.editorState,
            inlineStyle
        );

        if (inlineStyle === 'SUPERSCRIPT' || inlineStyle === 'SUBSCRIPT') {
            const removeStyle = inlineStyle === 'SUBSCRIPT' ? 'SUPERSCRIPT' : 'SUBSCRIPT';
            const contentState = Modifier.removeInlineStyle(
                newEditorState.getCurrentContent(),
                newEditorState.getSelection(),
                removeStyle
            );
            newEditorState = EditorState.push(newEditorState, contentState, 'change-inline-style');
        }

        if (newEditorState) {
            this.props.onChange(newEditorState);
        }
    };

    handleToggleBlockType = (blockType: string) => {
        this.props.onChange(
            RichUtils.toggleBlockType(
                this.props.editorState,
                // $FlowFixMe
                blockType
            )
        );
    };

    handleToggleAlignment = (alignment: string) => {
        const {
            editorState,
        } = this.props;

        const contentState = Modifier.setBlockData(
            editorState.getCurrentContent(),
            editorState.getSelection(),
            { 'text-align': alignment });

        const newEditorState = EditorState.push(editorState, contentState, 'change-block-data');

        if (newEditorState) {
            this.props.onChange(newEditorState);
        }
    };

    handleAnchor = () => {
        const {editorState} = this.props;
        const selection = editorState.getSelection();
        if (!selection.isCollapsed()) {
            const contentState = editorState.getCurrentContent();
            const startKey = editorState.getSelection().getStartKey();
            const startOffset = editorState.getSelection().getStartOffset();
            const blockWithLinkAtBeginning = contentState.getBlockForKey(startKey);
            const linkKey = blockWithLinkAtBeginning.getEntityAt(startOffset);

            let url = '';
            if (linkKey) {
                const linkInstance = contentState.getEntity(linkKey);
                url = linkInstance.getData().url;
            }

            this.setState({
                showURLInput: true,
                urlValue: url,
            }, () => { });
        }
    };

    render() {
        const {editorState} = this.props;
        const currentInlineStyle = editorState.getCurrentInlineStyle();
        const selection = editorState.getSelection();
        const currentBlock = editorState.getCurrentContent().getBlockForKey(selection.getStartKey());
        const currentBlockType = currentBlock.getType();
        const currentAlignment = currentBlock.getData().get('text-align') || 'left';

        return (
            <div className={toolbarStyles.toolbar}>
                {
                    toolbarConfig.map(
                        (itemConfig: ToolbarItem, index: number) => {
                            switch (itemConfig.type) {
                                case ToolbarItemTypes.Button:
                                    let active = false;
                                    switch (itemConfig.handle) {
                                        case 'inline-style':
                                            active = currentInlineStyle.has(itemConfig.style);
                                            break;
                                        case 'block-type':
                                            active = itemConfig.style === currentBlockType;
                                            break;
                                        case 'alignment':
                                            active = itemConfig.style === currentAlignment;
                                    }

                                    return (
                                        <Button
                                            {...itemConfig}
                                            onToggle={this.handleToggle}
                                            active={active}
                                            key={index}
                                        />
                                    );
                                case ToolbarItemTypes.Spacer:
                                    return (<Spacer {...itemConfig} key={index} />);
                            }
                        }
                    )
                }
            </div>
        );
    }
}

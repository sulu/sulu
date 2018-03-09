// @flow
import React from 'react';
import Button from './Button';
import ButtonGroup from './ButtonGroup';
import toolbarStyles from './toolbar.scss';

type Props = {
    value: Object,
    onChange: (change: Object) => void,
};

type ButtonConfig = {
    type: string,
    icon: string,
};

const DEFAULT_NODE = 'paragraph';

const toolbarConfig = {
    MARK_TYPE_BUTTONS: [
        {type: 'bold', icon: 'fa-bold'},
        {type: 'italic', icon: 'fa-italic'},
        {type: 'underline', icon: 'fa-underline'},
        {type: 'strikethrough', icon: 'fa-strikethrough'},
        {type: 'superscript', icon: 'fa-superscript'},
        {type: 'subscript', icon: 'fa-subscript'},
    ],
    BLOCK_TYPE_BUTTONS: [
        {type: 'ordered-list', icon: 'fa-list-ol'},
        {type: 'unordered-list', icon: 'fa-list-ul'},
    ],
};

export default class Toolbar extends React.Component<Props> {
    handleMarkType = (type: string) => {
        const { value } = this.props;
        this.props.onChange(
            value.change().toggleMark(type)
        );
    };

    handleBlockType = (type: string) => {
        const { value } = this.props;
        const change = value.change();
        const { document } = value;

        // Handle everything but list buttons.
        if (type != 'unordered-list' && type != 'ordered-list') {
            const isActive = this.hasBlock(type);
            const isList = this.hasBlock('list-item');

            if (isList) {
                change.setBlocks(isActive ? DEFAULT_NODE : type)
                    .unwrapBlock('unordered-list')
                    .unwrapBlock('ordered-list');
            } else {
                change.setBlocks(isActive ? DEFAULT_NODE : type);
            }
        } else {
            // Handle the extra wrapping required for list buttons.
            const isList = this.hasBlock('list-item');
            const isType = value.blocks.some((block) => {
                return !!document.getClosest(block.key, (parent) => parent.type == type);
            });

            if (isList && isType) {
                change.setBlocks(DEFAULT_NODE)
                    .unwrapBlock('unordered-list')
                    .unwrapBlock('ordered-list');
            } else if (isList) {
                change.unwrapBlock(type == 'unordered-list' ? 'ordered-list' : 'unordered-list')
                    .wrapBlock(type);
            } else {
                change.setBlocks('list-item').wrapBlock(type);
            }
        }

        this.props.onChange(change);
    };

    hasMark = (type: string) => {
        const {value} = this.props;
        return value.activeMarks.some((mark) => mark.type == type);
    };

    hasBlock = (type: string) => {
        const {value} = this.props;
        return value.blocks.some((node) => node.type == type);
    };

    render() {
        return (
            <div className={toolbarStyles.toolbar}>
                <ButtonGroup>
                    {
                        toolbarConfig.MARK_TYPE_BUTTONS.map((button: ButtonConfig, index: number) => (
                            <Button
                                key={index}
                                active={this.hasMark(button.type)}
                                type={button.type}
                                icon={button.icon}
                                onToggle={this.handleMarkType}
                            />
                        ))
                    }
                </ButtonGroup>
                <ButtonGroup>
                    {
                        toolbarConfig.BLOCK_TYPE_BUTTONS.map((button: ButtonConfig, index: number) => (
                            <Button
                                key={index}
                                active={this.hasBlock(button.type)}
                                type={button.type}
                                icon={button.icon}
                                onToggle={this.handleBlockType}
                            />
                        ))
                    }
                </ButtonGroup>
            </div>
        );
    }
}

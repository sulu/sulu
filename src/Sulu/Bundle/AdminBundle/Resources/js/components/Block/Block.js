// @flow
import React from 'react';
import type {Node} from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import blockStyles from './block.scss';

type Props = {
    children: Node,
    dragHandle?: Node,
    expanded: boolean,
    onCollapse: () => void,
    onExpand: () => void,
    onRemove?: () => void,
};

export default class Block extends React.Component<Props> {
    static defaultProps: {
        expanded: false,
    };

    handleCollapse = () => {
        const {expanded, onCollapse} = this.props;
        if (expanded) {
            onCollapse();
        }
    };

    handleExpand = () => {
        const {expanded, onExpand} = this.props;
        if (!expanded) {
            onExpand();
        }
    };

    handleRemove = () => {
        const {onRemove} = this.props;

        if (onRemove) {
            onRemove();
        }
    };

    render() {
        const {children, dragHandle, expanded, onRemove} = this.props;

        const blockClass = classNames(
            blockStyles.block,
            {
                [blockStyles.expanded]: expanded,
            }
        );

        return (
            <section className={blockClass} onClick={this.handleExpand}>
                <div className={blockStyles.handle}>
                    {dragHandle}
                </div>
                <div className={blockStyles.content}>
                    {expanded &&
                        <header className={blockStyles.header}>
                            {onRemove && <Icon name="trash-o" onClick={this.handleRemove} />}
                            <Icon name="times" onClick={this.handleCollapse} />
                        </header>
                    }
                    <article>{children}</article>
                </div>
            </section>
        );
    }
}

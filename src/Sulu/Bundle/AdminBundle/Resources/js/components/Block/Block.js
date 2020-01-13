// @flow
import React from 'react';
import type {Node} from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import SingleSelect from '../SingleSelect';
import blockStyles from './block.scss';

type Props = {
    activeType?: string,
    children: Node,
    dragHandle?: Node,
    expanded: boolean,
    onCollapse: () => void,
    onExpand: () => void,
    onRemove?: () => void,
    onTypeChange?: (type: string | number) => void,
    types?: {[key: string]: string},
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

    handleTypeChange = (type: string | number) => {
        const {onTypeChange} = this.props;

        if (onTypeChange) {
            onTypeChange(type);
        }
    };

    render() {
        const {activeType, children, dragHandle, expanded, onRemove, types} = this.props;

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
                            {types && Object.keys(types).length > 1 &&
                                <div className={blockStyles.types}>
                                    <SingleSelect onChange={this.handleTypeChange} value={activeType}>
                                        {Object.keys(types).map((key) => (
                                            <SingleSelect.Option key={key} value={key}>
                                                {types[key]}
                                            </SingleSelect.Option>
                                        ))}
                                    </SingleSelect>
                                </div>
                            }
                            <div className={blockStyles.icons}>
                                {onRemove && <Icon name="su-trash-alt" onClick={this.handleRemove} />}
                                <Icon name="su-angle-up" onClick={this.handleCollapse} />
                            </div>
                        </header>
                    }
                    <article>{children}</article>
                </div>
            </section>
        );
    }
}

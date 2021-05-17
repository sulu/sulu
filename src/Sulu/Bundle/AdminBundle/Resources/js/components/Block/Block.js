// @flow
import React, {Fragment} from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import SingleSelect from '../SingleSelect';
import blockStyles from './block.scss';
import type {Node} from 'react';

type Props<T: string> = {
    activeType?: T,
    children: Node,
    dragHandle?: Node,
    expanded: boolean,
    icons?: Array<string>,
    onCollapse?: () => void,
    onExpand?: () => void,
    onRemove?: () => void,
    onSettingsClick?: () => void,
    onTypeChange?: (type: T) => void,
    types?: {[key: T]: string},
};

export default class Block<T: string> extends React.Component<Props<T>> {
    static defaultProps: {
        expanded: false,
    };

    handleCollapse = () => {
        const {expanded, onCollapse} = this.props;
        if (expanded && onCollapse) {
            onCollapse();
        }
    };

    handleExpand = () => {
        const {expanded, onExpand} = this.props;
        if (!expanded && onExpand) {
            onExpand();
        }
    };

    handleTypeChange: (type: T) => void = (type) => {
        const {onTypeChange} = this.props;

        if (onTypeChange) {
            onTypeChange(type);
        }
    };

    render() {
        const {
            activeType,
            children,
            dragHandle,
            icons,
            onCollapse,
            onExpand,
            onRemove,
            onSettingsClick,
            types,
        } = this.props;

        const expanded = this.props.expanded || (!onCollapse && !onExpand);

        const blockClass = classNames(
            blockStyles.block,
            {
                [blockStyles.expanded]: expanded,
            }
        );

        return (
            <section className={blockClass} onClick={this.handleExpand} role="switch">
                {dragHandle &&
                    <div className={blockStyles.handle}>
                        {dragHandle}
                    </div>
                }
                <div className={blockStyles.content}>
                    <header className={blockStyles.header}>
                        {expanded
                            ? <Fragment>
                                {types && Object.keys(types).length > 1 &&
                                    <div className={blockStyles.types}>
                                        <SingleSelect onChange={this.handleTypeChange} value={activeType}>
                                            {Object.keys(types).map((key) => (
                                                // $FlowFixMe
                                                <SingleSelect.Option key={key} value={key}>
                                                    {types[key]}
                                                </SingleSelect.Option>
                                            ))}
                                        </SingleSelect>
                                    </div>
                                }
                                {icons &&
                                    <div className={blockStyles.icons}>
                                        {icons.map((icon) => <Icon key={icon} name={icon} />)}
                                    </div>
                                }
                                <div className={blockStyles.iconButtons}>
                                    {onSettingsClick && <Icon name="su-cog" onClick={onSettingsClick} />}
                                    {onRemove && <Icon name="su-trash-alt" onClick={onRemove} />}
                                    {onCollapse && onExpand &&
                                        <Icon name="su-angle-up" onClick={this.handleCollapse} />
                                    }
                                </div>
                            </Fragment>
                            : <Fragment>
                                {icons &&
                                    <div className={blockStyles.icons}>
                                        {icons.map((icon) => <Icon key={icon} name={icon} />)}
                                    </div>
                                }
                                {types && activeType && <div className={blockStyles.type}>{types[activeType]}</div>}
                                {onCollapse && onExpand && <Icon name="su-angle-down" />}
                            </Fragment>
                        }
                    </header>
                    <article className={blockStyles.children}>{children}</article>
                </div>
            </section>
        );
    }
}

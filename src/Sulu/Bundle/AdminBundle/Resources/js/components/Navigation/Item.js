// @flow
import React from 'react';
import classNames from 'classnames';
import type {ChildrenArray, Element} from 'react';
import Icon from '../Icon';
import itemStyles from './item.scss';

type Props = {
    children?: ChildrenArray<Element<typeof Item> | false>,
    value: *,
    title: string,
    active?: boolean,
    onClick?: (value: *) => void,
    expanded?: boolean,
    icon?: string,
};

export default class Item extends React.PureComponent<Props> {
    handleClick = () => {
        const {onClick, value} = this.props;

        if (!onClick) {
            return;
        }

        onClick(value);
    };

    render() {
        const {title, children, expanded, icon} = this.props;
        let {active} = this.props;

        // check for active children
        if (children) {
            React.Children.forEach(children, (child: Element<typeof Item>) => {
                if (child.props.active) {
                    active = true;
                }
            });
        }

        const itemClass = classNames(
            itemStyles.item,
            {
                [itemStyles.active]: active,
                [itemStyles.hasChildren]: !!children,
            }
        );

        return (
            <div className={itemClass}>
                <div className={itemStyles.title} onClick={this.handleClick}>
                    {icon && <Icon name={icon} />}
                    {title}
                </div>
                {expanded && children &&
                    <div>{children}</div>
                }
                {children &&
                    <Icon
                        onClick={this.handleClick}
                        className={itemStyles.childrenIndicator}
                        name={expanded ? 'su-arrow-down' : 'su-arrow-right'}
                    />
                }
            </div>
        );
    }
}

// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import itemStyles from './item.scss';
import type {ChildrenArray, Element} from 'react';

type Props = {
    active?: boolean,
    children?: ChildrenArray<Element<typeof Item> | false>,
    expanded?: boolean,
    icon?: string,
    onClick?: (value: string) => void,
    title: string,
    value: string,
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
            }
        );

        return (
            <div className={itemClass}>
                <button className={itemStyles.title} onClick={this.handleClick} type="button">
                    {icon && <Icon className={itemStyles.icon} name={icon} />}
                    <span className={itemStyles.text}>{title}</span>
                    {children &&
                        <Icon
                            className={itemStyles.childrenIndicator}
                            name={expanded ? 'su-angle-down' : 'su-angle-right'}
                        />
                    }
                </button>

                {expanded && children &&
                    <div>{children}</div>
                }
            </div>
        );
    }
}

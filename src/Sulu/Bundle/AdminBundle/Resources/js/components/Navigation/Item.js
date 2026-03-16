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
    href?: string,
    icon?: string,
    onClick?: (value: string) => void,
    onLinkClick?: (event: SyntheticMouseEvent<HTMLAnchorElement>, value: string) => void,
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

    handleLinkClick = (event: SyntheticMouseEvent<HTMLAnchorElement>) => {
        const {onLinkClick, value} = this.props;

        if (!onLinkClick) {
            return;
        }

        onLinkClick(event, value);
    };

    render() {
        const {title, children, expanded, icon, href} = this.props;
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
                {href
                    ? (
                        <a
                            className={itemStyles.title}
                            href={href}
                            onClick={this.handleLinkClick}
                        >
                            {icon && <Icon className={itemStyles.icon} name={icon} />}
                            <span className={itemStyles.text}>{title}</span>
                            {children &&
                                <Icon
                                    className={itemStyles.childrenIndicator}
                                    name={expanded ? 'su-angle-down' : 'su-angle-right'}
                                />
                            }
                        </a>
                    )
                    : (
                        <button
                            className={itemStyles.title}
                            onClick={this.handleClick}
                            type="button"
                        >
                            {icon && <Icon className={itemStyles.icon} name={icon} />}
                            <span className={itemStyles.text}>{title}</span>
                            {children &&
                                <Icon
                                    className={itemStyles.childrenIndicator}
                                    name={expanded ? 'su-angle-down' : 'su-angle-right'}
                                />
                            }
                        </button>
                    )
                }

                {expanded && children &&
                    <div>{children}</div>
                }
            </div>
        );
    }
}

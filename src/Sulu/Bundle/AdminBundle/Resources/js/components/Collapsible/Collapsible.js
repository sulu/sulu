// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import collapsibleStyles from './collapsible.scss';
import Action from './Action';
import type {ActionConfig} from './types';
import type {Node} from 'react';

type Props = {|
    actions: Array<ActionConfig>,
    children: Node,
    expanded: boolean,
    extra?: Node,
    handle?: Node,
    onCollapse?: () => void,
    onExpand?: () => void,
    subtitle?: string,
    title: string,
|};

export default class Collapsible extends React.Component<Props> {
    static defaultProps = {
        actions: [],
        expanded: false,
    };

    get collapsible(): boolean {
        const {onCollapse, onExpand} = this.props;

        return !!onCollapse && !!onExpand;
    }

    get expanded(): boolean {
        return this.props.expanded || !this.collapsible;
    }

    handleClick = () => {
        const {onExpand} = this.props;

        if (!this.expanded && onExpand) {
            onExpand();
        }
    };

    handleToggleClick = (event: SyntheticEvent<HTMLButtonElement>) => {
        event.stopPropagation();

        const {onCollapse, onExpand} = this.props;

        if (this.expanded) {
            if (onCollapse) {
                onCollapse();
            }
        } else if (onExpand) {
            onExpand();
        }
    };

    render() {
        const {actions, children, extra, handle, subtitle, title} = this.props;
        const expanded = this.expanded;

        const collapsibleClass = classNames(
            collapsibleStyles.collapsible,
            {
                [collapsibleStyles.expanded]: expanded,
            }
        );

        return (
            <section className={collapsibleClass} onClick={this.handleClick} role="switch">
                {handle &&
                    <div className={collapsibleStyles.handle}>{handle}</div>
                }
                <div className={collapsibleStyles.content}>
                    <header className={collapsibleStyles.header}>
                        <span className={collapsibleStyles.title}>{title}</span>
                        {subtitle &&
                            <span className={collapsibleStyles.subtitle}>{subtitle}</span>
                        }
                        <div className={collapsibleStyles.spacer} />
                        {extra &&
                            <div className={collapsibleStyles.extra}>{extra}</div>
                        }
                        {actions.map((action) => (
                            <Action action={action} key={action.icon + action.label} />
                        ))}
                        {this.collapsible &&
                            <button
                                aria-expanded={expanded}
                                className={collapsibleStyles.toggle}
                                onClick={this.handleToggleClick}
                                type="button"
                            >
                                <Icon name={expanded ? 'su-collapse-vertical' : 'su-expand-vertical'} />
                            </button>
                        }
                    </header>
                    {expanded &&
                        <article className={collapsibleStyles.children}>{children}</article>
                    }
                </div>
            </section>
        );
    }
}

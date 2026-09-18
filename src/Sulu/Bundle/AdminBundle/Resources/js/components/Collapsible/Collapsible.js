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

    handleHeaderClick = () => {
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
        const {actions, children, handle, subtitle, title} = this.props;
        const expanded = this.expanded;

        const collapsibleClass = classNames(
            collapsibleStyles.collapsible,
            {
                [collapsibleStyles.expanded]: expanded,
            }
        );

        const headerClass = classNames(
            collapsibleStyles.header,
            {
                [collapsibleStyles.toggleable]: this.collapsible,
            }
        );

        return (
            <section className={collapsibleClass}>
                {handle &&
                    <div className={collapsibleStyles.handle}>{handle}</div>
                }
                <div className={collapsibleStyles.content}>
                    <header
                        className={headerClass}
                        onClick={this.collapsible ? this.handleHeaderClick : undefined}
                        role="switch"
                    >
                        <span className={collapsibleStyles.title}>{title}</span>
                        {subtitle &&
                            <span className={collapsibleStyles.subtitle}>{subtitle}</span>
                        }
                        <div className={collapsibleStyles.spacer} />
                        {actions.map((action, index) => (
                            <Action action={action} key={index} />
                        ))}
                        {this.collapsible &&
                            // No own click handler: the click bubbles to the header, which toggles.
                            <button
                                aria-expanded={expanded}
                                className={collapsibleStyles.toggle}
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

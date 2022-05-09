// @flow
import React, {Fragment} from 'react';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Icon from '../Icon';
import SingleSelect from '../SingleSelect';
import blockStyles from './block.scss';
import ActionPopover from './ActionPopover';
import type {ActionConfig} from './types';
import type {ElementRef, Node} from 'react';

type Props<T: string> = {
    actions: Array<ActionConfig>,
    activeType?: T,
    children: Node,
    dragHandle?: Node,
    expanded: boolean,
    icons?: Array<string>,
    onCollapse?: () => void,
    onExpand?: () => void,
    onSettingsClick?: () => void,
    onTypeChange?: (type: T) => void,
    types?: {[key: T]: string},
};

@observer
class Block<T: string> extends React.Component<Props<T>> {
    static defaultProps: {
        actions: [],
        expanded: false,
    };

    @observable actionsIconRef: ?ElementRef<'*'>;
    @observable showActionsPopover = false;

    @action setActionsIconRef = (ref: ?ElementRef<'*'>) => {
        this.actionsIconRef = ref;
    };

    @action handleActionsIconClick = () => {
        this.showActionsPopover = true;
    };

    @action handleActionsPopoverClose = () => {
        this.showActionsPopover = false;
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
            actions,
            activeType,
            children,
            dragHandle,
            icons,
            onCollapse,
            onExpand,
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
                                    {actions.length > 0 && <Icon
                                        iconRef={this.setActionsIconRef}
                                        name="su-circle"
                                        onClick={this.handleActionsIconClick}
                                    /> }
                                    {onSettingsClick && <Icon name="su-cog" onClick={onSettingsClick} />}
                                    {onCollapse && onExpand &&
                                        <Icon name="su-angle-up" onClick={this.handleCollapse} />
                                    }
                                </div>
                                <ActionPopover
                                    actions={actions}
                                    anchorElement={this.actionsIconRef}
                                    onClose={this.handleActionsPopoverClose}
                                    open={this.showActionsPopover}
                                />
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

export default Block;

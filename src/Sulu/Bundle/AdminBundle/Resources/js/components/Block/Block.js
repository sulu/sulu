// @flow
import React, {Fragment} from 'react';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import log from 'loglevel';
import Icon from '../Icon';
import SingleSelect from '../SingleSelect';
import {translate} from '../../utils';
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
    onRemove?: () => void, // @deprecated
    onSettingsClick?: () => void,
    onTypeChange?: (type: T) => void,
    types?: {[key: T]: string},
};

@observer
class Block<T: string> extends React.Component<Props<T>> {
    static defaultProps = {
        actions: [],
        expanded: false,
    };

    @observable actionsButtonRef: ?ElementRef<'*'>;
    @observable showActionsPopover = false;

    @computed get actions(): Array<ActionConfig> {
        const {onRemove, actions} = this.props;

        // @deprecated
        if (onRemove) {
            log.warn(
                'The "onRemove" prop of the "Block" component is deprecated since 2.5 and will ' +
                'be removed. Use the "actions" prop with an appropriate callback instead.'
            );

            return [
                ...actions,
                {
                    type: 'button',
                    icon: 'su-trash-alt',
                    label: translate('sulu_admin.delete'),
                    onClick: onRemove,
                },
            ];
        }

        return actions;
    }

    @action setActionsButtonRef = (ref: ?ElementRef<'*'>) => {
        this.actionsButtonRef = ref;
    };

    @action handleActionsButtonClick = () => {
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
                                    {this.actions.length > 0 && (
                                        <button
                                            onClick={this.handleActionsButtonClick}
                                            ref={this.setActionsButtonRef}
                                            type="button"
                                        >
                                            <Icon
                                                name="su-more-circle"
                                            />
                                        </button>
                                    ) }
                                    {onSettingsClick && (
                                        <button
                                            onClick={onSettingsClick}
                                            type="button"
                                        >
                                            <Icon name="su-cog" />
                                        </button>
                                    )}
                                    {onCollapse && onExpand && (
                                        <button
                                            onClick={this.handleCollapse}
                                            type="button"
                                        >
                                            <Icon name="su-collapse-vertical" />
                                        </button>
                                    )}
                                </div>
                                <ActionPopover
                                    actions={this.actions}
                                    anchorElement={this.actionsButtonRef}
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
                                {onCollapse && onExpand && <Icon name="su-expand-vertical" />}
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

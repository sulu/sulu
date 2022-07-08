// @flow
import React from 'react';
import classnames from 'classnames';
import Checkbox from '../Checkbox';
import {translate} from '../../utils';
import Icon from '../Icon';
import Tooltip from '../Tooltip';
import blockToolbarStyles from './blockToolbar.scss';
import type {BlockToolbarMode} from './types';

type Props = {|
    actions: Array<{|
        handleClick: () => void,
        icon: string,
        label: string,
    |}>,
    allSelected: boolean,
    mode: BlockToolbarMode,
    onCancel?: () => void,
    onSelectAll?: () => void,
    onUnselectAll?: () => void,
    selectedCount: any,
|};

class BlockToolbar extends React.Component<Props> {
    static defaultProps = {
        actions: [],
        allSelected: false,
        mode: 'static',
        selectedCount: 0,
    };

    constructor(props: Props) {
        super(props);
    }

    handleChangeSelectAll = () => {
        const {onSelectAll, onUnselectAll, allSelected} = this.props;

        if (onSelectAll && !allSelected) {
            onSelectAll();
        } else if (onUnselectAll && allSelected) {
            onUnselectAll();
        }
    };

    handleCancel = () => {
        const {onCancel} = this.props;

        if (onCancel) {
            onCancel();
        }
    };

    render() {
        const {
            actions,
            allSelected,
            selectedCount,
            mode,
        } = this.props;

        return (
            <section className={classnames(blockToolbarStyles.container, blockToolbarStyles[mode])}>
                <div className={blockToolbarStyles.divide}>
                    <div className={blockToolbarStyles.selected}>
                        {translate('sulu_admin.%count%_selected', {count: selectedCount})}
                    </div>

                    <div>
                        <Checkbox
                            checked={allSelected}
                            onChange={this.handleChangeSelectAll}
                            size="small"
                        >
                            {translate('sulu_admin.select_all')}
                        </Checkbox>
                    </div>
                </div>

                <div className={blockToolbarStyles.divide}>
                    <div className={blockToolbarStyles.actionList}>
                        {actions.map((action) => (
                            <Tooltip key={action.label} label={action.label}>
                                <button
                                    aria-label={action.label}
                                    className={classnames(blockToolbarStyles.actionButton, {
                                        [blockToolbarStyles.actionButtonDisabled]: selectedCount === 0,
                                    })}
                                    disabled={selectedCount === 0}
                                    onClick={action.handleClick}
                                    type="button"
                                >
                                    <Icon
                                        className={blockToolbarStyles.actionButtonIcon}
                                        name={action.icon}
                                    />
                                </button>
                            </Tooltip>
                        ))}
                    </div>

                    <div>
                        <button
                            className={blockToolbarStyles.cancelButton}
                            onClick={this.handleCancel}
                            type="button"
                        >
                            <Icon
                                className={blockToolbarStyles.cancelButtonIcon}
                                name="su-cancel"
                            />

                            {translate('sulu_admin.cancel')}
                        </button>
                    </div>
                </div>
            </section>
        );
    }
}

export default BlockToolbar;

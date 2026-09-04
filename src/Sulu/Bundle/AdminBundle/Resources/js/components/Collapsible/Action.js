// @flow
import React from 'react';
import Icon from '../Icon';
import collapsibleStyles from './collapsible.scss';
import type {ActionConfig} from './types';

type Props = {|
    action: ActionConfig,
|};

export default class Action extends React.PureComponent<Props> {
    handleClick = (event: SyntheticEvent<HTMLButtonElement>) => {
        event.stopPropagation();

        this.props.action.onClick();
    };

    render() {
        const {action} = this.props;

        return (
            <button
                aria-label={action.label}
                className={collapsibleStyles.action}
                onClick={this.handleClick}
                type="button"
            >
                <Icon name={action.icon} />
            </button>
        );
    }
}

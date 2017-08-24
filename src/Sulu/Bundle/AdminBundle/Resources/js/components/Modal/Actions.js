// @flow
import React from 'react';
import type {Action} from './types';
import actionsStyles from './actions.scss';

type Props = {
    actions: Array<Action>,
};

export default class Actions extends React.PureComponent<Props> {
    render() {
        if (this.props.actions.length > 0) {
            return (
                <div className={actionsStyles.actions}>
                    {this.props.actions.map((action, index) => {
                        const handleButtonClick = action.onClick;
                        return (
                            <button
                                key={index}
                                className={actionsStyles.action}
                                onClick={handleButtonClick}>{action.title}</button>
                        );
                    })}
                </div>
            );
        }

        return null;
    }
}

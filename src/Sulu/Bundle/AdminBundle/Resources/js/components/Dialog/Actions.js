// @flow
import React from 'react';
import type {Action} from './types';

type Props = {
    actions: Array<Action>,
};

export default class Actions extends React.PureComponent<Props> {
    render() {
        const {actions} = this.props;
        if (!actions.length) {
            return null;
        }

        return (
            <div>
                {actions.map((action, index) => {
                    const handleButtonClick = action.onClick;
                    return (
                        <button
                            key={index}
                            onClick={handleButtonClick}
                        >
                            {action.title}
                        </button>
                    );
                })}
            </div>
        );
    }
}

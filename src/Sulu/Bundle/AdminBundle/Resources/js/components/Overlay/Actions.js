// @flow
import React from 'react';
import Button from '../Button';
import type {Action} from './types';
import actionsStyles from './actions.scss';

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
            <div className={actionsStyles.actions}>
                {actions.map((action, index) => {
                    const handleButtonClick = action.onClick;
                    return (
                        <Button
                            key={index}
                            onClick={handleButtonClick}
                            skin="link"
                        >
                            {action.title}
                        </Button>
                    );
                })}
            </div>
        );
    }
}

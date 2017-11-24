// @flow
import React from 'react';

type Props = {
    adapter: string,
    active: boolean,
    onClick: (adapter: string) => void,
};

export default class AdapterSwitchItem extends React.PureComponent<Props> {
    handleClick = () => {
        const {
            adapter,
            onClick,
        } = this.props;

        onClick(adapter);
    };

    render() {
        const {
            active,
            adapter,
        } = this.props;

        return (
            <li key={adapter} onClick={this.handleClick}>
                {active ? (
                    <i>{adapter}</i>
                ) : adapter}
            </li>
        );
    }
}

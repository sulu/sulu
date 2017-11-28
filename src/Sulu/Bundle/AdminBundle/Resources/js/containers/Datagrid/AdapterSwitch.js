// @flow
import React from 'react';
import AdapterSwitchItem from './AdapterSwitchItem';

type Props = {
    adapters: Array<string>,
    currentAdapter: string,
    onAdapterChange: (adapter: string) => void,
};

export default class AdapterSwitch extends React.PureComponent<Props> {
    handleAdapterChange = (adapter: string) => {
        this.props.onAdapterChange(adapter);
    };

    render() {
        const {
            currentAdapter,
            adapters,
        } = this.props;

        if (adapters.length < 2) {
            return null;
        }

        return (
            <ul>
                {adapters.map((adapter, index) => (
                    <AdapterSwitchItem
                        adapter={adapter}
                        key={index}
                        active={adapter === currentAdapter}
                        onClick={this.handleAdapterChange}
                    />
                ))}
            </ul>
        );
    }
}

// @flow
import React from 'react';
import ButtonGroup from '../../components/ButtonGroup';
import Button from '../../components/Button';
import Icon from '../../components/Icon';
import datagridAdapterRegistry from './registries/DatagridAdapterRegistry';

type Props = {
    adapters: Array<string>,
    currentAdapter: string,
    onAdapterChange: (adapter: string) => void,
};

export default class AdapterSwitch extends React.PureComponent<Props> {
    handleAdapterChange = (adapter: ?string) => {
        if (!adapter || this.props.currentAdapter === adapter) {
            return;
        }

        this.props.onAdapterChange(adapter);
    };

    render() {
        const {
            currentAdapter,
            adapters,
        } = this.props;

        if (2 > adapters.length) {
            return null;
        }

        return (
            <ButtonGroup>
                {adapters.map((adapter, index) => {
                    const Adapter = datagridAdapterRegistry.get(adapter);

                    return (
                        <Button
                            value={adapter}
                            key={index}
                            active={adapter === currentAdapter}
                            onClick={this.handleAdapterChange}
                        >
                            <Icon name={Adapter.icon} />
                        </Button>
                    );
                })}
            </ButtonGroup>
        );
    }
}

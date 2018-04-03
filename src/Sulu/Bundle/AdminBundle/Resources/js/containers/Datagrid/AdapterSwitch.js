// @flow
import React, {Fragment} from 'react';
import datagridAdapterRegistry from './registries/DatagridAdapterRegistry';
import ButtonGroup from '../../components/ButtonGroup';
import Button from '../../components/Button';
import Icon from '../../components/Icon';

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

// @flow
import React from 'react';
import type {Element} from 'react';
import {observer} from 'mobx-react';
import ResourceListStore from '../../stores/ResourceListStore';
import Loader from '../../components/Loader';
import SingleSelect from '../../components/SingleSelect';

type Props<T> = {|
    disabled?: boolean,
    displayProperty: string,
    idProperty: string,
    onChange: (value: T) => void,
    resourceKey: string,
    value: ?T,
|};

@observer
export default class ResourceSingleSelect<T: string | number> extends React.Component<Props<T>> {
    resourceListStore: ResourceListStore;

    constructor(props: Props<T>) {
        super(props);

        const {
            resourceKey,
        } = this.props;

        this.resourceListStore = new ResourceListStore(resourceKey);
    }

    render() {
        const {disabled, displayProperty, idProperty, onChange, value} = this.props;

        if (this.resourceListStore.loading) {
            return <Loader size={30} />;
        }

        return (
            <SingleSelect disabled={disabled} onChange={onChange} value={value}>
                {this.resourceListStore.data.map((object, index) => ((
                    <SingleSelect.Option key={index} value={object[idProperty]}>
                        {object[displayProperty]}
                    </SingleSelect.Option>
                ): Element<typeof SingleSelect.Option>))}
            </SingleSelect>
        );
    }
}

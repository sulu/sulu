// @flow
import React from 'react';
import {observer} from 'mobx-react';
import MultiSelectComponent from '../../components/MultiSelect';
import ResourceListStore from '../../stores/ResourceListStore';
import Loader from '../../components/Loader/Loader';

type Props<T> = {|
    allSelectedText?: string,
    displayProperty: string,
    idProperty: string,
    noneSelectedText?: string,
    onChange: (value: Array<T>) => void,
    resourceKey: string,
    values: ?Array<T>,
|};

@observer
export default class MultiSelect<T> extends React.Component<Props<T>> {
    static defaultProps = {
        idProperty: 'id',
    };

    resourceListStore: ResourceListStore;

    constructor(props: Props<T>) {
        super(props);

        const {
            resourceKey,
        } = this.props;

        this.resourceListStore = new ResourceListStore(resourceKey);
    }

    render() {
        const {
            allSelectedText,
            noneSelectedText,
            onChange,
            displayProperty,
            idProperty,
            values,
        } = this.props;

        if (this.resourceListStore.loading || !this.resourceListStore.data) {
            return <Loader />;
        }

        return (
            <MultiSelectComponent
                allSelectedText={allSelectedText}
                noneSelectedText={noneSelectedText}
                onChange={onChange}
                values={values ? values : []}
            >
                {this.resourceListStore.data.map((object, index) => (
                    <MultiSelectComponent.Option key={index} value={object[idProperty]}>
                        {object[displayProperty]}
                    </MultiSelectComponent.Option>
                ))}
            </MultiSelectComponent>
        );
    }
}

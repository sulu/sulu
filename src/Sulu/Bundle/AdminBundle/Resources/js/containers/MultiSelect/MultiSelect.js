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
    onChange: (values: Array<T>, valueObjects?: Array<Object>) => void,
    resourceKey: string,
    values: ?Array<T>,
    apiOptions: Object,
|};

@observer
export default class MultiSelect<T> extends React.Component<Props<T>> {
    static defaultProps = {
        apiOptions: {},
        idProperty: 'id',
    };

    resourceListStore: ResourceListStore;

    constructor(props: Props<T>) {
        super(props);

        const {
            resourceKey,
            apiOptions,
        } = this.props;

        this.resourceListStore = new ResourceListStore(resourceKey, apiOptions);
    }

    handleChange = (values: Array<T>) => {
        const {
            onChange,
            idProperty,
        } = this.props;

        if (onChange.length === 1) {
            onChange(values);
        }

        const valueObjects = this.resourceListStore.data.filter((dataValue) => {
            return values.includes(dataValue[idProperty]);
        });

        onChange(values, valueObjects);
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
                onChange={this.handleChange}
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

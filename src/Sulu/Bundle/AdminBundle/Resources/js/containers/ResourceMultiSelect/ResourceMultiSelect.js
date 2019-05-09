// @flow
import React from 'react';
import type {Element} from 'react';
import {observer} from 'mobx-react';
import MultiSelectComponent from '../../components/MultiSelect';
import ResourceListStore from '../../stores/ResourceListStore';
import Loader from '../../components/Loader';

type Props<T: string | number> = {|
    allSelectedText?: string,
    apiOptions: Object,
    disabled: boolean,
    displayProperty: string,
    idProperty: string,
    noneSelectedText?: string,
    onChange: (values: Array<T>, valueObjects?: Array<Object>) => void,
    resourceKey: string,
    values: Array<T>,
|};

@observer
class ResourceMultiSelect<T: string | number> extends React.Component<Props<T>> {
    static defaultProps = {
        apiOptions: {},
        disabled: false,
        idProperty: 'id',
        values: [],
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

    // TODO: Remove explicit type annotation when flow bug is fixed
    // https://github.com/facebook/flow/issues/6978
    handleChange: (Array<T>) => void = (values: Array<T>) => {
        const {
            onChange,
            idProperty,
        } = this.props;

        const valueObjects = this.resourceListStore.data.filter((dataValue) => {
            return values.includes(dataValue[idProperty]);
        });

        onChange(values, valueObjects);
    };

    render() {
        const {
            allSelectedText,
            disabled,
            noneSelectedText,
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
                disabled={disabled}
                noneSelectedText={noneSelectedText}
                onChange={this.handleChange}
                values={values}
            >
                {this.resourceListStore.data.map((object, index) => ((
                    <MultiSelectComponent.Option key={index} value={object[idProperty]}>
                        {object[displayProperty]}
                    </MultiSelectComponent.Option>
                ): Element<typeof MultiSelectComponent.Option>))}
            </MultiSelectComponent>
        );
    }
}

export default ResourceMultiSelect;

// @flow
import type {Element} from 'react';
import React from 'react';
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import MultiSelectComponent from '../../components/MultiSelect';
import ResourceListStore from '../../stores/ResourceListStore';
import Loader from '../../components/Loader';

type Props<T: string | number> = {|
    allSelectedText?: string,
    disabled: boolean,
    displayProperty: string,
    idProperty: string,
    noneSelectedText?: string,
    onChange: (values: Array<T>, valueObjects?: Array<Object>) => void,
    requestParameters: Object,
    resourceKey: string,
    values: Array<T>,
|};

@observer
class ResourceMultiSelect<T: string | number> extends React.Component<Props<T>> {
    static defaultProps = {
        disabled: false,
        idProperty: 'id',
        requestParameters: {},
        values: [],
    };

    @observable resourceListStore: ResourceListStore;

    constructor(props: Props<T>) {
        super(props);

        this.createResourceListStore();
    }

    componentDidUpdate(prevProps: Props<T>) {
        const {
            resourceKey,
            requestParameters,
        } = this.props;

        if (!equals(prevProps.requestParameters, requestParameters) || prevProps.resourceKey !== resourceKey) {
            this.createResourceListStore();
        }
    }

    @action createResourceListStore = () => {
        const {
            resourceKey,
            requestParameters,
        } = this.props;

        this.resourceListStore = new ResourceListStore(resourceKey, requestParameters);
    };

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

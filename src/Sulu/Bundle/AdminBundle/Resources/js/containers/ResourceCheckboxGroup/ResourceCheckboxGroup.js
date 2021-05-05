// @flow
import React from 'react';
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import Checkbox, {CheckboxGroup} from '../../components/Checkbox';
import ResourceListStore from '../../stores/ResourceListStore';
import Loader from '../../components/Loader';

type Props<T: string | number> = {|
    disabled: boolean,
    displayProperty: string,
    idProperty: string,
    onChange: (values: Array<T>, valueObjects?: Array<Object>) => void,
    requestParameters: Object,
    resourceKey: string,
    values: Array<T>,
|};

@observer
class ResourceCheckboxGroup<T: string | number> extends React.Component<Props<T>> {
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

    handleChange: (Array<T>) => void = (values) => {
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
            disabled,
            displayProperty,
            idProperty,
            values,
        } = this.props;

        if (this.resourceListStore.loading || !this.resourceListStore.data) {
            return <Loader size={30} />;
        }

        return (
            <CheckboxGroup
                disabled={disabled}
                onChange={this.handleChange}
                values={values}
            >
                {this.resourceListStore.data.map((object, index) => (
                    <Checkbox key={index} value={object[idProperty]}>
                        {object[displayProperty]}
                    </Checkbox>
                ))}
            </CheckboxGroup>
        );
    }
}

export default ResourceCheckboxGroup;

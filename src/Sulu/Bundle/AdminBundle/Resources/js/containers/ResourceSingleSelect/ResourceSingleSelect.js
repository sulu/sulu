// @flow
import React, {Fragment} from 'react';
import type {Element} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import ResourceListStore from '../../stores/ResourceListStore';
import Loader from '../../components/Loader';
import SingleSelect from '../../components/SingleSelect';
import {translate} from '../../utils/Translator';
import EditOverlay from './EditOverlay';

type Props<T> = {|
    apiOptions: Object,
    disabled: boolean,
    displayProperty: string,
    editable: boolean,
    idProperty: string,
    onChange: (value: ?T) => void,
    overlayTitle?: string,
    resourceKey: string,
    value: ?T,
|};

@observer
export default class ResourceSingleSelect<T: string | number> extends React.Component<Props<T>> {
    static defaultProps = {
        apiOptions: {},
        disabled: false,
        editable: false,
    };

    resourceListStore: ResourceListStore;
    @observable showEditOverlay: boolean = false;

    @computed get data(): Array<Object> {
        const {displayProperty} = this.props;
        return this.resourceListStore.data.concat()
            .sort((data1, data2) => data1[displayProperty] < data2[displayProperty] ? -1 : 1);
    }

    constructor(props: Props<T>) {
        super(props);

        const {
            apiOptions,
            idProperty,
            resourceKey,
        } = this.props;

        this.resourceListStore = new ResourceListStore(resourceKey, apiOptions, idProperty);
    }

    handleReset = () => {
        const {onChange} = this.props;

        onChange(undefined);
    };

    @action handleEdit = () => {
        this.showEditOverlay = true;
    };

    @action handleEditOverlayClose = () => {
        this.showEditOverlay = false;
    };

    render() {
        const {disabled, displayProperty, editable, idProperty, onChange, overlayTitle, value} = this.props;

        if (this.resourceListStore.loading) {
            return <Loader size={30} />;
        }

        return (
            <Fragment>
                <SingleSelect disabled={disabled} onChange={onChange} value={value}>
                    <SingleSelect.Action onClick={this.handleReset}>
                        {translate('sulu_admin.please_choose')}
                    </SingleSelect.Action>
                    {this.data.map((object, index) => ((
                        <SingleSelect.Option key={index} value={object[idProperty]}>
                            {object[displayProperty]}
                        </SingleSelect.Option>
                    ): Element<typeof SingleSelect.Option>))}
                    {editable && <SingleSelect.Divider />}
                    {editable &&
                        <SingleSelect.Action onClick={this.handleEdit}>
                            {translate('sulu_admin.edit')}
                        </SingleSelect.Action>
                    }
                </SingleSelect>
                {editable &&
                    <EditOverlay
                        displayProperty={displayProperty}
                        idProperty={idProperty}
                        onClose={this.handleEditOverlayClose}
                        open={this.showEditOverlay}
                        resourceListStore={this.resourceListStore}
                        title={overlayTitle}
                    />
                }
            </Fragment>
        );
    }
}

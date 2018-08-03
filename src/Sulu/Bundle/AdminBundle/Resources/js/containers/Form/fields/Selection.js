// @flow
import React from 'react';
import {autorun, computed, observable, toJS} from 'mobx';
import equal from 'fast-deep-equal';
import Datagrid from '../../../containers/Datagrid';
import DatagridStore from '../../../containers/Datagrid/stores/DatagridStore';
import {translate} from '../../../utils/Translator';
import SelectionComponent from '../../Selection';
import type {FieldTypeProps} from '../../../types';

type Props = FieldTypeProps<Array<string | number>>;

export default class Selection extends React.Component<Props> {
    datagridStore: ?DatagridStore;
    changeDatagridDisposer: ?() => void;

    constructor(props: Props) {
        super(props);

        if (this.type !== 'overlay' && this.type !== 'datagrid') {
            throw new Error(
                'The Selection field must either be declared as "overlay" or as "datagrid", '
                + 'received type was "' + this.type + '"!'
            );
        }

        const {
            fieldTypeOptions: {
                resource_key: resourceKey,
            },
        } = this.props;

        if (!resourceKey) {
            throw new Error('The selection field needs a "resource_key" option to work properly');
        }

        if (this.type === 'datagrid') {
            const {
                formInspector,
                value,
            } = this.props;

            this.datagridStore = new DatagridStore(
                resourceKey,
                {locale: formInspector.locale, page: observable.box()},
                {},
                value
            );

            this.changeDatagridDisposer = autorun(this.handleDatagridSelectionChange);
        }
    }

    componentWillUnmount() {
        if (this.changeDatagridDisposer) {
            this.changeDatagridDisposer();
        }
    }

    @computed get type() {
        return this.props.fieldTypeOptions.default_type;
    }

    render() {
        if (this.type === 'overlay') {
            return this.renderOverlay();
        }

        if (this.type === 'datagrid') {
            return this.renderDatagrid();
        }
    }

    renderOverlay() {
        const {
            formInspector,
            onChange,
            fieldTypeOptions: {
                resource_key: resourceKey,
                types: {
                    overlay: {
                        adapter,
                        display_properties: displayProperties,
                        icon,
                        label,
                        overlay_title: overlayTitle,
                    },
                },
            },
            value,
        } = this.props;

        if (!adapter) {
            throw new Error('The selection field needs a "adapter" option to work properly');
        }

        return (
            <SelectionComponent
                adapter={adapter}
                displayProperties={displayProperties}
                disabledIds={resourceKey === formInspector.resourceKey && formInspector.id ? [formInspector.id] : []}
                icon={icon}
                label={translate(label)}
                locale={formInspector.locale}
                onChange={onChange}
                resourceKey={resourceKey}
                overlayTitle={translate(overlayTitle)}
                value={value || []}
            />
        );
    }

    renderDatagrid() {
        if (!this.datagridStore) {
            throw new Error('The DatagridStore has not been initialized! This should not happen and is likely a bug.');
        }

        const {
            fieldTypeOptions: {
                types: {
                    datagrid: {
                        adapter,
                    },
                },
            },
        } = this.props;

        if (!adapter) {
            throw new Error('The selection field needs a "adapter" option for the datagrid type to work properly');
        }

        return <Datagrid adapters={[adapter]} store={this.datagridStore} />;
    }

    handleDatagridSelectionChange = () => {
        const {
            props: {
                onChange,
                onFinish,
                value,
            },
            datagridStore,
        } = this;

        if (!datagridStore) {
            throw new Error(
                'The DatagridStore has not been initialized! This should not happen and is likely a bug.'
            );
        }

        if (equal(toJS(value), datagridStore.selectionIds)) {
            return;
        }

        onChange(datagridStore.selectionIds);
        onFinish();
    };
}

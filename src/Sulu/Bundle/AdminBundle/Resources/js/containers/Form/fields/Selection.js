// @flow
import React from 'react';
import {autorun, computed, observable, toJS} from 'mobx';
import equal from 'fast-deep-equal';
import Datagrid from '../../../containers/Datagrid';
import DatagridStore from '../../../containers/Datagrid/stores/DatagridStore';
import MultiAutoComplete from '../../../containers/MultiAutoComplete';
import {translate} from '../../../utils/Translator';
import SelectionComponent from '../../Selection';
import type {FieldTypeProps} from '../../../types';
import selectionStyles from './selection.scss';

type Props = FieldTypeProps<Array<string | number>>;

export default class Selection extends React.Component<Props> {
    datagridStore: ?DatagridStore;
    changeDatagridDisposer: ?() => void;

    constructor(props: Props) {
        super(props);

        if (this.type !== 'overlay' && this.type !== 'datagrid' && this.type !== 'auto_complete') {
            throw new Error(
                'The Selection field must either be declared as "overlay", "datagrid" or as "auto_complete", '
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

        if (this.type === 'auto_complete') {
            return this.renderAutoComplete();
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

    renderAutoComplete() {
        const {
            fieldTypeOptions: {
                resource_key: resourceKey,
                types: {
                    auto_complete: {
                        display_property: displayProperty,
                        filter_parameter: filterParameter,
                        id_property: idProperty,
                        search_properties: searchProperties,
                    },
                },
            },
            formInspector,
            value,
        } = this.props;

        if (!displayProperty) {
            throw new Error('The selection field needs a "display_property" option to work properly!');
        }

        if (!searchProperties) {
            throw new Error('The selection field needs a "search_properties" option to work properly!');
        }

        return (
            <MultiAutoComplete
                displayProperty={displayProperty}
                filterParameter={filterParameter}
                idProperty={idProperty}
                locale={formInspector.locale}
                onChange={this.handleAutoCompleteChange}
                resourceKey={resourceKey}
                searchProperties={searchProperties}
                value={value}
            />
        );
    }

    handleAutoCompleteChange = (value: Array<string | number>) => {
        const {onChange, onFinish} = this.props;
        onChange(value);
        onFinish();
    };

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

        return (
            <div className={selectionStyles.datagrid}>
                <Datagrid adapters={[adapter]} searchable={false} store={this.datagridStore} />
            </div>
        );
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

        if (equal(toJS(value), datagridStore.selectionIds) || datagridStore.dataLoading) {
            return;
        }

        if (datagridStore.loading) {
            return;
        }

        onChange(datagridStore.selectionIds);
        onFinish();
    };
}

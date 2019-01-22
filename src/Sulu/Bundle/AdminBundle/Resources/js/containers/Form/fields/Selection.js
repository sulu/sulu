// @flow
import React from 'react';
import {autorun, computed, observable, toJS} from 'mobx';
import equal from 'fast-deep-equal';
import Datagrid from '../../../containers/Datagrid';
import DatagridStore from '../../../containers/Datagrid/stores/DatagridStore';
import MultiAutoComplete from '../../../containers/MultiAutoComplete';
import {translate} from '../../../utils/Translator';
import MultiSelectionComponent from '../../MultiSelection';
import type {FieldTypeProps} from '../../../types';
import selectionStyles from './selection.scss';

type Props = FieldTypeProps<Array<string | number>>;

const USER_SETTINGS_KEY = 'selection';

export default class Selection extends React.Component<Props> {
    datagridStore: ?DatagridStore;
    changeDatagridDisposer: ?() => void;

    constructor(props: Props) {
        super(props);

        if (this.type !== 'datagrid_overlay' && this.type !== 'datagrid' && this.type !== 'auto_complete') {
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
                fieldTypeOptions: {
                    types: {
                        datagrid: {
                            datagrid_key: datagridKey,
                        },
                    },
                },
                formInspector,
                value,
            } = this.props;

            this.datagridStore = new DatagridStore(
                resourceKey,
                // TODO make optional
                datagridKey,
                USER_SETTINGS_KEY,
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
        const defaultType = this.props.fieldTypeOptions.default_type;
        if (typeof defaultType !== 'string') {
            throw new Error('The "default_type" field-type option must be a string!');
        }

        const {schemaOptions} = this.props;

        if (!schemaOptions) {
            return defaultType;
        }

        const {
            type: {
                value: type = defaultType,
            } = {},
        } = schemaOptions;

        if (typeof type !== 'string') {
            throw new Error('The "type" schema option must be a string!');
        }

        return type;
    }

    render() {
        if (this.type === 'datagrid_overlay') {
            return this.renderDatagridOverlay();
        }

        if (this.type === 'auto_complete') {
            return this.renderAutoComplete();
        }

        if (this.type === 'datagrid') {
            return this.renderDatagrid();
        }

        throw new Error('The "' + this.type + '" type does not exist in the Selection field type.');
    }

    renderDatagridOverlay() {
        const {
            disabled,
            formInspector,
            fieldTypeOptions: {
                resource_key: resourceKey,
                types: {
                    datagrid_overlay: {
                        adapter,
                        datagrid_key: datagridKey,
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
            <MultiSelectionComponent
                adapter={adapter}
                // TODO make optional
                datagridKey={datagridKey}
                disabled={!!disabled}
                disabledIds={resourceKey === formInspector.resourceKey && formInspector.id ? [formInspector.id] : []}
                displayProperties={displayProperties}
                icon={icon}
                label={translate(label, {count: value ? value.length : 0})}
                locale={formInspector.locale}
                onChange={this.handleSelectionChange}
                overlayTitle={translate(overlayTitle)}
                resourceKey={resourceKey}
                value={value || []}
            />
        );
    }

    handleSelectionChange = (selectedIds: Array<string | number>) => {
        const {onChange, onFinish} = this.props;

        onChange(selectedIds);
        onFinish();
    };

    renderAutoComplete() {
        const {
            dataPath,
            disabled,
            fieldTypeOptions: {
                resource_key: resourceKey,
                types: {
                    auto_complete: {
                        allow_add: allowAdd,
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
                allowAdd={allowAdd}
                disabled={!!disabled}
                displayProperty={displayProperty}
                filterParameter={filterParameter}
                id={dataPath}
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
            disabled,
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
                <Datagrid adapters={[adapter]} disabled={!!disabled} searchable={false} store={this.datagridStore} />
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

        if (equal(toJS(value), datagridStore.selectionIds) || datagridStore.dataLoading || datagridStore.loading) {
            return;
        }

        onChange(datagridStore.selectionIds);
        onFinish();
    };
}

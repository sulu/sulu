// @flow
import React from 'react';
import {computed, observable, toJS, reaction} from 'mobx';
import type {IObservableValue} from 'mobx';
import equal from 'fast-deep-equal';
import List from '../../../containers/List';
import ListStore from '../../../containers/List/stores/ListStore';
import MultiAutoComplete from '../../../containers/MultiAutoComplete';
import {translate} from '../../../utils/Translator';
import MultiSelectionComponent from '../../MultiSelection';
import userStore from '../../../stores/UserStore';
import type {FieldTypeProps} from '../../../types';
import selectionStyles from './selection.scss';

type Props = FieldTypeProps<Array<string | number>>;

const USER_SETTINGS_KEY = 'selection';

export default class Selection extends React.Component<Props> {
    listStore: ?ListStore;
    changeListDisposer: ?() => *;

    constructor(props: Props) {
        super(props);

        if (this.type !== 'list_overlay' && this.type !== 'list' && this.type !== 'auto_complete') {
            throw new Error(
                'The Selection field must either be declared as "overlay", "list" or as "auto_complete", '
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

        if (this.type === 'list') {
            const {
                fieldTypeOptions: {
                    types: {
                        list: {
                            list_key: listKey,
                        },
                    },
                },
                value,
            } = this.props;

            this.listStore = new ListStore(
                resourceKey,
                listKey || resourceKey,
                USER_SETTINGS_KEY,
                {locale: this.locale, page: observable.box()},
                {},
                value
            );

            this.changeListDisposer = reaction(
                () => (this.listStore ? this.listStore.selectionIds : []),
                this.handleListSelectionChange
            );
        }
    }

    componentWillUnmount() {
        if (this.changeListDisposer) {
            this.changeListDisposer();
        }
    }

    @computed get locale(): IObservableValue<string> {
        const {formInspector} = this.props;

        return formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
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
        if (this.type === 'list_overlay') {
            return this.renderListOverlay();
        }

        if (this.type === 'auto_complete') {
            return this.renderAutoComplete();
        }

        if (this.type === 'list') {
            return this.renderList();
        }

        throw new Error('The "' + this.type + '" type does not exist in the Selection field type.');
    }

    renderListOverlay() {
        const {
            disabled,
            formInspector,
            fieldTypeOptions: {
                resource_key: resourceKey,
                types: {
                    list_overlay: {
                        adapter,
                        list_key: listKey,
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
                disabled={!!disabled}
                disabledIds={resourceKey === formInspector.resourceKey && formInspector.id ? [formInspector.id] : []}
                displayProperties={displayProperties}
                icon={icon}
                label={translate(label, {count: value ? value.length : 0})}
                listKey={listKey || resourceKey}
                locale={this.locale}
                onChange={this.handleMultiSelectionChange}
                overlayTitle={translate(overlayTitle)}
                resourceKey={resourceKey}
                value={value || []}
            />
        );
    }

    handleMultiSelectionChange = (selectedIds: Array<string | number>) => {
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
                locale={this.locale}
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

    renderList() {
        if (!this.listStore) {
            throw new Error('The ListStore has not been initialized! This should not happen and is likely a bug.');
        }

        const {
            disabled,
            fieldTypeOptions: {
                types: {
                    list: {
                        adapter,
                    },
                },
            },
        } = this.props;

        if (!adapter) {
            throw new Error('The selection field needs a "adapter" option for the list type to work properly');
        }

        return (
            <div className={selectionStyles.list}>
                <List adapters={[adapter]} disabled={!!disabled} searchable={false} store={this.listStore} />
            </div>
        );
    }

    handleListSelectionChange = (selectedIds: Array<string | number>) => {
        const {onChange, onFinish, value} = this.props;

        if (!this.listStore) {
            throw new Error(
                'The ListStore has not been initialized! This should not happen and is likely a bug.'
            );
        }

        if (this.listStore.dataLoading || this.listStore.loading) {
            return;
        }

        if (!equal(toJS(value), toJS(selectedIds))) {
            onChange(selectedIds);
            onFinish();
        }
    };
}

// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from '../../../utils/Translator';
import CollapsibleCollection from '../../../components/CollapsibleCollection';
import Loader from '../../../components/Loader';
import Table from '../../../components/Table';
import Router from '../../../services/Router';
import MultiListOverlay from '../../MultiListOverlay';
import FormInspector from '../../Form/FormInspector';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';
import AttributeGroupTable from '../AttributeGroupTable';
import attributeGroupTableStyles from '../AttributeGroupTable/attributeGroupTable.scss';
import AttributeFieldCell from './AttributeFieldCell';
import AttributeRemoveButton from './AttributeRemoveButton';
import type {CollapsibleActionConfig} from '../../../components/CollapsibleCollection/types';
import type {Entry, FieldColumn, Group} from '../types';
import type {IObservableValue} from 'mobx/lib/mobx';

type Props = {|
    dataPath: string,
    disabled?: boolean,
    formInspector: FormInspector,
    listKey: string,
    locale: IObservableValue<string>,
    onChange: (value: Array<Entry>) => void,
    resourceKey: string,
    router: ?Router,
    schemaPath: string,
    value: ?Array<Entry>,
|};

const COLUMNS: Array<FieldColumn> = [
    {name: 'required', title: 'sulu_product.attribute_required', type: 'checkbox'},
    {name: 'variantSpecific', title: 'sulu_product.attribute_variant', type: 'checkbox'},
];

/**
 * @experimental We can not yet give BC Promise for this new container in Sulu 3.1.
 */
@observer
class ProductFamilyAttributes extends React.Component<Props> {
    selectionStore: MultiSelectionStore<string>;

    requestedIds: Set<string>;

    @observable overlayOpen: boolean = false;

    constructor(props: Props) {
        super(props);

        const ids = this.value.map((entry) => entry.id);

        this.requestedIds = new Set(ids);
        this.selectionStore = new MultiSelectionStore(this.props.resourceKey, ids, this.props.locale, 'ids');
    }

    componentDidUpdate() {
        const ids = this.value.map((entry) => entry.id);

        if (!ids.some((id) => !this.itemsById.has(id) && !this.requestedIds.has(id))) {
            return;
        }

        this.requestedIds = new Set(ids);
        this.selectionStore.loadItems(ids);
    }

    @computed get value(): Array<Entry> {
        return this.props.value || [];
    }

    @computed get itemsById(): Map<string, Object> {
        return new Map(this.selectionStore.items.map((item) => [item.id, item]));
    }

    @computed get selectedItems(): Array<Object> {
        return this.value.map((entry) => this.itemsById.get(entry.id)).filter(Boolean);
    }

    @computed get groups(): Array<Group> {
        const groups = {};

        this.value.forEach((entry) => {
            const item = this.itemsById.get(entry.id);

            if (!item) {
                return;
            }

            if (!groups[item.group]) {
                groups[item.group] = {entries: [], id: item.group, title: item.groupName || ''};
            }

            groups[item.group].entries.push({entry, item});
        });

        return Object.keys(groups)
            .map((groupId) => {
                const group = groups[groupId];

                group.entries.sort((a, b) => (a.item.position || 0) - (b.item.position || 0));

                return {
                    ...group,
                    subtitle: translate('sulu_product.attribute_count', {count: group.entries.length}),
                };
            })
            .sort((a, b) => a.title.localeCompare(b.title));
    }

    @computed get collapsibleActions(): Array<CollapsibleActionConfig> {
        if (this.props.disabled) {
            return [];
        }

        return [{
            icon: 'su-trash-alt',
            label: translate('sulu_admin.delete'),
            onClick: this.handleGroupRemove,
        }];
    }

    renderHeaderCells(): Array<Object> {
        const cells = [
            <Table.HeaderCell className={attributeGroupTableStyles.labelCell} key="label">
                {translate('sulu_product.attribute')}
            </Table.HeaderCell>,
            ...COLUMNS.map((column) => (
                <Table.HeaderCell className={attributeGroupTableStyles.fieldCell} key={column.name}>
                    {translate(column.title)}
                </Table.HeaderCell>
            )),
        ];

        // Table.Header clones every child unconditionally, so an omitted cell must never appear as
        // a false/null child; build the array instead.
        if (!this.props.disabled) {
            cells.push(<Table.HeaderCell className={attributeGroupTableStyles.removeCell} key="delete" />);
        }

        return cells;
    }

    renderRowCells(entry: Entry, item: Object): Array<Object> {
        const {dataPath, disabled, formInspector, router, schemaPath} = this.props;

        const cells = [
            <Table.Cell className={attributeGroupTableStyles.labelCell} key="label">{item.name}</Table.Cell>,
            ...COLUMNS.map((column) => (
                <AttributeFieldCell
                    column={column}
                    dataPath={dataPath}
                    disabled={!!disabled}
                    entry={entry}
                    formInspector={formInspector}
                    key={column.name}
                    onChange={this.handleFieldChange}
                    router={router}
                    schemaPath={schemaPath}
                />
            )),
        ];

        // Table.Row clones every child unconditionally, so an omitted cell must never appear as a
        // false/null child; build the array instead. AttributeRemoveButton is its own Table.Cell.
        if (!disabled) {
            cells.push(<AttributeRemoveButton id={entry.id} key="delete" onClick={this.handleEntryRemove} />);
        }

        return cells;
    }

    @action handleAddClick = () => {
        this.overlayOpen = true;
    };

    @action handleOverlayClose = () => {
        this.overlayOpen = false;
    };

    @action handleOverlayConfirm = (selectedItems: Array<Object>) => {
        const existing = {};
        this.value.forEach((entry) => {
            existing[entry.id] = entry;
        });

        // The attributes list always returns group, groupName and position.
        this.requestedIds = new Set(selectedItems.map((item) => item.id));
        this.selectionStore.set(selectedItems);
        this.overlayOpen = false;

        this.props.onChange(selectedItems.map((item) => existing[item.id] || {
            id: item.id,
            required: false,
            variantSpecific: false,
        }));
    };

    handleFieldChange = (id: string, name: string, value: mixed) => {
        this.props.onChange(this.value.map((entry) => entry.id === id ? {...entry, [name]: value} : entry));
    };

    handleEntryRemove = (id: string) => {
        this.props.onChange(this.value.filter((entry) => entry.id !== id));
    };

    handleGroupRemove = (index: number) => {
        const groupId = this.groups[index].id;

        this.props.onChange(this.value.filter((entry) => {
            const item = this.itemsById.get(entry.id);

            // An unresolved id belongs to no group and must survive a group delete.
            return !item || item.group !== groupId;
        }));
    };

    handleCollectionChange = () => {};

    renderCollapsibleContent = (group: Group) => {
        return (
            <AttributeGroupTable headerCells={this.renderHeaderCells()}>
                {group.entries.map(({entry, item}) => (
                    <Table.Row id={entry.id} key={entry.id}>
                        {this.renderRowCells(entry, item)}
                    </Table.Row>
                ))}
            </AttributeGroupTable>
        );
    };

    render() {
        if (this.selectionStore.loading) {
            return <Loader />;
        }

        return (
            <Fragment>
                <CollapsibleCollection
                    actions={this.collapsibleActions}
                    addButtonText={translate('sulu_product.add_attributes_overlay_title')}
                    movable={false}
                    onAddClick={this.props.disabled ? undefined : this.handleAddClick}
                    onChange={this.handleCollectionChange}
                    renderCollapsibleContent={this.renderCollapsibleContent}
                    value={this.groups}
                />
                <MultiListOverlay
                    adapter="table"
                    listKey={this.props.listKey}
                    locale={this.props.locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    preSelectedItems={this.selectedItems}
                    resourceKey={this.props.resourceKey}
                    title={translate('sulu_product.add_attributes_overlay_title')}
                />
            </Fragment>
        );
    }
}

export default ProductFamilyAttributes;

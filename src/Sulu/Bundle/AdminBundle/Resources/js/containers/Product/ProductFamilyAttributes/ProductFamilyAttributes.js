// @flow
import React, {Fragment} from 'react';
import {action, computed, observable, reaction} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from '../../../utils/Translator';
import CollapsibleCollection from '../../../components/CollapsibleCollection';
import Loader from '../../../components/Loader';
import Table from '../../../components/Table';
import MultiListOverlay from '../../MultiListOverlay';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';
import AttributeGroupTable from '../AttributeGroupTable';
import attributeGroupTableStyles from '../AttributeGroupTable/attributeGroupTable.scss';
import AttributeFlagCheckbox from './AttributeFlagCheckbox';
import AttributeRemoveButton from './AttributeRemoveButton';
import type {CollapsibleActionConfig} from '../../../components/CollapsibleCollection/types';
import type {IObservableValue} from 'mobx/lib/mobx';

export type Entry = {
    id: string,
    required: boolean,
    variantSpecific: boolean,
};

// "subtitle" is optional because CollapsibleConfig declares it optional and invariant.
type Group = {
    entries: Array<{entry: Entry, item: Object}>,
    id: string,
    subtitle?: string,
    title: string,
};

type Props = {|
    disabled?: boolean,
    locale: IObservableValue<string>,
    onChange: (value: Array<Entry>) => void,
    value: ?Array<Entry>,
|};

const RESOURCE_KEY = 'attributes';
const LIST_KEY = 'attributes';

@observer
class ProductFamilyAttributes extends React.Component<Props> {
    selectionStore: MultiSelectionStore<string>;

    disposeInitialLoadReaction: () => void;

    @observable overlayOpen: boolean = false;

    // Only the first load blanks the field behind a Loader; a reload triggered by the overlay must
    // leave the already-rendered cards in place.
    @observable initialLoadDone: boolean = false;

    constructor(props: Props) {
        super(props);

        // "group" and "groupName" are visibility="never" in the bundle's attributes list, so
        // AbstractListBuilder::setSelectFields() strips them unless named here explicitly.
        this.selectionStore = new MultiSelectionStore(
            RESOURCE_KEY,
            this.value.map((entry) => entry.id),
            this.props.locale,
            'ids',
            {fields: 'id,name,group,groupName,position'}
        );

        this.disposeInitialLoadReaction = reaction(
            () => this.selectionStore.loading,
            (loading) => {
                if (!loading) {
                    this.markInitialLoadDone();
                }
            },
            {fireImmediately: true}
        );
    }

    componentWillUnmount() {
        this.disposeInitialLoadReaction();
    }

    @action markInitialLoadDone = () => {
        this.initialLoadDone = true;
    };

    @computed get value(): Array<Entry> {
        return this.props.value || [];
    }

    @computed get selectedItems(): Array<Object> {
        return this.value.map((entry) => this.selectionStore.getById(entry.id)).filter(Boolean);
    }

    @computed get groups(): Array<Group> {
        const groups = {};

        this.value.forEach((entry) => {
            const item = this.selectionStore.getById(entry.id);

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
            <Table.HeaderCell className={attributeGroupTableStyles.flagCell} key="required">
                {translate('sulu_product.attribute_required')}
            </Table.HeaderCell>,
            <Table.HeaderCell className={attributeGroupTableStyles.flagCell} key="variantSpecific">
                {translate('sulu_product.attribute_variant')}
            </Table.HeaderCell>,
        ];

        // Table.Header clones every child unconditionally, so an omitted cell must never appear as
        // a false/null child; build the array instead.
        if (!this.props.disabled) {
            cells.push(<Table.HeaderCell className={attributeGroupTableStyles.removeCell} key="delete" />);
        }

        return cells;
    }

    renderRowCells(entry: Entry, item: Object): Array<Object> {
        const {disabled} = this.props;

        const cells = [
            <Table.Cell className={attributeGroupTableStyles.labelCell} key="label">{item.name}</Table.Cell>,
            <Table.Cell className={attributeGroupTableStyles.flagCell} key="required">
                <AttributeFlagCheckbox
                    checked={entry.required}
                    disabled={!!disabled}
                    flag="required"
                    id={entry.id}
                    onChange={this.handleFlagChange}
                />
            </Table.Cell>,
            <Table.Cell className={attributeGroupTableStyles.flagCell} key="variantSpecific">
                <AttributeFlagCheckbox
                    checked={entry.variantSpecific}
                    disabled={!!disabled}
                    flag="variantSpecific"
                    id={entry.id}
                    onChange={this.handleFlagChange}
                />
            </Table.Cell>,
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

        // The overlay's items lack group/groupName, so setting them directly would flash every row
        // into one untitled card. Reload from the resource instead.
        this.selectionStore.loadItems(selectedItems.map((item) => item.id));
        this.overlayOpen = false;

        this.props.onChange(selectedItems.map((item) => existing[item.id] || {
            id: item.id,
            required: false,
            variantSpecific: false,
        }));
    };

    handleFlagChange = (id: string, flag: string, checked: boolean) => {
        this.props.onChange(this.value.map((entry) => entry.id === id ? {...entry, [flag]: checked} : entry));
    };

    handleEntryRemove = (id: string) => {
        this.props.onChange(this.value.filter((entry) => entry.id !== id));
    };

    handleGroupRemove = (index: number) => {
        const groupId = this.groups[index].id;

        this.props.onChange(this.value.filter((entry) => {
            const item = this.selectionStore.getById(entry.id);

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
        if (this.selectionStore.loading && !this.initialLoadDone) {
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
                    listKey={LIST_KEY}
                    locale={this.props.locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    preSelectedItems={this.selectedItems}
                    resourceKey={RESOURCE_KEY}
                    title={translate('sulu_product.add_attributes_overlay_title')}
                />
            </Fragment>
        );
    }
}

export default ProductFamilyAttributes;

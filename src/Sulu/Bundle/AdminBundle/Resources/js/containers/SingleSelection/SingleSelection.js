// @flow
import React, {Fragment} from 'react';
import {action, reaction, observable, toJS} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import jexl from 'jexl';
import SingleItemSelection from '../../components/SingleItemSelection';
import PublishIndicator from '../../components/PublishIndicator';
import SingleSelectionStore from '../../stores/SingleSelectionStore';
import SingleListOverlay from '../SingleListOverlay';
import singleSelectionStyles from './singleSelection.scss';

type Props = {|
    adapter: string,
    allowDeselectForDisabledItems: boolean,
    detailOptions?: Object,
    disabled: boolean,
    disabledIds: Array<string | number>,
    displayProperties: Array<string>,
    emptyText: string,
    icon: string,
    itemDisabledCondition?: ?string,
    listKey: string,
    listOptions?: Object,
    locale?: ?IObservableValue<string>,
    onChange: (selectedIds: ?string | number, selectedItem: ?Object) => void,
    onItemClick?: (id: ?number | string, item: ?Object) => void,
    overlayTitle: string,
    resourceKey: string,
    value: ?string | number,
|};

@observer
class SingleSelection extends React.Component<Props> {
    static defaultProps = {
        allowDeselectForDisabledItems: false,
        disabled: false,
        disabledIds: [],
        icon: 'su-plus',
    };

    singleSelectionStore: SingleSelectionStore<string | number>;
    changeDisposer: () => *;

    @observable overlayOpen: boolean = false;

    constructor(props: Props) {
        super(props);

        const {detailOptions, locale, resourceKey, value} = this.props;

        // TODO instead of creating the store here and passing the props required for this, we should pass a store prop
        this.singleSelectionStore = new SingleSelectionStore(resourceKey, value, locale, detailOptions);
        this.changeDisposer = reaction(
            () => this.singleSelectionStore.item === undefined
                ? undefined // this value is returned if nothing was assigned
                : this.singleSelectionStore.item === null
                    ? null // this value is returned if something was assigned but was deleted
                    : this.singleSelectionStore.item.id,
            (loadedItemId: ?string | number) => {
                const {onChange, value} = this.props;

                if (value !== loadedItemId) {
                    onChange(loadedItemId, this.singleSelectionStore.item);
                }
            }
        );
    }

    componentWillUnmount() {
        this.changeDisposer();
    }

    componentDidUpdate() {
        const newId = toJS(this.props.value);
        const loadedId = this.singleSelectionStore.item ? this.singleSelectionStore.item.id : undefined;

        if (loadedId !== newId) {
            this.singleSelectionStore.loadItem(newId);
        }
    }

    @action openOverlay() {
        this.overlayOpen = true;
    }

    @action closeOverlay() {
        this.overlayOpen = false;
    }

    @action handleOverlayOpen = () => {
        this.openOverlay();
    };

    @action handleOverlayClose = () => {
        this.closeOverlay();
    };

    handleOverlayConfirm = (selectedItem: Object) => {
        // need to load the whole item as the object returned from the overlay may not contain all displayProperties
        this.singleSelectionStore.loadItem(selectedItem.id);
        this.closeOverlay();
    };

    handleRemove = () => {
        this.singleSelectionStore.clear();
    };

    render() {
        const {
            adapter,
            allowDeselectForDisabledItems,
            listKey,
            disabled,
            disabledIds,
            displayProperties,
            emptyText,
            icon,
            itemDisabledCondition,
            locale,
            listOptions,
            onItemClick,
            overlayTitle,
            resourceKey,
        } = this.props;
        const {item, loading} = this.singleSelectionStore;
        const columns = displayProperties.length;

        const itemDisabled = (!!item && disabledIds.includes(item.id)) ||
            (!!item && !!itemDisabledCondition && jexl.evalSync(itemDisabledCondition, item));

        const published = item ? item.published : undefined;
        const publishedState = item ? item.publishedState : undefined;

        return (
            <Fragment>
                <SingleItemSelection
                    allowRemoveWhileItemDisabled={allowDeselectForDisabledItems}
                    disabled={disabled}
                    emptyText={emptyText}
                    id={item && item.id}
                    itemDisabled={itemDisabled}
                    leftButton={{
                        icon,
                        onClick: this.handleOverlayOpen,
                    }}
                    loading={loading}
                    onItemClick={onItemClick}
                    onRemove={item ? this.handleRemove : undefined}
                    value={item}
                >
                    {item &&
                        <div className={singleSelectionStyles.itemContainer}>
                            {(publishedState !== undefined || published !== undefined)
                            && !(publishedState && published) &&
                                <div className={singleSelectionStyles.publishIndicator}>
                                    <PublishIndicator
                                        draft={!publishedState}
                                        published={!!published}
                                    />
                                </div>
                            }

                            <div className={singleSelectionStyles.columnList}>
                                {displayProperties.map((displayProperty) => (
                                    <span
                                        className={singleSelectionStyles.itemColumn}
                                        key={displayProperty}
                                        style={{width: 100 / columns + '%'}}
                                    >
                                        {item[displayProperty]}
                                    </span>
                                ))}
                            </div>
                        </div>
                    }
                </SingleItemSelection>
                {!loading &&
                    <SingleListOverlay
                        adapter={adapter}
                        disabledIds={disabledIds}
                        itemDisabledCondition={itemDisabledCondition}
                        listKey={listKey}
                        locale={locale}
                        onClose={this.handleOverlayClose}
                        onConfirm={this.handleOverlayConfirm}
                        open={this.overlayOpen}
                        options={listOptions}
                        preSelectedItem={item}
                        resourceKey={resourceKey}
                        title={overlayTitle}
                    />
                }
            </Fragment>
        );
    }
}

export default SingleSelection;

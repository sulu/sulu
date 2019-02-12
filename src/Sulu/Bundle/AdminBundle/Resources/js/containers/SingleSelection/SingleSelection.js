// @flow
import React, {Fragment} from 'react';
import {action, reaction, observable, toJS} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import SingleItemSelection from '../../components/SingleItemSelection';
import SingleSelectionStore from '../../stores/SingleSelectionStore';
import SingleDatagridOverlay from '../SingleDatagridOverlay';
import singleSelectionStyles from './singleSelection.scss';

type Props = {|
    adapter: string,
    datagridKey: string,
    disabled: boolean,
    disabledIds: Array<string | number>,
    displayProperties: Array<string>,
    emptyText: string,
    icon: string,
    locale?: ?IObservableValue<string>,
    onChange: (selectedIds: ?string | number) => void,
    overlayTitle: string,
    resourceKey: string,
    value: ?string | number,
|};

@observer
export default class SingleSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        disabledIds: [],
        icon: 'su-plus',
    };

    singleSelectionStore: SingleSelectionStore;
    changeDisposer: () => *;

    @observable overlayOpen: boolean = false;

    constructor(props: Props) {
        super(props);

        const {locale, resourceKey, value} = this.props;

        this.singleSelectionStore = new SingleSelectionStore(resourceKey, value, locale);
        this.changeDisposer = reaction(
            () => (this.singleSelectionStore.item ? this.singleSelectionStore.item.id : undefined),
            (loadedItemId: ?string | number) => {
                const {onChange, value} = this.props;

                if (value !== loadedItemId) {
                    onChange(loadedItemId);
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
            datagridKey,
            disabled,
            disabledIds,
            displayProperties,
            emptyText,
            icon,
            locale,
            overlayTitle,
            resourceKey,
        } = this.props;
        const {item, loading} = this.singleSelectionStore;
        const columns = displayProperties.length;

        return (
            <Fragment>
                <SingleItemSelection
                    disabled={disabled}
                    emptyText={emptyText}
                    leftButton={{
                        icon,
                        onClick: this.handleOverlayOpen,
                    }}
                    loading={loading}
                    onRemove={this.singleSelectionStore.item ? this.handleRemove : undefined}
                >
                    {item &&
                        <div>
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
                    }
                </SingleItemSelection>
                <SingleDatagridOverlay
                    adapter={adapter}
                    datagridKey={datagridKey}
                    disabledIds={disabledIds}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    preSelectedItem={item}
                    resourceKey={resourceKey}
                    title={overlayTitle}
                />
            </Fragment>
        );
    }
}

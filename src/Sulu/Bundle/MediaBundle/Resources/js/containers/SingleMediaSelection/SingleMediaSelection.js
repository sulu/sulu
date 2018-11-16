// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import type {IObservableValue} from 'mobx';
import {action, observable} from 'mobx';
import SingleItemSelection from 'sulu-admin-bundle/components/SingleItemSelection';
import singleSelectionStyles from 'sulu-admin-bundle/containers/SingleSelection/singleSelection.scss';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import MediaSelectionOverlay from '../MediaSelection/MediaSelectionOverlay';

type Props = {|
    disabled: boolean,
    locale: IObservableValue<string>,
    onChange: (selectedIds: ?number) => void,
    value: ?number,
|}

@observer
export default class SingleMediaSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    @observable overlayOpen: boolean = false;

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

    handleOverlayConfirm = (selectedMedia: Array<Object>) => {
        // this.singleSelectionStore.set(selectedItem);
        console.log(selectedMedia);
        this.closeOverlay();
    };

    handleRemove = () => {
        // this.singleSelectionStore.clear();
        console.log('clear selection');
    };

    render() {
        const {
            disabled,
            locale,
            value,
        } = this.props;

        return (
            <Fragment>
                <SingleItemSelection
                    disabled={disabled}
                    emptyText={translate('sulu_media.select_media_singular')}
                    leftButton={{
                        icon: 'su-image',
                        onClick: this.handleOverlayOpen,
                    }}
                    //onRemove={this.singleSelectionStore.item ? this.handleRemove : undefined}
                    onRemove={this.handleRemove}
                >
                    {value &&
                    <div>
                        <span className={singleSelectionStyles.itemColumn}>
                            {value}
                        </span>
                    </div>
                    }
                </SingleItemSelection>
                <MediaSelectionOverlay
                    excludedIds={value ? [value] : []}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                />
            </Fragment>
        );
    }
}

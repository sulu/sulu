// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import textVersion from 'textversionjs';
import {MimeTypeIndicator} from 'sulu-media-bundle/components';
import {SingleMediaSelectionOverlay} from 'sulu-media-bundle/containers';
import type {Media} from 'sulu-media-bundle/types';
import {Button, Icon, Input} from 'sulu-admin-bundle/components';
import {TextEditor} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import itemStyles from './item.scss';
import type {TeaserItem} from './types';

type Props = {|
    description: ?string,
    editing: boolean,
    id: number | string,
    locale: IObservableValue<string>,
    mediaId: ?number,
    onApply: (item: TeaserItem) => void,
    onCancel: (id: number | string) => void,
    title: ?string,
    type: string,
|};

@observer
export default class Item extends React.Component<Props> {
    static mediaUrl: ?string = undefined;

    @observable title: ?string = undefined;
    @observable description: ?string = undefined;
    @observable mediaId: ?number = undefined;
    @observable mediaOverlayOpen: boolean = false;

    componentDidMount() {
        this.setStateFromProps();
    }

    componentDidUpdate(prevProps: Props) {
        if (prevProps.title !== this.props.title
            || prevProps.description !== this.props.description
            || prevProps.mediaId !== this.props.mediaId
        ) {
            this.setStateFromProps();
        }

        if (prevProps.editing === true && this.props.editing === false) {
            this.setStateFromProps();
        }
    }

    @action setStateFromProps() {
        const {description, mediaId, title} = this.props;

        this.title = title;
        this.description = description;
        this.mediaId = mediaId;
    }

    @action handleMediaClick = () => {
        this.mediaOverlayOpen = true;
    };

    @action handleMediaConfirm = (media: Media) => {
        this.mediaId = media.id;
        this.mediaOverlayOpen = false;
    };

    @action handleMediaOverlayClose = () => {
        this.mediaOverlayOpen = false;
    };

    @action handleTitleChange = (title: ?string) => {
        this.title = title;
    };

    @action handleDescriptionChange = (description: ?string) => {
        this.description = description;
    };

    handleCancel = () => {
        const {id, onCancel} = this.props;

        onCancel(id);
    };

    handleApply = () => {
        const {id, onApply, type} = this.props;

        onApply({description: this.description, id, mediaId: this.mediaId, title: this.title, type});
    };

    render() {
        const {editing, locale, type} = this.props;
        const {mediaUrl} = Item;

        // TODO replace type with correct translation from TeaserProviderRegistry
        return (
            editing
                ? <Fragment>
                    <div className={itemStyles.editForm}>
                        <div className={itemStyles.form}>
                            <div className={itemStyles.mediaColumn}>
                                {mediaUrl &&
                                    <button className={itemStyles.mediaButton} onClick={this.handleMediaClick}>
                                        {this.mediaId
                                            ? <img src={mediaUrl.replace(':id', this.mediaId.toString())} />
                                            : <MimeTypeIndicator iconSize={16} mimeType="image" />
                                        }
                                        <Icon className={itemStyles.mediaButtonIcon} name="su-pen" />
                                    </button>
                                }
                            </div>
                            <div className={itemStyles.formColumn}>
                                <div className={itemStyles.titleInput}>
                                    <Input onChange={this.handleTitleChange} value={this.title} />
                                </div>
                                <div className={itemStyles.descriptionTextArea}>
                                    <TextEditor
                                        adapter="ckeditor5"
                                        locale={locale}
                                        onChange={this.handleDescriptionChange}
                                        value={this.description}
                                    />
                                </div>
                            </div>
                        </div>
                        <div className={itemStyles.buttons}>
                            <Button onClick={this.handleCancel}>{translate('sulu_admin.cancel')}</Button>
                            <Button onClick={this.handleApply} skin="primary">{translate('sulu_admin.apply')}</Button>
                        </div>
                    </div>
                    <SingleMediaSelectionOverlay
                        locale={locale}
                        onClose={this.handleMediaOverlayClose}
                        onConfirm={this.handleMediaConfirm}
                        open={this.mediaOverlayOpen}
                    />
                </Fragment>
                : <div className={itemStyles.item}>
                    <div className={itemStyles.media}>
                        {mediaUrl && this.mediaId && <img src={mediaUrl.replace(':id', this.mediaId.toString())} />}
                    </div>
                    <div className={itemStyles.content}>
                        <p className={itemStyles.title}>{this.title}</p>
                        <p className={itemStyles.description}>
                            {this.description && textVersion(this.description)}
                        </p>
                    </div>
                    <p className={itemStyles.type}>{type}</p>
                </div>
        );
    }
}

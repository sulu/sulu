// @flow
import React from 'react';
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import textVersion from 'textversionjs';
import Button from '../../components/Button';
import Input from '../../components/Input';
import TextEditor from '../../containers/TextEditor';
import {translate} from '../../utils/Translator';
import itemStyles from './item.scss';
import type {TeaserItem} from './types';

type Props = {|
    description: ?string,
    editing: boolean,
    id: number | string,
    locale: ?IObservableValue<string>,
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

    componentDidMount() {
        this.setStateFromProps();
    }

    componentDidUpdate(prevProps: Props) {
        if (prevProps.title !== this.props.title || prevProps.description !== this.props.description) {
            this.setStateFromProps();
        }

        if (prevProps.editing === true && this.props.editing === false) {
            this.setStateFromProps();
        }
    }

    @action setStateFromProps() {
        const {description, title} = this.props;

        this.title = title;
        this.description = description;
    }

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

        onApply({description: this.description, id, title: this.title, type});
    };

    render() {
        const {editing, locale, mediaId, type} = this.props;
        const {mediaUrl} = Item;

        // TODO replace type with correct translation from TeaserProviderRegistry
        return (
            editing
                ? <div className={itemStyles.editForm}>
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
                    <div className={itemStyles.buttons}>
                        <Button onClick={this.handleCancel}>{translate('sulu_admin.cancel')}</Button>
                        <Button onClick={this.handleApply} skin="primary">{translate('sulu_admin.apply')}</Button>
                    </div>
                </div>
                : <div className={itemStyles.item}>
                    <div className={itemStyles.media}>
                        {mediaUrl && mediaId && <img src={mediaUrl.replace(':id', mediaId.toString())} />}
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
